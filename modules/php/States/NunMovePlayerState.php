<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NunMovePlayerState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 32,
      type: StateType::ACTIVE_PLAYER,
      description: clienttranslate('${roleName} ${player_name} must move'),
      descriptionMyTurn: clienttranslate('${you} (${roleName}) must move'),
      updateGameProgression: true,
    );
  }

  public function getArgs(): array
  {
    $nun = $this->game->getNunList()->getActiveNun();
    $distance = count($nun->move->spaces);
    $actions = $this->game->board->getNunActions();
    $actionsForNow = $this->game->board->getActionsForDistance($actions, $distance);
    foreach ($actions as $action => &$info) {
      $info['disabled'] = !in_array($action, $actionsForNow);
    }
    $undo = $nun->move->undo != $nun->move->spaces;
    return [
      'i18n' => ['roleName'],
      'actions' => $actions,
      'distance' => $distance,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'possible' => $this->game->board->getNunPossibleMoves($nun),
      'role' => $nun->role,
      'roleName' => $nun->roleName,
      'undo' => $undo,
    ];
  }

  #[PossibleAction]
  public function actMove(array $args, int $version, int $location)
  {
    $this->game->checkVersion($version);
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Cannot move to location $location");
    }

    $possible = $args['possible'][$location];
    $spaces = $possible->spaces;
    array_shift($spaces);

    $nuns = $this->game->getNunList();
    $nun = &$nuns->getActiveNun();
    $novices = $this->game->getNoviceList();
    $locationsWithNovices = $novices->getLocationsWithNovices();
    $oldSpaceId = $nun->location;
    $oldRoomId = $nun->room;
    $oldNovicesVisible = $nuns->getNovicesVisible($novices);
    $this->game->debug("old room $oldRoomId space $oldSpaceId oldNovicesVisible: " . json_encode($oldNovicesVisible) . ' // ');
    foreach ($spaces as $spaceId) {
      $nun->location = $spaceId;
      $nun->move->spaces[] = $nun->location;
      $nun->room = $this->game->board->getRoomId($nun->location);
      $this->bga->notify->all('nunMove', clienttranslate('${roleName} ${player_name} moves to ${location}'), [
        'i18n' => ['roleName'],
        'preserve' => ['role'],
        'location' => $nun->location,
        'player_id' => $nun->playerId,
        'player_name' => $nun->playerName,
        'role' => $nun->role,
        'roleName' => $nun->roleName,
      ]);

      if ($nun->room != $oldRoomId) {
        $nun->move->undo = $nun->move->spaces;
        $novicesVisible = $nuns->getNovicesVisible($novices);
        $this->game->debug("new room {$nun->room} space $spaceId novicesVisible: " . json_encode($novicesVisible) . ' // ');
        foreach ($novicesVisible as $playerId => $visible) {
          $novice = &$novices->get($playerId);
          $oldVisible = $oldNovicesVisible[$playerId];
          if ($visible && !$oldVisible) {
            $nun->move->deviate = true;
            $this->bga->notify->all('noviceMove', clienttranslate('${player_name} is visible at ${visibleLocation}'), [
              'player_id' => $novice->playerId,
              'player_name' => $novice->playerName,
              'visibleLocation' => $novice->location,
            ]);
          } else if (!$visible && $oldVisible) {
            $this->bga->notify->all('noviceMove', '', [
              'location' => $novice->startLocation,
              'player_id' => $novice->playerId,
              'player_name' => $novice->playerName,
            ]);
          }
        }
        $oldNovicesVisible = $novicesVisible;
      }
      $oldSpaceId = $spaceId;
      $oldRoomId = $nun->room;

      if (array_key_exists($spaceId, $locationsWithNovices)) {
        foreach ($locationsWithNovices[$spaceId] as $playerId) {
          $novice = &$novices->get($playerId);
          $novice->caught = true;
          $novice->move->caughtHistory = true;
          $nun->move->deviate = $this->game->board->getNunDeviate($nun, $novices);
          $nun->move->undo = $nun->move->spaces;
          $this->bga->playerStats->inc('caught', 1, $nun->playerId, true);
          $this->bga->playerStats->inc('caughtTimes', 1, $novice->playerId);

          $this->bga->notify->all('noviceCaught', clienttranslate('${roleName} ${player_name} catches ${player_name2} at ${location}!'), [
            'i18n' => ['roleName'],
            'preserve' => ['caught', 'caughtMeter', 'player_id2', 'role'],
            'caught' => $novice->caught,
            'caughtMeter' => $this->game->getCaught(),
            'location' => $spaceId,
            'player_id' => $nun->playerId,
            'player_id2' => $novice->playerId,
            'player_name' => $nun->playerName,
            'player_name2' => $novice->playerName,
            'role' => $nun->role,
            'roleName' => $nun->roleName,
          ]);

          if ($novice->hasWish) {
            $novice->hasWish = false;
            $this->bga->notify->player($novice->playerId, 'noviceWish', clienttranslate('You drop your secret wish and must pick it up again at ${wishLocation}'), [
              'preserve' => ['hasWish', 'player_id'],
              'hasWish' => $novice->hasWish,
              'player_id' => $novice->playerId,
              'wishLocation' => $novice->wishLocation,
            ]);
          }
          $this->game->saveNovice($novice);
        }

        if ($this->game->getCaught() >= $this->game->getCaughtGoal()) {
          $this->game->saveNuns($nuns);
          // We have a winner!
          $winners = [];
          foreach ($nuns as $nun) {
            $winners[$nun->playerId] = [
              'caughtTimes' => 0,
              'playerName' => $nun->playerName
            ];
          }
          $this->game->winGame($winners, 'caught');
          return EndGameState::class;
        }
      }

      if ($nun->location == $nun->path->destination) {
        $this->game->saveNuns($nuns);
        return NunPathPlayerState::class;
      }
    }
    $this->game->saveNuns($nuns);
    return NunMovePlayerState::class;
  }

  #[PossibleAction]
  public function actConfirm(array $args, int $version, string $confirmAction)
  {
    $this->game->checkVersion($version);
    if (!array_key_exists($confirmAction, $args['actions'])) {
      throw new SystemException("Action $confirmAction is not possible now. Expected: " . json_encode($args['actions']));
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->move->action = $confirmAction;
    $nun->move->active = false;
    $this->bga->playerStats->inc('spaces', count($nun->move->spaces), $nun->playerId);
    $this->bga->playerStats->inc($confirmAction . 'Move', 1, $nun->playerId);
    if ($confirmAction == 'walk') {
      $message = clienttranslate('${roleName} ${player_name} walks');
    } else {
      $message = clienttranslate('${roleName} ${player_name} runs');
    }
    $this->game->saveNun($nun);
    $this->bga->notify->all('nunAction', $message, [
      'i18n' => ['roleName'],
      'preserve' => ['action', 'actionName', 'role'],
      'action' => $nun->move->action,
      'actionName' => $nun->move->actionName,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);
    $this->game->giveExtraTime($nun->playerId);
    return NunChoiceMultiState::class;
  }

  #[PossibleAction]
  public function actUndo(array $args, int $version)
  {
    $this->game->checkVersion($version);
    if (!$args['undo']) {
      throw new SystemException("Action undo is not possible now");
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->location = empty($nun->move->undo) ? $nun->move->start : end($nun->move->undo);
    $nun->move->action = null;
    $nun->move->spaces = $nun->move->undo;
    // $position = array_search($nun->move->undo, $nun->move->spaces);
    // $this->bga->notify->all('message', 'undo position = ' . $position . ' within spaces ' . json_encode($nun->move->spaces));
    // if ($position === false) {
    //   throw new SystemException("Not found undo " . $nun->move->undo . " in spaces " . json_encode($nun->move->spaces));
    // }
    // $nun->move->spaces = array_slice($nun->move->spaces, 0, $position + 1);
    $this->game->saveNun($nun);

    $this->bga->notify->all('nunMove', clienttranslate('${roleName} ${player_name} returns to ${location} (undo)'), [
      'i18n' => ['roleName'],
      'preserve' => ['role'],
      'location' => $nun->location,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);

    return NunMovePlayerState::class;
  }

  function zombie(int $playerId, array $args)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
    // Give control to the other player, if possible
    $nunIds = $this->game->getPlayerIds(1);
    if (!empty($nunIds)) {
      $otherPlayerId = reset($nunIds);
      $this->bga->notify->all('message', "🪦 Zombie $playerId: Change nun owner to other player $otherPlayerId");
      $nun = $this->game->getNunList()->getActiveNun();
      $nun->playerId = $otherPlayerId;
      $nun->playerName = $this->game->getPlayerNameById($nun->playerId);
      $this->bga->notify->all('nunZombie', '', [
        'playerId' => $nun->playerId,
        'playerName' => $nun->playerName,
        'role' => $nun->role,
      ]);
      $this->gamestate->changeActivePlayer($otherPlayerId);
      return NunMovePlayerState::class;
    }

    // Otherwise, zombie makes a move
    $this->bga->notify->all('message', "🪦 Zombie $playerId: TODO move this");
  }
}

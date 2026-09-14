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
  public function actMove(array $args, int $location)
  {
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
          $nun->move->deviate = $this->game->board->getNunDeviate($nun, $novices);
          $nun->move->undo = $nun->move->spaces;
          $this->bga->playerStats->inc('caught', 1, $nun->playerId, true);
          $this->bga->playerStats->inc('caughtTimes', 1, $novice->playerId);

          $this->bga->notify->all('noviceCaught', clienttranslate('${roleName} ${player_name} catches ${player_name2} at ${location}!'), [
            'i18n' => ['roleName'],
            'preserve' => ['caught', 'player_id2', 'role'],
            'caught' => $novice->caught,
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
      }

      if ($nun->location == $nun->pathDestination) {
        $this->game->saveNuns($nuns);
        return NunPathPlayerState::class;
      }
    }
    $this->game->saveNuns($nuns);
    return NunMovePlayerState::class;
  }

  #[PossibleAction]
  public function actConfirm(array $args, string $confirmAction)
  {
    if (!array_key_exists($confirmAction, $args['actions'])) {
      throw new SystemException("Action $confirmAction is not possible now. Expected: " . json_encode($args['actions']));
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->move->action = $confirmAction;
    $nun->move->active = false;
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
    return NunChoiceMultiState::class;
  }

  #[PossibleAction]
  public function actUndo(array $args)
  {
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

  /**
   * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   * You can do whatever you want in order to make sure the turn of this player ends appropriately
   * (ex: play a random card).
   * 
   * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
   *
   * Important: your zombie code will be called when the player leaves the game. This action is triggered
   * from the main site and propagated to the gameserver from a server, not from a browser.
   * As a consequence, there is no current player associated to this action. In your zombieTurn function,
   * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
   * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
   */
  function zombie(int $playerId)
  {
    throw new SystemException($this::class . " zombie function not implemented");
  }
}

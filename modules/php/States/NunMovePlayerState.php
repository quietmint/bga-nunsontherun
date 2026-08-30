<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
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
      description: clienttranslate('${roleIcon} ${roleName} ${player_name} must move'),
      descriptionMyTurn: clienttranslate('${you} (${roleIcon} ${roleName}) must move'),
    );
  }

  public function getArgs(): array
  {
    $nun = $this->game->getNunList()->getCurrentNun();
    $distance = count($nun->move->spaces);
    $actions = $this->game->board->getNunActions();
    $actionsForNow = $this->game->board->getActionsForDistance($actions, $distance);
    foreach ($actions as $action => &$info) {
      $info['disabled'] = !in_array($action, $actionsForNow);
    }
    return [
      'i18n' => ['roleName'],
      'actions' => $actions,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'possible' => $this->game->board->getNunPossibleMoves($nun),
      'role' => $nun->role,
      'roleIcon' => $this->game->getRoleIcon($nun->role),
      'roleName' => $this->game->getRoleName($nun->role),
    ];
  }

  #[PossibleAction]
  public function actMove(int $currentPlayerId, array $args, int $location)
  {
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new UserException("Cannot move to location $location");
    }
    $possible = $args['possible'][$location];
    $spaces = $possible->spaces;
    array_shift($spaces);

    $nuns = $this->game->getNunList();
    $nun = &$nuns->getCurrentNun();
    $novices = $this->game->getNoviceList();
    $locationsWithNovices = $novices->getLocationsWithNovices();
    $oldSpaceId = $nun->location;
    $oldRoomId = $nun->room;
    $oldNovicesVisible = $nuns->getNovicesVisible($novices);
    $this->game->debug("old room $oldRoomId space $oldSpaceId oldNovicesVisible: " . json_encode($oldNovicesVisible) . ' // ');
    foreach ($spaces as $spaceId) {
      $nun->location = $location;
      $nun->room = $this->game->board->getRoomId($nun->location);
      $this->bga->notify->all('nunMove', clienttranslate('${roleIcon} ${roleName} ${player_name} moves to ${location}'), [
        'i18n' => ['roleName'],
        'location' => $spaceId,
        'player_id' => $nun->playerId,
        'player_name' => $nun->playerName,
        'role' => $nun->role,
        'roleIcon' => $this->game->getRoleIcon($nun->role),
        'roleName' => $this->game->getRoleName($nun->role),
      ]);

      if (array_key_exists($spaceId, $locationsWithNovices)) {
        foreach ($locationsWithNovices[$spaceId] as $playerId) {
          $novice = $novices->get($playerId);
          if (!$novice->caught) {
            $novice->caught = true;
            $novice->hasWish = false;
            $this->game->saveNovice($novice);
            $this->bga->playerStats->inc('caught', 1, $nun->playerId, true);
            $this->bga->playerStats->inc('caughtTimes', 1, $novice->playerId);
            $this->bga->notify->all('noviceCaught', clienttranslate('${roleIcon} ${roleName} ${player_name} catches ${player_name2} at ${location}!'), [
              'i18n' => ['roleName'],
              'location' => $spaceId,
              'player_id' => $nun->playerId,
              'player_id2' => $novice->playerId,
              'player_name' => $nun->playerName,
              'player_name2' => $novice->playerName,
              'role' => $nun->role,
              'roleIcon' => $this->game->getRoleIcon($nun->role),
              'roleName' => $this->game->getRoleName($nun->role),
            ]);
          }
        }
      }

      if ($nun->room != $oldRoomId) {
        $novicesVisible = $nuns->getNovicesVisible($novices);
        $this->game->debug("new room {$nun->room} space $spaceId novicesVisible: " . json_encode($novicesVisible) . ' // ');
        foreach ($novicesVisible as $playerId => $visible) {
          $novice = $novices->get($playerId);
          $oldVisible = $oldNovicesVisible[$playerId];
          if ($visible && !$oldVisible) {
            $this->bga->notify->all('noviceMove', clienttranslate('${player_name} is visible at ${location}'), [
              'location' => $novice->location,
              'player_id' => $novice->playerId,
              'player_name' => $novice->playerName,
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
    }
    array_push($nun->move->spaces, ...$spaces);
    $this->game->saveNuns($nuns);

    return NunMovePlayerState::class;
  }

  #[PossibleAction]
  public function actReset()
  {
    $nuns = $this->game->getNunList();
    $nun = $nuns->getCurrentNun();
    $nun->location = $nun->move->start;
    $nun->room = $this->game->board->getRoomId($nun->location);
    $nun->move->action = null;
    $nun->move->spaces = [];
    $this->game->saveNuns($nuns);
    $this->bga->notify->all('nunMove', clienttranslate('${roleIcon} ${roleName} ${player_name} restarts their turn'), [
      'i18n' => ['roleName'],
      'location' => $nun->location,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleIcon' => $this->game->getRoleIcon($nun->role),
      'roleName' => $this->game->getRoleName($nun->role),
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

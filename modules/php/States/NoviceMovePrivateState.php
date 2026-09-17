<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NoviceMovePrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 11,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must confirm your move'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNoviceList()->get($playerId);
    $nuns = $this->game->getNunList();
    $round = $this->game->getRound();
    $possible = $this->game->board->getNovicePossibleMoves($novice, $nuns, $round);
    $distance = count($novice->move->spaces);
    $actions = $this->game->board->getNoviceActions($novice, $round);
    $actionsForNow = $this->game->board->getActionsForDistance($actions, $distance);
    foreach ($actions as $action => &$info) {
      $info['disabled'] = !in_array($action, $actionsForNow);
    }
    // Special case
    if ($novice->caught && $novice->location == $novice->startLocation) {
      $actions['walk']['disabled'] = false;
    }
    return [
      'actions' => $actions,
      'distance' => $distance,
      'possible' => $possible,
      'undo' => $distance > 0,
    ];
  }

  #[PossibleAction]
  public function actMove(int $currentPlayerId, array $args, int $location)
  {
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Cannot move to location $location");
    }

    $possible = $args['possible'][$location];
    $spaces = $possible->spaces;
    array_shift($spaces);

    $nuns = $this->game->getNunList();
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $oldSpaceId = $novice->location;
    $oldVisible = $nuns->isNoviceVisible($novice);
    foreach ($spaces as $spaceId) {
      $novice->location = $spaceId;
      $novice->room = $this->game->board->getRoomId($novice->location);
      $visible = $nuns->isNoviceVisible($novice);
      if ($visible) {
        $this->bga->notify->player($currentPlayerId, 'noviceMove', clienttranslate('You are visible at ${visibleLocation}'), [
          'player_id' => $currentPlayerId,
          'visibleLocation' => $spaceId,
        ]);
      } else if (!$novice->caught && !$visible && $oldVisible) {
        $this->bga->notify->player($currentPlayerId, 'noviceVanish', clienttranslate('You vanish at ${vanishLocation}'), [
          'player_id' => $currentPlayerId,
          'vanishLocation' => $oldSpaceId,
        ]);
        $novice->move->vanishTokens[$oldSpaceId] = $this->game->board->getRoomId($oldSpaceId);
        $this->bga->playerStats->inc('vanishTokens', 1, $novice->playerId, true);
      }
      $this->bga->notify->player($currentPlayerId, 'noviceMove', '', [
        'location' => $spaceId,
        'player_id' => $currentPlayerId,
      ]);
      $oldSpaceId = $spaceId;
      $oldVisible = $visible;
    }
    array_push($novice->move->spaces, ...$spaces);
    $this->game->saveNovice($novice);

    $this->gamestate->nextPrivateState($currentPlayerId, NoviceMovePrivateState::class);
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, array $args, string $confirmAction)
  {
    if (!array_key_exists($confirmAction, $args['actions'])) {
      throw new SystemException("$confirmAction is not possible. Possible actions: " . json_encode(array_keys($args['actions'])));
    }

    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->move->action = $confirmAction;
    $this->bga->playerStats->inc('spaces', count($novice->move->spaces), $novice->playerId);
    $this->bga->playerStats->inc($confirmAction . 'Move', 1, $novice->playerId);
    switch ($confirmAction) {
      case 'stand':
        $message = clienttranslate('You stand still at ${location1}');
        break;
      case 'sneak':
        $message = clienttranslate('You sneak from ${location1} to ${location2}');
        break;
      case 'walk':
        $message = clienttranslate('You walk from ${location1} to ${location2}');
        break;
      case 'run':
        $message = clienttranslate('You run from ${location1} to ${location2}');
        break;
    }
    $this->bga->notify->player($currentPlayerId, 'noviceAction', $message, [
      'preserve' => ['action', 'actionName', 'player_id'],
      'action' => $novice->move->action,
      'actionName' => $novice->move->actionName,
      'location1' => $novice->move->start,
      'location2' => $novice->location,
      'player_id' => $novice->playerId,
    ]);

    if (!$novice->hasKey && $novice->location == $novice->keyLocation) {
      $novice->hasKey = true;
      $this->bga->playerStats->inc('keyObtained', 1, $novice->playerId);
      $this->bga->notify->player($currentPlayerId, 'noviceKey', clienttranslate('You pick up your key at ${keyLocation}'), [
        'preserve' => ['player_id', 'hasKey'],
        'hasKey' => $novice->hasKey,
        'keyLocation' => $novice->keyLocation,
        'player_id' => $novice->playerId,
      ]);
    } else if (!$novice->caught && !$novice->hasWish && $novice->location == $novice->wishLocation) {
      $novice->hasWish = true;
      $this->bga->playerStats->inc('wishObtained', 1, $novice->playerId);
      $this->bga->notify->player($currentPlayerId, 'noviceWish', clienttranslate('You pick up your secret wish at ${wishLocation}'), [
        'preserve' => ['hasWish', 'player_id'],
        'hasWish' => $novice->hasWish,
        'player_id' => $novice->playerId,
        'wishLocation' => $novice->wishLocation,
      ]);
    }
    $this->game->saveNovice($novice);

    if ($novice->caught) {
      if (!$this->game->getNunList()->isNoviceVisible($novice)) {
        $this->gamestate->nextPrivateState($currentPlayerId, NoviceCaughtPrivateState::class);
      } else {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
      }
    } else {
      if ($novice->hasWish && $novice->location == $novice->startLocation) {
        $this->bga->notify->player($currentPlayerId, 'message', clienttranslate('You return to ${startLocation} with your secret wish (caught ${caughtTimes} times)'), [
          'caughtTimes' => $this->bga->playerStats->get('caughtTimes', $novice->playerId),
          'startLocation' => $novice->startLocation,
        ]);
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
      } else {
        $this->gamestate->nextPrivateState($currentPlayerId, NoviceRollPrivateState::class);
      }
    }
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->location = $novice->move->start;
    $novice->move->action = null;
    $novice->move->noiseTokens = [];
    $novice->move->spaces = [];
    $novice->move->vanishTokens = [];
    $novice->room = $this->game->board->getRoomId($novice->location);
    $this->game->saveNovice($novice);

    $noiseTokens = $novice->move->noiseTokens;
    $nuns = $this->game->getNunList();
    foreach ($nuns as $nun) {
      if (array_key_exists($novice->playerId, $nun->noiseTokens)) {
        $noiseTokens[] = $nun->noiseTokens[$novice->playerId];
      }
    }
    $this->bga->notify->player($currentPlayerId, 'noviceUndo', clienttranslate('You return to ${location} (undo)'), [
      'preserve' => ['noiseTokens', 'player_id'],
      'location' => $novice->location,
      'noiseTokens' => $noiseTokens,
      'player_id' => $currentPlayerId,
    ]);

    $this->gamestate->setPlayersMultiactive([$currentPlayerId], '');
    $this->gamestate->initializePrivateState($currentPlayerId);
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

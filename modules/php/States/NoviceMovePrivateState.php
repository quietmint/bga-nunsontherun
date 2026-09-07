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
    $actions = $this->game->board->getNoviceActions($round);
    $actionsForNow = $this->game->board->getActionsForDistance($actions, $distance);
    foreach ($actions as $action => &$info) {
      $info['disabled'] = !in_array($action, $actionsForNow);
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
      if ($visible && !$oldVisible) {
        $this->bga->notify->player($currentPlayerId, 'message', clienttranslate('You are visible at ${location}'), [
          'location' => $spaceId,
          'player_id' => $currentPlayerId,
        ]);
      } else if (!$visible && $oldVisible) {
        $this->bga->notify->player($currentPlayerId, 'noviceVanish', clienttranslate('You vanish at ${vanishLocation}, invisible to the nuns'), [
          'player_id' => $currentPlayerId,
          'vanishLocation' => $oldSpaceId,
        ]);
        $novice->move->vanishTokens[$oldSpaceId] = $this->game->board->getRoomId($oldSpaceId);
      }
      $this->bga->notify->player($currentPlayerId, 'noviceMove', clienttranslate('You move to ${location}'), [
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
    $this->game->saveNovice($novice);
    switch ($confirmAction) {
      case 'stand':
        $message = clienttranslate('You stand still at ${location}');
        break;
      case 'sneak':
        $message = clienttranslate('You sneak from ${startLocation} to ${location}');
        break;
      case 'walk':
        $message = clienttranslate('You walk from ${startLocation} to ${location}');
        break;
      case 'run':
        $message = clienttranslate('You run from ${startLocation} to ${location}');
        break;
    }
    $this->bga->notify->player($currentPlayerId, 'message', $message, [
      'location' => $novice->location,
      'startLocation' => $novice->move->start,
    ]);

    $this->gamestate->nextPrivateState($currentPlayerId, NoviceRollPrivateState::class);
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->location = $novice->move->start;
    $novice->room = $this->game->board->getRoomId($novice->location);
    $novice->move->action = null;
    $novice->move->spaces = [];
    $this->game->saveNovice($novice);
    $this->bga->notify->player($currentPlayerId, 'noviceMove', clienttranslate('You restart your turn'), [
      'location' => $novice->location,
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

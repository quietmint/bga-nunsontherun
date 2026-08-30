<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
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
    $actionsForNow = $this->game->board->getNoviceActionsForDistance($actions, $distance);
    foreach ($actions as $action => &$info) {
      $info['disabled'] = !in_array($action, $actionsForNow);
    }
    return [
      'actions' => $actions,
      'possible' => $possible,
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
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $oldSpaceId = $novice->location;
    $oldVisible = $nuns->isRoomVisible($this->game->board->getRoomId($novice->location));
    foreach ($spaces as $spaceId) {
      $visible = $nuns->isRoomVisible($this->game->board->getRoomId($spaceId));
      if ($visible && !$oldVisible) {
        $this->bga->notify->player($currentPlayerId, 'noviceAppear', clienttranslate('You appear at ${appearLocation}, visible to the nuns'), [
          'appearLocation' => $spaceId,
          'player_id' => $currentPlayerId,
        ]);
      } else if (!$visible && $oldVisible) {
        $this->bga->notify->player($currentPlayerId, 'noviceVanish', clienttranslate('You vanish at ${vanishLocation}, invisible to the nuns'), [
          'player_id' => $currentPlayerId,
          'vanishLocation' => $oldSpaceId,
        ]);
      }
      $this->bga->notify->player($currentPlayerId, 'noviceMove', clienttranslate('You move to ${location}'), [
        'location' => $spaceId,
        'player_id' => $currentPlayerId,
      ]);
      $oldSpaceId = $spaceId;
      $oldVisible = $visible;
    }
    $novice->location = $location;
    array_push($novice->move->spaces, ...$spaces);
    $this->game->saveNovice($novice);

    $this->gamestate->nextPrivateState($currentPlayerId, NoviceMovePrivateState::class);
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, string $confirmAction)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $round = $this->game->getRound();
    $distance = count($novice->move->spaces);
    $actions = $this->game->board->getNoviceActions($round);
    $actionsForNow = $this->game->board->getNoviceActionsForDistance($actions, $distance);
    if (!in_array($confirmAction, $actionsForNow)) {
      throw new UserException("Cannot $confirmAction -- This move is not authorized now. Must be " . json_encode($actionsForNow));
    }

    $novice->move->action = $confirmAction;
    $novice->move->noiseRoll = \bga_rand(1, 6);
    $novice->move->noiseTotal = max(0, $novice->move->noiseRoll + $actions[$novice->move->action]['noise']);
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
      'i18n' => ['action'],
      'action' => $novice->move->action,
      'location' => $novice->location,
      'startLocation' => $novice->move->start,
    ]);
    $this->game->bga->notify->player($currentPlayerId, 'noviceRoll', clienttranslate('You roll ${roll} for noise'), [
      'player_id' => $currentPlayerId,
      'roll' => $novice->move->noiseRoll,
    ]);

    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNoisePrivateState::class);
  }

  #[PossibleAction]
  public function actReset(int $currentPlayerId)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->location = $novice->move->start;
    $novice->move->action = null;
    $novice->move->spaces = [];
    $this->game->saveNovice($novice);
    $this->notify->player($currentPlayerId, 'noviceMove', clienttranslate('You restart your turn'), [
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

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\Actions\CheckAction;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;

class NovicesMove extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 10,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Novices must move'),
      initialPrivate: NoviceMove::class,
    );
  }

  public function onEnteringState(): void
  {
    $novices = $this->game->getNoviceList();
    foreach ($novices as &$novice) {
      // Add previous move to the history
      if ($novice->move != null) {
        array_push($novice->moves, $novice->move);
      }
      // Create a new move
      $move = new Move();
      $move->start = $novice->location;
      $novice->move = $move;
    }
    $this->game->saveNovices($novices);

    $playerIds = $this->game->getObjectListFromDB(
      'SELECT `player_id` FROM `player` WHERE `nun` = 0 AND `player_zombie` = 0 AND `player_eliminated` = 0',
      true
    );
    $this->gamestate->setPlayersMultiactive($playerIds, '');
    $this->gamestate->initializePrivateStateForAllActivePlayers();
  }

  #[CheckAction(false)]
  #[PossibleAction]
  public function actActivate(int $currentPlayerId)
  {
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
    return NextPlayer::class;
  }
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\NunsOnTheRun\Game;

class Nuns extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 20,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Nuns must take their turns'),
      initialPrivate: NunMove::class,
    );
  }

  public function onEnteringState(): void
  {
    $playerIds = $players = $this->game->getObjectListFromDB(
      "SELECT `player_id` FROM `player` WHERE `nun` = 1",
      true
    );
    $this->gamestate->setPlayersMultiactive($playerIds, '');
    $this->gamestate->initializePrivateStateForAllActivePlayers();
  }

  #[PossibleAction]
  public function actReset(int $playerId)
  {
    $novice = $this->game->getNovice($playerId);
    $novice->move = null;
    $novice->location = 1;
    $this->game->saveNovice($novice);
    $this->notify->all("move", clienttranslate('${player_name} restarts their turn, returning to ${location}'), [
      "player_id" => $playerId,
      "player_name" => $novice->playerName, // remove this line if you uncomment notification decorator
      "location" => $novice->location,
    ]);

    $this->gamestate->setPlayersMultiactive([$playerId], '');
    $this->gamestate->initializePrivateState($playerId);
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

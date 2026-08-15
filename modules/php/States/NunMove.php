<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\Game;

class NunMove extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 22,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must move'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $nun = $this->game->getNun('prioress');
    return [
      'possible' => $this->game->board->getNunPossibleMoves($nun)
    ];
  }

  #[PossibleAction]
  public function actMove(int $playerId, array $args, int $location)
  {
    $novice = $this->game->getNovice($playerId);
    $novice->location = $location;
    $this->game->saveNovice($novice);

    $this->bga->notify->all("move", clienttranslate('${player_name} moves to ${location}'), [
      "player_id" => $playerId,
      "player_name" => $novice->playerName,
      "location" => $location
    ]);
    return NunMove::class;
  }

  /**
   * Player action, example content.
   *
   * In this scenario, each time a player pass, this method will be called. This method is called directly
   * by the action trigger on the front side with `bgaPerformAction`.
   */
  #[PossibleAction]
  public function actDone(int $currentPlayerId)
  {
    $this->notify->all("done", clienttranslate('${player_name} is done moving'), [
      "player_id" => $currentPlayerId,
      "player_name" => $this->game->getPlayerNameById($currentPlayerId),
    ]);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NovicesMove::class);
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
    return $this->actDone($playerId);
  }
}

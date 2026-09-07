<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;
use Bga\Games\NunsOnTheRun\NunList;

class NunNoiseMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 35,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Novices must make noise'),
      initialPrivate: NoviceNunNoisePrivateState::class,
    );
  }

  function onEnteringState()
  {
    $nun = $this->game->getNunList()->getActiveNun();
    $oneNuns = new NunList();
    $oneNuns->add($nun);
    $novices = $this->game->getNoviceList();
    $noisyNovices = [];
    foreach ($novices as $novice) {
      $novice->move->noiseTotal = $nun->move->noiseTotal;
      $possible = $this->game->board->getNovicePossibleNoise($novice, $oneNuns);
      if (!empty($possible)) {
        $noisyNovices[] = $novice->playerId;
      }
    }
    if (!empty($noisyNovices)) {
      // Noisy novices add a noise token
      $this->gamestate->setPlayersMultiactive($noisyNovices, '', true);
      $this->gamestate->initializePrivateStateForAllActivePlayers();
    } else {
      // Nobody can be heard, go to the next nun
      return NunNoiseGameState::class;
    }
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

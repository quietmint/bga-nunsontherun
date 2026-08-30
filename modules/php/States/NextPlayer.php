<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NextPlayer extends \Bga\GameFramework\States\GameState
{

  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 90,
      type: StateType::ACTIVE_PLAYER,
      updateGameProgression: true,
    );
  }

  /**
   * Game state action, example content.
   *
   * The onEnteringState method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
   */
  function onEnteringState(int $activePlayerId)
  {
    // $this->game->giveExtraTime($activePlayerId);
    // $this->game->activeNextPlayer();
  }

  function zombie(int $playerId)
  {
    throw new SystemException($this::class . " zombie function not implemented");
  }
}

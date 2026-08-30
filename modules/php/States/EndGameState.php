<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\Games\NunsOnTheRun\Game;

const ST_END_GAME = 99;

class EndGameState extends \Bga\GameFramework\States\GameState
{

  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 98,
      type: StateType::GAME,
    );
  }

  public function onEnteringState()
  {
    return ST_END_GAME;
  }
}

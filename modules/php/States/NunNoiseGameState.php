<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\NunsOnTheRun\Game;

class NunNoiseGameState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 33,
      type: StateType::GAME,
    );
  }

  function onEnteringState(array $args)
  {
    $nuns = $this->game->getNunList();
    foreach ($nuns as &$nun) {
      if ($nun->move->action == 'walk' && is_null($nun->move->noiseRoll)) {
        NunRollPlayerState::nunRoll($this->game, $nun);
        $this->gamestate->changeActivePlayer($nun->playerId);
        return NunRollPlayerState::class;
      }
    }
    return NextRoundGameState::class;
  }
}

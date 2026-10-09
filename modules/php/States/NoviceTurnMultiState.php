<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\NunsOnTheRun\Game;

class NoviceTurnMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 10,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Novices must take their turns'),
      initialPrivate: NoviceMovePrivateState::class,
    );
  }

  public function onEnteringState()
  {
    // Activate all novices
    $this->gamestate->setPlayersMultiactive($this->game->getNoviceList()->getPlayerIds(), '', true);
    $this->gamestate->initializePrivateStateForAllActivePlayers();
  }
}

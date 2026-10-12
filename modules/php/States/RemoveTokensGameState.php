<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\NunsOnTheRun\Game;

class RemoveTokensGameState extends GameState
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

  function onEnteringState()
  {
    // 2c. Remove noise and vanish tokens
    $novices = $this->game->getNoviceList();
    foreach ($novices as &$novice) {
      foreach ($novice->move->tokens as &$token) {
        $token->history = true;
      }
    }
    $this->game->saveNovices($novices);
    $nuns = $this->game->getNunList();
    foreach ($nuns as &$nun) {
      foreach ($nun->move->tokens as &$token) {
        $token->history = true;
      }
    }
    $this->game->saveNuns($nuns);
    $this->bga->notify->all('removeTokens', clienttranslate('Noise and vanish tokens are removed'));
    return NunNoiseGameState::class;
  }
}

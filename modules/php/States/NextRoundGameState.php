<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;

class NextRoundGameState extends \Bga\GameFramework\States\GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 40,
      type: StateType::GAME,
      updateGameProgression: true,
    );
  }

  function onEnteringState()
  {
    $nuns = $this->game->getNunList();
    $round = $this->game->incRound();
    if ($round == 15) {
      $winners = [];
      foreach ($nuns as $nun) {
        $winners[$nun->playerId] = [
          'caughtTimes' => 0,
          'playerName' => $nun->playerName
        ];
      }
      $this->game->winGame($winners);
      return EndGameState::class;
    }

    // Add moves to history
    $novices = $this->game->getNoviceList();
    foreach ($novices as &$novice) {
      $oldMove = $novice->move;
      if ($oldMove != null) {
        $oldMove->undo = null;
        $novice->moves[] = $oldMove;
      }
      $novice->move = new Move(
        caught: $novice->caught,
        start: $novice->location,
      );
    }
    $this->game->saveNovices($novices);

    foreach ($nuns as &$nun) {
      $oldMove = $nun->move;
      if ($oldMove != null) {
        $oldMove->active = false;
        $oldMove->undo = null;
        $nun->moves[] = $oldMove;
      }
      $nun->move = new Move(
        noiseTokens: $oldMove->noiseTokens,
        start: $nun->location,
        undo: [],
      );
    }
    $this->game->saveNuns($nuns);

    // Continue the game
    return NoviceTurnMultiState::class;
  }
}

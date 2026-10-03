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
    foreach ($novices as &$novice) {
      $possible = $this->game->board->getNovicePossibleNoise($novice, $oneNuns);
      if (!empty($possible)) {
        $noisyNovices[] = $novice->playerId;
      }
    }
    if (!empty($noisyNovices)) {
      // Noisy novices add a noise token
      $this->game->saveNovices($novices);
      $this->gamestate->setPlayersMultiactive($noisyNovices, '', true);
      $this->gamestate->initializePrivateStateForAllActivePlayers();
    } else {
      // Nobody can be heard, go to the next nun
      $nun->move->active = false;
      $this->game->saveNun($nun);
      return NunNoiseGameState::class;
    }
  }

  function zombie(int $playerId)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
  }
}

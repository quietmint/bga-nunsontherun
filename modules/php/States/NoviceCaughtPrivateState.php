<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NoviceCaughtPrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 14,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must choose your status'),
    );
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, int $version, bool $caught)
  {
    $this->game->checkVersion($version);
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    if (!$caught) {
      $novice->caught = false;
      $this->game->saveNovice($novice);
      $this->bga->notify->player($currentPlayerId, 'noviceCaught', clienttranslate('You are back on the run'), [
        'preserve' => ['caught', 'player_id'],
        'caught' => $novice->caught,
        'player_id' => $novice->playerId,
      ]);
    }
    $this->game->giveExtraTime($currentPlayerId);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
  }

  function zombie(int $playerId)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
  }
}

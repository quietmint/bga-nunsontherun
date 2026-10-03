<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NoviceOwnNoisePrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 13,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must make noise'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNoviceList()->get($playerId);
    $nuns = $this->game->getNunList();
    $possible = $this->game->board->getNovicePossibleNoise($novice, $nuns);
    $action = $novice->move->action;
    $info = $this->game->board->getNoviceActions($novice, 0)[$action];
    return [
      'i18n' => ['action'],
      'action' => $info['name'],
      'possible' => $possible,
      'roll' => $novice->move->noiseRoll,
      'undo' => !empty($novice->move->noiseTokens),
    ];
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $version, int $location)
  {
    $this->game->checkVersion($version);
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Cannot make noise at location $location");
    }

    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->move->noiseTokens[] = $location;
    $this->game->saveNovice($novice);

    $this->bga->notify->player($currentPlayerId, 'noviceNoise', clienttranslate('You make noise at ${noiseLocation}'), [
      'noiseLocation' => $location,
      'player_id' => $currentPlayerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    if (!empty($args['possible'])) {
      throw new SystemException("You must make more noise.");
    }
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $count = count($novice->move->noiseTokens);
    if ($count > 0) {
      $this->bga->playerStats->inc('noiseTokens', $count, $novice->playerId, true);
    }
    $this->game->giveExtraTime($currentPlayerId);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId, int $version)
  {
    $this->game->checkVersion($version);
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->move->noiseTokens = [];
    $this->game->saveNovice($novice);
    unset($novice->move->noiseTokens['___bga_associative_array_flag']);

    $noiseTokens = $novice->move->noiseTokens;
    $nuns = $this->game->getNunList();
    foreach ($nuns as $nun) {
      if (array_key_exists($novice->playerId, $nun->move->noiseTokens)) {
        $noiseTokens[] = $nun->move->noiseTokens[$novice->playerId];
      }
    }
    $this->bga->notify->player($currentPlayerId, 'noviceNoiseUndo', clienttranslate('You undo'), [
      'preserve' => ['noiseTokens', 'player_id'],
      'noiseTokens' => $noiseTokens,
      'player_id' => $novice->playerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
  }

  function zombie(int $playerId)
  {
   $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
  }
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\NunList;
use Random\Randomizer;

class NoviceNunNoisePrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 36,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must make noise'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNoviceList()->get($playerId);
    $nun = $this->game->getNunList()->getActiveNun();
    $oneNuns = new NunList();
    $oneNuns->add($nun);
    $possible = $this->game->board->getNovicePossibleNoise($novice, $oneNuns);
    return [
      'possible' => $possible,
      'undo' => array_key_exists($novice->playerId, $nun->move->noiseTokens),
    ];
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    if (!empty($args['possible'])) {
      throw new SystemException("You must make more noise.");
    }
    $nun = $this->game->getNunList()->getActiveNun();
    if (array_key_exists($currentPlayerId, $nun->move->noiseTokens)) {
      $this->bga->playerStats->inc('noiseTokens', 1, $currentPlayerId, true);
    }
    $this->game->giveExtraTime($currentPlayerId);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NunRecapGameState::class);
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $version, int $location)
  {
    $this->game->checkVersion($version);
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Cannot make noise at location $location");
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->move->noiseTokens[$currentPlayerId] = $location;
    $this->game->saveNun($nun);

    $this->bga->notify->player($currentPlayerId, 'noviceNoise', clienttranslate('You make noise at ${noiseLocation}'), [
      'noiseLocation' => $location,
      'player_id' => $currentPlayerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNunNoisePrivateState::class);
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId, int $version)
  {
    $this->game->checkVersion($version);
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $nuns = $this->game->getNunList();
    $nun = $nuns->getActiveNun();
    unset($nun->move->noiseTokens[$currentPlayerId]);
    $this->game->saveNun($nun);
    unset($nun->move->noiseTokens['___bga_associative_array_flag']);

    $noiseTokens = $novice->move->noiseTokens;
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
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNunNoisePrivateState::class);
  }

  public function zombie(int $playerId, array $args)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . get_class($this));
    $version = $this->bga->tableOptions->getGameVersion();
    if (!empty($args['possible'])) {
      $r = new Randomizer();
      $location = $r->pickArrayKeys($args['possible'], 1)[0];
      $this->bga->notify->all('message', "🪦 Zombie $playerId -- random noise $location");
      return $this->actNoise($playerId, $args, $version, $location);
    } else {
      $this->bga->notify->all('message', "🪦 Zombie $playerId -- confirm");
      return $this->actConfirm($playerId, $args, $version);
    }
  }
}

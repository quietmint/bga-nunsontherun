<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\NunList;
use Bga\Games\NunsOnTheRun\Token;

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
      'undo' => !empty($nun->move->getNoiseTokens($novice->playerId)),
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
    $count = count($nun->move->getNoiseTokens($currentPlayerId));
    if ($count > 0) {
      $this->bga->playerStats->inc('noiseTokens', $count, $currentPlayerId, true);
    }
    $this->game->giveExtraTime($currentPlayerId);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NunRecapGameState::class);
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $version, int $location)
  {
    $this->game->checkVersion($version);
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Novice $currentPlayerId cannot make noise at location $location");
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->move->tokens[] = new Token(
      location: $location,
      playerId: $currentPlayerId,
      room: $this->game->board->getRoomId($location),
      type: 'noise',
    );
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
    $nun->move->tokens = array_filter($nun->move->tokens, fn($token) => !(!$token->history && $token->type == 'noise' && $token->playerId == $currentPlayerId));
    $this->game->saveNun($nun);

    $noiseTokens = $novice->move->getNoiseTokens();
    foreach ($nuns as $nun) {
      $noiseTokens = array_merge($noiseTokens, $nun->move->getNoiseTokens($novice->playerId));
    }
    $noiseTokens = array_map(fn($token) => $token->location, $noiseTokens);
    $this->bga->notify->player($currentPlayerId, 'noviceNoiseUndo', clienttranslate('You undo'), [
      'preserve' => ['noiseTokens', 'player_id'],
      'noiseTokens' => $noiseTokens,
      'player_id' => $novice->playerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNunNoisePrivateState::class);
  }

  public function zombie(int $playerId)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . get_class($this));
    $this->game->zombieNovice($playerId);
    $this->gamestate->setPlayerNonMultiactive($playerId, NoviceRecapGameState::class);
  }
}

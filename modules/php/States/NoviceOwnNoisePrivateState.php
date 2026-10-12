<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Token;

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
      'undo' => !empty($novice->move->getNoiseTokens()),
    ];
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    if (!empty($args['possible'])) {
      throw new SystemException("You must make more noise.");
    }
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $count = count($novice->move->getNoiseTokens());
    if ($count > 0) {
      $this->bga->playerStats->inc('noiseTokens', $count, $novice->playerId, true);
    }
    $this->game->giveExtraTime($currentPlayerId);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $version, int $location)
  {
    $this->game->checkVersion($version);
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("$novice cannot make noise at location $location");
    }

    $novice->move->tokens[] = new Token(
      location: $location,
      playerId: $novice->playerId,
      room: $this->game->board->getRoomId($location),
      type: 'noise',
    );
    $this->game->saveNovice($novice);

    $this->bga->notify->player($currentPlayerId, 'noviceNoise', clienttranslate('You make noise at ${noiseLocation}'), [
      'noiseLocation' => $location,
      'player_id' => $currentPlayerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId, int $version)
  {
    $this->game->checkVersion($version);
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->move->tokens = array_filter($novice->move->tokens, fn($token) => !(!$token->history && $token->type == 'noise'));
    $this->game->saveNovice($novice);

    $noiseTokens = $novice->move->getNoiseTokens();
    $nuns = $this->game->getNunList();
    foreach ($nuns as $nun) {
      $noiseTokens = array_merge($noiseTokens, $nun->move->getNoiseTokens($novice->playerId));
    }
    $noiseTokens = array_map(fn($token) => $token->location, $noiseTokens);
    $this->bga->notify->player($currentPlayerId, 'noviceNoiseUndo', clienttranslate('You undo'), [
      'preserve' => ['noiseTokens', 'player_id'],
      'noiseTokens' => $noiseTokens,
      'player_id' => $novice->playerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
  }

  public function zombie(int $playerId)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . get_class($this));
    $this->game->zombieNovice($playerId);
    $this->gamestate->setPlayerNonMultiactive($playerId, NoviceRecapGameState::class);
  }
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Novice;

class NoviceRollPrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 12,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} roll ${roll} and make noise ${noiseTotal} spaces away'),
    );
  }

  public static function noviceRoll(Game $game, Novice $novice)
  {
    $actions = $game->board->getNoviceActions($novice, $game->getRound());
    $novice->move->noiseRoll = \bga_rand(1, 6);
    $novice->move->noiseTotal = max(0, $novice->move->noiseRoll + $actions[$novice->move->action]['noise']);
    $game->saveNovice($novice);

    $game->bga->notify->player($novice->playerId, 'noviceRoll', clienttranslate('You roll ${roll} and make noise ${noiseTotal} spaces away'), [
      'noiseTotal' => $novice->move->noiseTotal,
      'player_id' => $novice->playerId,
      'roll' => $novice->move->noiseRoll,
    ]);
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNoviceList()->get($playerId);
    $nuns = $this->game->getNunList();
    $possible = $this->game->board->getNovicePossibleNoise($novice, $nuns);
    $info = $this->game->board->getNoviceActions($novice, 0)[$novice->move->action];
    return [
      'i18n' => ['action'],
      'action' => $info['name'],
      'blessing' => $novice->blessing,
      'heard' => !empty($possible),
      'noiseTotal' => $novice->move->noiseTotal,
      'possible' => $possible,
      'roll' => $novice->move->noiseRoll,
      'rollAnimate' => true,
    ];
  }

  public function onEnteringState(int $currentPlayerId)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    if (is_null($novice->move->noiseRoll)) {
      self::noviceRoll($this->game, $novice);
    }
  }

  #[PossibleAction]
  public function actBlessingAdjust(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    if ($args['blessing'] != Game::BLESSING_ADJUST) {
      throw new SystemException("Unexpected blessing: " . $args['blessing']);
    }
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->blessing = null;
    $novice->move->blessing = Game::BLESSING_ADJUST;
    $novice->move->noiseTotal = max(0, $novice->move->noiseTotal - 1);
    $this->game->saveNovice($novice);
    $this->bga->notify->player($currentPlayerId, 'message', clienttranslate('You use a blessing to make less noise'));
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceRollPrivateState::class);
  }

  #[PossibleAction]
  public function actBlessingReroll(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    if ($args['blessing'] != Game::BLESSING_REROLL) {
      throw new SystemException("Unexpected blessing: " . $args['blessing']);
    }
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $novice->blessing = null;
    $novice->move->blessing = Game::BLESSING_REROLL;
    $this->bga->notify->player($currentPlayerId, 'message', clienttranslate('You use a blessing to reroll'));
    self::noviceRoll($this->game, $novice);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceRollPrivateState::class);
  }

  #[PossibleAction]
  public function actConfirm(int $currentPlayerId, array $args, int $version)
  {
    $this->game->checkVersion($version);
    $this->game->giveExtraTime($currentPlayerId);
    if (empty($args['possible'])) {
      $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
    } else {
      $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
    }
  }

  function zombie(int $playerId)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
  }
}

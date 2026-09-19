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
    $this->game->debug("NoviceRollPrivateState loaded this novice: " . json_encode($novice) . ' // ');

    if (is_null($novice->move->noiseRoll)) {
      self::noviceRoll($this->game, $novice);
    }
  }

  #[PossibleAction]
  public function actBlessingAdjust(int $currentPlayerId, array $args)
  {
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
  public function actBlessingReroll(int $currentPlayerId, array $args)
  {
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
  public function actConfirm(int $currentPlayerId, array $args)
  {
    if (empty($args['possible'])) {
      $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NoviceRecapGameState::class);
    } else {
      $this->gamestate->nextPrivateState($currentPlayerId, NoviceOwnNoisePrivateState::class);
    }
  }

  /**
   * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   * You can do whatever you want in order to make sure the turn of this player ends appropriately
   * (ex: play a random card).
   * 
   * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
   *
   * Important: your zombie code will be called when the player leaves the game. This action is triggered
   * from the main site and propagated to the gameserver from a server, not from a browser.
   * As a consequence, there is no current player associated to this action. In your zombieTurn function,
   * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
   * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
   */
  function zombie(int $playerId)
  {
    throw new SystemException($this::class . " zombie function not implemented");
  }
}

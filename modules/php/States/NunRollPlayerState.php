<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Nun;
use Bga\Games\NunsOnTheRun\NunList;

class NunRollPlayerState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 34,
      type: StateType::ACTIVE_PLAYER,
      description: clienttranslate('${roleName} ${player_name} rolls ${roll} and hears noise ${noiseTotal} spaces away'),
      descriptionMyTurn: clienttranslate('${you} (${roleName}) roll ${roll} and hear noise ${noiseTotal} spaces away'),
    );
  }

  public static function nunRoll(Game $game, Nun $nun)
  {
    $nun->move->active = true;
    $nun->move->noiseRoll = \bga_rand(1, 6);
    $nun->move->noiseTotal = $nun->move->noiseRoll;
    $game->saveNun($nun);

    $game->bga->notify->all('nunRoll', clienttranslate('${roleName} ${player_name} rolls ${roll} and hears noise ${noiseTotal} spaces away'), [
      'i18n' => ['roleName'],
      'noiseTotal' => $nun->move->noiseTotal,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
      'roll' => $nun->move->noiseRoll,
    ]);
  }

  public function getArgs(): array
  {
    $nun = $this->game->getNunList()->getActiveNun();
    return [
      'i18n' => ['roleName'],
      'noiseTotal' => $nun->move->noiseTotal,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
      'roll' => $nun->move->noiseRoll,
      'rollAnimate' => true,
    ];
  }

  #[PossibleAction]
  public function actBlessingAdjust(array $args)
  {
    if ($args['blessing'] != Game::BLESSING_ADJUST) {
      throw new SystemException("Unexpected blessing: " . $args['blessing']);
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->blessing = null;
    $nun->move->blessing = Game::BLESSING_ADJUST;
    $nun->move->noiseTotal++;
    $this->game->saveNun($nun);

    $this->bga->notify->all('blessing', clienttranslate('${roleName} ${player_name} uses a blessing to hear more noise'), [
      'i18n' => ['roleName'],
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);
    return NunRollPlayerState::class;
  }

  #[PossibleAction]
  public function actBlessingReroll(array $args)
  {
    if ($args['blessing'] != Game::BLESSING_REROLL) {
      throw new SystemException("Unexpected blessing: " . $args['blessing']);
    }

    $nun = $this->game->getNunList()->getActiveNun();
    $nun->blessing = null;
    $nun->move->blessing = Game::BLESSING_REROLL;
    $this->bga->notify->all('blessing', clienttranslate('${roleName} ${player_name} uses a blessing to reroll'), [
      'i18n' => ['roleName'],
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);
    self::nunRoll($this->game, $nun);
    return NunRollPlayerState::class;
  }

  #[PossibleAction]
  public function actConfirm()
  {
    return NunNoiseMultiState::class;
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

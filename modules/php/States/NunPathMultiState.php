<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\Game;

class NunPathMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 31,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Nuns must choose a path for ${icon} ${role}'),
      descriptionMyTurn: clienttranslate('${you} must choose a path for ${icon} ${role}'),
    );
  }

  public function getArgs(): array
  {
    $nun = $this->game->getNunList()->getCurrentNun();
    return [
      'i18n' => ['role'],
      'icon' => $this->game->getRoleIcon($nun->role),
      'paths' => [],
      'role' => $this->game->getRoleName($nun->role),
    ];
  }

  #[PossibleAction]
  public function actPath(int $playerId, array $args, int $path)
  {
    $nun = $this->game->getNunList()->getCurrentNun();
    $nun->path = [];
    $this->game->saveNun($nun);

    $this->bga->notify->all("move", clienttranslate('${player_name} choose a path for ${role}'), [
      'path' => $nun->path,
      'player_id' => $playerId,
      'player_name' => $this->game->getPlayerNameById($playerId),
      'role' => $this->game->getRoleName($nun->role),
    ]);
    return NunMoveMultiState::class;
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

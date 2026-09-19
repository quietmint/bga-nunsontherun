<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class NunPathPlayerState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 31,
      type: StateType::ACTIVE_PLAYER,
      description: clienttranslate('${roleName} ${player_name} must choose a path'),
      descriptionMyTurn: clienttranslate('${you} (${roleName}) must choose a path'),
    );
  }

  public function getArgs(): array
  {
    $nuns = $this->game->getNunList();
    $nun = $nuns->getActiveNun();
    $possible = $this->game->board->getNunPossiblePaths($nuns, $nun);
    return [
      'i18n' => ['roleName'],
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'possible' => $possible,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
      'start' => $nun->location,
    ];
  }

  #[PossibleAction]
  public function actPath(int $currentPlayerId, array $args, string $path)
  {
    if (!array_key_exists($path, $args['possible'])) {
      throw new SystemException("Path $path is not possible");
    }
    $nun = $this->game->getNunList()->getActiveNun();
    $nun->path = $args['possible'][$path];
    $nun->paths[] = $path;
    $this->game->saveNun($nun);

    $this->bga->notify->all('nunPath', clienttranslate('${roleName} ${player_name} chooses path ${pathName}'), [
      'i18n' => ['roleName'],
      'preserve' => ['path', 'role'],
      'path' => $nun->path,
      'pathName' => $nun->path,
      'player_id' => $currentPlayerId,
      'player_name' => $this->game->getPlayerNameById($currentPlayerId),
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);
    return NunMovePlayerState::class;
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

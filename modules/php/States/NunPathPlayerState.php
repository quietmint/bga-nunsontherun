<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
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
      description: clienttranslate('${roleIcon} ${roleName} ${player_name} must choose a path'),
      descriptionMyTurn: clienttranslate('${you} (${roleIcon} ${roleName}) must choose a path'),
    );
  }

  public function getArgs(): array
  {
    $nuns = $this->game->getNunList();
    $nun = $nuns->getCurrentNun();
    $possible = $this->game->board->getNunPossiblePaths($nuns, $nun);
    return [
      'i18n' => ['roleName'],
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'possible' => $possible,
      'role' => $nun->role,
      'roleIcon' => $this->game->getRoleIcon($nun->role),
      'roleName' => $this->game->getRoleName($nun->role),
      'start' => $nun->location,
    ];
  }

  #[PossibleAction]
  public function actPath(int $currentPlayerId, array $args, string $path)
  {
    if (!array_key_exists($path, $args['possible'])) {
      throw new SystemException("Path $path is not possible");
    }
    $nuns = $this->game->getNunList();
    $nun = $nuns->getCurrentNun();
    $color = $args['possible'][$path]['color'];
    $spaces = $args['possible'][$path]['path'];
    $destination = $spaces[0];
    if ($destination == $nun->location) {
      $destination = end($spaces);
    }
    $nun->path = $path;
    $this->game->saveNuns($nuns);

    $this->bga->notify->all('nunPath', clienttranslate('${player_name} (${roleIcon} ${roleName}) chooses path ${pathLocation}'), [
      'i18n' => ['roleName'],
      'preserve' => ['path', 'pathColor', 'pathStart'],
      'path' => $nun->path,
      'pathColor' => $color,
      'pathLocation' => $destination,
      'pathStart' => $nun->location,
      'player_id' => $currentPlayerId,
      'player_name' => $this->game->getPlayerNameById($currentPlayerId),
      'role' => $nun->role,
      'roleIcon' => $this->game->getRoleIcon($nun->role),
      'roleName' => $this->game->getRoleName($nun->role),
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

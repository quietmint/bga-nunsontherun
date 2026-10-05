<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Random\Randomizer;

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
  public function actPath(array $args, int $version, string $path)
  {
    $this->game->checkVersion($version);
    if (!array_key_exists($path, $args['possible'])) {
      throw new SystemException("Path $path is not possible");
    }
    $nun = $this->game->getNunList()->getActiveNun();
    $nun->path = $args['possible'][$path];
    $nun->paths[] = $path;
    $this->game->saveNun($nun);

    $this->game->giveExtraTime($nun->playerId);
    $this->bga->notify->all('nunPath', clienttranslate('${roleName} ${player_name} chooses path ${pathName}'), [
      'i18n' => ['roleName'],
      'preserve' => ['path', 'role'],
      'path' => $nun->path,
      'pathName' => $nun->path,
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);
    return NunMovePlayerState::class;
  }

  public function zombie(int $playerId, array $args)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . get_class($this));
    // Reassign nun if possible
    $otherPlayerId = $this->game->zombieReassignNuns();
    if ($otherPlayerId) {
      $this->gamestate->changeActivePlayer($otherPlayerId);
      return NunPathPlayerState::class;
    }

    // Otherwise, random path
    $version = $this->bga->tableOptions->getGameVersion();
    $r = new Randomizer();
    $path = $r->pickArrayKeys($args['possible'], 1)[0];
    $this->bga->notify->all('message', "🪦 Zombie $playerId -- random path $path");
    return $this->actPath($args, $version, $path);
  }
}

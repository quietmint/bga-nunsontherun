<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\NunsOnTheRun\Game;
use Random\Randomizer;

class RolesMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 41,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('${nunCount} players must play as nuns'),
      descriptionMyTurn: clienttranslate('${nunCount} players must play as nuns'),
    );
  }

  public function getArgs(): array
  {
    $players = $this->bga->globals->get('players');
    $roles = $this->bga->globals->get('roles');
    $nunCount = count($players) == 8 ? 2 : 1;
    return [
      'nunCount' => $nunCount,
      'roles' => $roles ?? [],
    ];
  }

  function onEnteringState()
  {
    $option = $this->bga->tableOptions->get(Game::OPTION_ROLES);
    $roles = [];
    $players = $this->bga->globals->get('players');
    $nunCount = count($players) == 8 ? 2 : 1;
    if ($option == Game::OPTION_ROLES__RANDOM) {
      $r = new Randomizer();
      $nunIds = $r->pickArrayKeys($players, $nunCount);
      foreach ($players as $playerId => $player) {
        $roles[$playerId] = in_array($playerId, $nunIds) ? 'nun' : 'novice';
      }
      $this->bga->globals->set('roles', $roles);
      return RolesGameState::class;
    } else if ($option == Game::OPTION_ROLES__ADMIN) {
      foreach ($players as $playerId => $player) {
        $roles[$playerId] = $player['player_is_admin'] ? 'nun' : 'novice';
      }
      $this->bga->globals->set('roles', $roles);
      if ($nunCount == 1) {
        return RolesGameState::class;
      }
    }

    // Ask non-nun players to volunteer
    $playerIds = $this->game->getPlayerIds(0);
    $nunIds = array_keys($roles, 'nun');
    if (!empty($nuns)) {
      $playerIds = array_diff($playerIds, $nunIds);
    }
    $this->gamestate->setPlayersMultiactive($playerIds, '', true);
  }

  #[PossibleAction]
  public function actChoose(int $currentPlayerId, array $args, int $version, string $role)
  {
    $this->game->checkVersion($version);
    $roles = $args['roles'];
    $roles[$currentPlayerId] = $role;
    $this->bga->globals->set('roles', $roles);
    $this->bga->notify->all('message', clienttranslate('${player_name} chooses ${roleName}'), [
      'i18n' => ['roleName'],
      'player_id' => $currentPlayerId,
      'player_name' => $this->game->getPlayerNameById($currentPlayerId),
      'roleName' => $role == 'nun' ? clienttranslate('Nun') : clienttranslate('Novice'),
    ]);

    // Stop if we have enough nuns
    $nunIds = array_keys($roles, 'nun');
    if (count($nunIds) >= $args['nunCount']) {
      $roles = [];
      if (count($nunIds) > $args['nunCount']) {
        $nunIds = array_slice($nunIds, 0, $args['nunCount']);
      }
      $playerIds = array_keys($this->bga->globals->get('players'));
      foreach ($playerIds as $playerId) {
        $roles[$playerId] = in_array($playerId, $nunIds) ? 'nun' : 'novice';
      }
      $this->bga->globals->set('roles', $roles);
      $this->gamestate->setAllPlayersNonMultiactive(RolesGameState::class);
      return;
    }

    // Ask for more volunteers if not enough
    $activeCount = count($this->gamestate->getActivePlayerList());
    if ($activeCount == 1) {
      $this->bga->notify->all('message', clienttranslate('${nunCount} players must play as nuns'), [
        'nunCount' => $args['nunCount'],
      ]);
      $playerIds = $this->game->getPlayerIds(0);
      $nunIds = array_keys($roles, 'nun');
      if (!empty($nuns)) {
        $playerIds = array_diff($playerIds, $nunIds);
      }
      $this->gamestate->setPlayersMultiactive($playerIds, '', true);
      return;
    }

    // Wait for others to choose
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, '');
  }

  public function zombie(int $playerId)
  {
    return $this->actChoose($playerId, $this->getArgs(), Game::ZOMBIE_VERSION, 'novice');
  }
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;

class NunChoiceMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 30,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Nuns must choose which nun to activate'),
      descriptionMyTurn: clienttranslate('${you} must choose which nun to activate'),
    );
  }

  public function getArgs(): array
  {
    $nuns = $this->game->getNunList();
    $choices = $nuns->getChoices();
    return [
      '_no_notify' => count($choices) < 2,
      'choices' => $choices,
    ];
  }

  function onEnteringState(array $args)
  {
    if ($args['_no_notify']) {
      $role = reset($args['choices']);
      $this->setCurrentNun($role);
      return;
    }

    // Activate all nuns
    $this->gamestate->setPlayersMultiactive($this->game->getPlayerIds(1), '', true);
  }

  #[PossibleAction]
  public function actChoose(int $currentPlayerId, string $role)
  {
    $this->bga->notify->all('message', clienttranslate('${roleIcon} ${roleName} ${player_name} activates'), [
      'i18n' => ['roleName'],
      'player_id' => $currentPlayerId,
      'player_name' => $this->game->getPlayerNameById($currentPlayerId),
      'role' => $role,
      'roleIcon' => $this->game->getRoleIcon($role),
      'roleName' => $this->game->getRoleName($role),
    ]);

    $this->setCurrentNun($role);
  }

  private function setCurrentNun(string $role)
  {
    $nuns = $this->game->getNunList();
    $nun = $nuns->get($role);
    $nun->move = new Move();
    $nun->move->current = true;
    $nun->move->start = $nun->location;
    $this->game->saveNuns($nuns);
    $this->gamestate->changeActivePlayer($nun->playerId);
    $this->gamestate->setAllPlayersNonMultiactive(NunPathPlayerState::class);
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

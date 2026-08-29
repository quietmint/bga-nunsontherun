<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\Game;

class NoviceNoise extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 12,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must place noise tokens'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNovice($playerId);
    $nuns = $this->game->getNunList();
    $possible = $this->game->board->getNovicePossibleNoise($novice, $nuns);
    $action = $novice->move->action;
    $info = $this->game->board->getNoviceActions(0)[$action];
    return [
      'i18n' => ['action'],
      'action' => $info['name'],
      'formula' => ($info['noise'] > 0 ? "+ " : "- ") . abs($info['noise']),
      'noise' => $novice->move->noiseTotal,
      'possible' => $possible,
      'roll' => $novice->move->noiseRoll,
    ];
  }

  #[PossibleAction]
  public function actBack(int $currentPlayerId)
  {
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceMove::class);
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $location)
  {
    $this->game->bga->notify->player($currentPlayerId, 'noviceNoise', clienttranslate('You place a noise token at ${noiseLocation}'), [
      'playerId' => $currentPlayerId,
      'noiseLocation' => $location,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNoise::class);
  }

  #[PossibleAction]
  public function actReset(int $currentPlayerId)
  {
    $novice = $this->game->getNovice($currentPlayerId);
    $novice->location = $novice->move->start;
    $novice->move->spaces = [];
    $this->game->saveNovice($novice);
    $this->notify->player($currentPlayerId, "noviceMove", clienttranslate('${player_name} restarts their turn'), [
      "player_id" => $currentPlayerId,
      "player_name" => $novice->playerName,
      "location" => $novice->location,
    ]);

    $this->gamestate->setPlayersMultiactive([$currentPlayerId], '');
    $this->gamestate->initializePrivateState($currentPlayerId);
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
    return $this->actDone($playerId, $this->getArgs($playerId), 'stand');
  }
}

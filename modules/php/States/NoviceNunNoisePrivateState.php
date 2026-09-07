<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\NunList;

class NoviceNunNoisePrivateState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 36,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must make noise'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNoviceList()->get($playerId);
    $nun = $this->game->getNunList()->getActiveNun();
    $oneNuns = new NunList();
    $oneNuns->add($nun);
    $possible = $this->game->board->getNovicePossibleNoise($novice, $oneNuns);
    return [
      'noise' => $novice->move->noiseTotal,
      'possible' => $possible,
      'undo' => !empty($novice->move->noiseTokens),
    ];
  }

  #[PossibleAction]
  public function actContinue(int $currentPlayerId, array $args)
  {
    if (!empty($args['possible'])) {
      throw new SystemException("Not done yet. You must make more noise.");
    }
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NunRecapGameState::class);
  }

  #[PossibleAction]
  public function actNoise(int $currentPlayerId, array $args, int $location)
  {
    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new SystemException("Cannot make noise at location $location");
    }

    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $nun = $this->game->getNunList()->getActiveNun();
    $novice->move->noiseTokens[$location] = $nun->role;
    $this->game->saveNovice($novice);

    $this->bga->notify->player($currentPlayerId, 'noviceNoise', clienttranslate('You make noise at ${noiseLocation}'), [
      'noiseLocation' => $location,
      'player_id' => $currentPlayerId,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNunNoisePrivateState::class);
  }

  #[PossibleAction]
  public function actUndo(int $currentPlayerId)
  {
    $novice = $this->game->getNoviceList()->get($currentPlayerId);
    $nun = $this->game->getNunList()->getActiveNun();
    $novice->move->noiseTokens = array_diff($novice->move->noiseTokens, [$nun->role]);
    $this->game->saveNovice($novice);
    unset($novice->move->noiseTokens['___bga_associative_array_flag']);
    $this->bga->notify->player($currentPlayerId, 'noviceNoiseUndo', clienttranslate('You undo'), [
      'player_id' => $novice->playerId,
      'noiseTokens' => $novice->move->noiseTokens,
    ]);
    $this->gamestate->nextPrivateState($currentPlayerId, NoviceNunNoisePrivateState::class);
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

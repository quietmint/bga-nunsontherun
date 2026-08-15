<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\Game;

class NoviceMove extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 11,
      type: StateType::PRIVATE,
      descriptionMyTurn: clienttranslate('${you} must move'),
    );
  }

  public function getArgs(int $playerId): array
  {
    $novice = $this->game->getNovice($playerId);
    return [
      'possible' => $this->game->board->getNovicePossibleMoves($novice)
    ];
  }

  #[PossibleAction]
  public function actMove(int $currentPlayerId, array $args, int $location)
  {

    // Check location
    if (!array_key_exists($location, $args['possible'])) {
      throw new UserException("Cannot move to location $location");
    }
    $possible = $args['possible'][$location];
    $spaces = $possible->spaces;
    array_shift($spaces);

    $roomIds = $this->game->getNunRoomIds();
    $novice = $this->game->getNovice($currentPlayerId);
    $roomId = $this->game->board->getRoomId($novice->location);
    $oldVisible = array_key_exists($roomId, $roomIds);
    foreach ($spaces as $spaceId) {
      $roomId = $this->game->board->getRoomId($spaceId);
      $newVisible = array_key_exists($roomId, $roomIds);
      if ($newVisible) {
        // visible
        $this->bga->notify->all("noviceMove", clienttranslate('${player_name} moves to ${location} in view of the nuns'), [
          "player_id" => $currentPlayerId,
          "player_name" => $novice->playerName,
          "location" => $spaceId
        ]);
      } else {
        // hidden
        if ($oldVisible) {
          $this->bga->notify->all("vanish", clienttranslate('${player_name} vanishes from ${location}'), [
            "player_id" => $currentPlayerId,
            "player_name" => $novice->playerName,
            "location" => $novice->location
          ]);
        }
        $this->bga->notify->player($currentPlayerId, "noviceMove", clienttranslate('${player_name} moves secretly to ${location}'), [
          "player_id" => $currentPlayerId,
          "player_name" => $novice->playerName,
          "location" => $spaceId
        ]);
      }
      $oldVisible = $newVisible;
      $novice->location = $location;
    }
    array_push($novice->move->spaces, ...$spaces);
    $this->game->saveNovice($novice);

    $this->gamestate->nextPrivateState($currentPlayerId, NoviceMove::class);
  }

  #[PossibleAction]
  public function actDone(int $currentPlayerId)
  {
    $this->notify->all("done", clienttranslate('${player_name} is done moving'), [
      "player_id" => $currentPlayerId,
      "player_name" => $this->game->getPlayerNameById($currentPlayerId),
    ]);
    $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NunsMove::class);
  }

  #[PossibleAction]
  public function actReset(int $currentPlayerId)
  {
    $novice = $this->game->getNovice($currentPlayerId);
    $novice->location = $novice->move->start;
    $novice->move->spaces = [];
    $this->game->saveNovice($novice);
    $this->notify->all("noviceMove", clienttranslate('${player_name} restarts their turn'), [
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
    return $this->actDone($playerId);
  }
}

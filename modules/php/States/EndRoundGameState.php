<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;

class EndRoundGameState extends \Bga\GameFramework\States\GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 40,
      type: StateType::GAME,
      updateGameProgression: true,
    );
  }

  function onEnteringState()
  {
    $nuns = $this->game->getNunList();
    $round = $this->game->incRound();
    if ($round == 15) {
      $this->game->DbQuery('UPDATE `player` SET `player_score` = 1 WHERE `nun` = 1 AND `player_zombie` = 0 AND `player_eliminated` = 0');
      $abbess = $nuns->get('abbess');
      $message = clienttranslate('${player_name} wins!');
      $args = [
        'player_name' => $abbess->playerName,
        'player_id' => $abbess->playerId,
      ];
      if ($this->game->getPlayersNumber() == 8) {
        $prioress = $nuns->get('prioress');
        $message = clienttranslate('${player_name} and ${player_name2} win!');
        $args['player_name2'] = $prioress->playerName;
        $args['player_id2'] = $prioress->playerId;
      }
      $this->bga->notify->all('message', $message, $args);
      return EndGameState::class;
    }

    // Add move to history
    foreach ($nuns as &$nun) {
      if ($nun->move != null) {
        $nun->move->active = false;
        $nun->moves[] = $nun->move;
      }
      $nun->move = null;
    }
    $this->game->saveNuns($nuns);
    $novices = $this->game->getNoviceList();
    foreach ($novices as &$novice) {
      if ($novice->move != null) {
        $novice->moves[] = $novice->move;
      }
      $novice->move = null;
    }
    $this->game->saveNovices($novices);

    // Continue the game
    return NoviceTurnMultiState::class;
  }
}

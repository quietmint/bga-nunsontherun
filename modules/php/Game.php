<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\States\RolesMultiState;

class Game extends \Bga\GameFramework\Table
{
  public const BGA_BLUE = '0000ff';
  public const BGA_GREEN = '008000';
  public const BGA_ORANGE = 'f07f16';
  public const BGA_PURPLE = '982fff';
  public const BGA_RED = 'ff0000';
  public const BGA_YELLOW = 'ffa500';

  public const COLOR_BLACK = '000000';
  public const COLOR_BLUE = '29b6f6'; // light-blue-400
  public const COLOR_GREEN = '4caf50'; // green-500
  public const COLOR_ORANGE = 'ff9800'; // orange-500
  public const COLOR_PURPLE = 'ab47bc'; // purple-400
  public const COLOR_RED = 'ec407a'; // pink-400
  public const COLOR_WHITE = 'ffffff';
  public const COLOR_YELLOW = 'fdd835'; // yellow-600

  public const BLESSING_ADJUST = 'adjust';
  public const BLESSING_MOVE = 'move';
  public const BLESSING_NOISE = 'noise';
  public const BLESSING_REROLL = 'reroll';
  public const BLESSINGS = [
    Game::BLESSING_ADJUST,
    Game::BLESSING_ADJUST,
    Game::BLESSING_MOVE,
    Game::BLESSING_MOVE,
    Game::BLESSING_NOISE,
    Game::BLESSING_NOISE,
    Game::BLESSING_REROLL,
    Game::BLESSING_REROLL,
  ];

  public const OPTION_BLESSINGS = 100;
  public const OPTION_SLOW_NOVICES = 101;
  public const OPTION_SLOW_NUNS = 102;
  public const OPTION_CAUGHT_GOAL = 103;
  public const OPTION_ROLES = 110;
  public const OPTION_ROLES__RANDOM = 0;
  public const OPTION_ROLES__CHOOSE = 1;
  public const OPTION_ROLES__ADMIN = 2;

  public const START_LOCATIONS = [1, 2, 3, 4, 5, 6];
  public const WISHES = ['dessert', 'game', 'letter', 'magazine', 'makeup', 'perfume', 'phone', 'wine'];
  public const ZOMBIE_VERSION = -666;

  public Board $board;

  public function __construct()
  {
    parent::__construct();
    $this->board = new Board($this);
  }

  public function getGameProgression()
  {
    $caughtProgression = round($this->getCaught() / $this->getCaughtGoal() * 100);
    $roundProgression = round(($this->getRound() - 1) / 0.15);
    return min(max($caughtProgression, $roundProgression), 100);
  }

  public function getVersion(): int
  {
    return (int) $this->bga->tableOptions->getGameVersion();
  }

  public function checkVersion(int $clientVersion): void
  {
    if ($clientVersion != Game::ZOMBIE_VERSION && $clientVersion != $this->getVersion()) {
      throw new UserException(new NotificationMessage('', ['checkVersion' => true]));
    }
  }

  /**
   * Migrate database.
   *
   * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
   * method is called everytime the system detects a game running with your old database scheme. In this case, if you
   * change your database scheme, you just have to apply the needed changes in order to update the game database and
   * allow the game to continue to run with your new version.
   * 
   * ! important ! Use `DBPREFIX_<table_name>` for all tables
   *
   * @param int $from_version
   * @return void
   */
  public function upgradeTableDb($from_version) {}

  protected function getAllDatas(int $currentPlayerId): array
  {
    $state = $this->gamestate->getCurrentMainStateClass();
    $novices = $this->getNoviceList();
    $nuns = $this->getNunList();
    $result = [
      'caught' => $this->getCaught(),
      'caughtGoal' => $this->getCaughtGoal(),
      'novices' => $novices->getAllDatas($currentPlayerId, $state, $nuns),
      'nuns' => $nuns->getAllDatas($currentPlayerId, $state),
      'players' => $this->getCollectionFromDb('SELECT `player_id` AS `id`, `player_score` AS `score` FROM `player`'),
      'round' => $this->getRound(),
      'roundMax' => 15,
      'version' => $this->getVersion(),
    ];
    return $result;
  }

  function getSpecificColorPairings(): array
  {
    return [
      Game::BGA_ORANGE => Game::COLOR_ORANGE,
      Game::BGA_BLUE => Game::COLOR_BLUE,
      Game::BGA_RED => Game::COLOR_RED,
      Game::BGA_GREEN => Game::COLOR_GREEN,
      Game::BGA_PURPLE => Game::COLOR_PURPLE,
      Game::BGA_YELLOW => Game::COLOR_YELLOW,
    ];
  }

  /**
   * This method is called only once, when a new game is launched. In this method, you must setup the game
   *  according to the game rules, so that the game is ready to be played.
   */
  protected function setupNewGame($players, $options = [])
  {
    $this->bga->globals->set('players', $players);
    $values = [];
    foreach ($players as $playerId => $player) {
      $values[] = vsprintf("(%s, '%s', '%s')", [
        $playerId,
        Game::COLOR_BLACK,
        addslashes($player["player_name"]),
      ]);
    }
    static::DbQuery(sprintf("INSERT INTO `player` (`player_id`, `player_color`, `player_name`) VALUES %s", implode(",", $values)));

    // Table statistics
    $this->bga->tableStats->set('noiseTokens', 0);
    $this->bga->tableStats->set('vanishTokens', 0);
    return RolesMultiState::class;
  }

  public function getCaught(): int
  {
    return $this->tableStats->get('caught');
  }

  public function getCaughtGoal(): int
  {
    return $this->tableStats->get('caughtGoal');
  }

  public function getRound(): int
  {
    return $this->tableStats->get('round');
  }

  public function incRound(): int
  {
    $this->tableStats->inc('round', 1);
    $round = $this->getRound();
    $this->bga->notify->all('round', clienttranslate('Round ${round} of ${roundMax} begins'), [
      'round' => $round,
      'roundMax' => 15,
    ]);
    return $round;
  }

  public function getPlayerIds(int $dbNun): array
  {
    return array_map('intval', $this->getObjectListFromDB(
      "SELECT `player_id` FROM `player` WHERE `nun` = $dbNun AND `player_zombie` = 0 AND `player_eliminated` = 0",
      true
    ));
  }

  public function getNoviceList(): NoviceList
  {
    return NoviceList::fromData($this->bga->globals->get('novices'));
  }

  public function saveNovice(Novice $novice)
  {
    $noviceList = $this->getNoviceList();
    $noviceList->add($novice);
    $this->saveNovices($noviceList);
  }

  public function saveNovices(NoviceList $noviceList)
  {
    $this->bga->globals->set('novices', $noviceList);
  }

  public function getNunList(): NunList
  {
    return NunList::fromData($this->bga->globals->get('nuns'));
  }

  public function saveNun(Nun $nun)
  {
    $nunList = $this->getNunList();
    $nunList->add($nun);
    $this->saveNuns($nunList);
  }

  public function saveNuns(NunList $nunList)
  {
    $this->bga->globals->set('nuns', $nunList);
  }

  public function winGame(array $winners, string $reason)
  {
    // Set score
    if (!empty($winners)) {
      $caughtTimes = [];
      foreach ($winners as $playerId => $winner) {
        $caughtTimes[$playerId] = $winner['caughtTimes'];
        $this->bga->playerScore->set($playerId, 1);
        $this->bga->playerScoreAux->set($playerId, $winner['caughtTimes'] * -1);
      }
      $min = min($caughtTimes);
      foreach ($winners as $playerId => $winner) {
        if ($winner['caughtTimes'] > $min) {
          unset($winners[$playerId]);
        }
      }
    }

    // Add moves to history
    $novices = $this->getNoviceList();
    foreach ($novices as &$novice) {
      $oldMove = $novice->move;
      if ($oldMove != null) {
        $oldMove->undo = null;
        $novice->moves[] = $oldMove;
      }
    }
    $this->saveNovices($novices);

    $nuns = $this->getNunList();
    foreach ($nuns as &$nun) {
      $oldMove = $nun->move;
      if ($oldMove != null) {
        $oldMove->active = false;
        $oldMove->undo = null;
        $nun->moves[] = $oldMove;
      }
    }
    $this->saveNuns($nuns);

    // Send recap data
    $state = $this->gamestate->getCurrentMainStateClass();
    $novices = $this->getNoviceList();
    $nuns = $this->getNunList();
    $args = [
      'novices' => $novices->getAllDatas(-1, $state, $nuns),
      'nuns' => $nuns->getAllDatas(-1, $state),
      'reason' => $reason,
    ];

    // Message
    if ($reason == 'novice') {
      $this->bga->notify->all('message', clienttranslate('Game over! A novice returned with their secret wish'));
    } else if ($reason == 'caught') {
      $this->bga->notify->all('message', clienttranslate('Game over! Nuns caught ${caught} of ${caughtGoal} novices'), [
        'caught' => $this->getCaught(),
        'caughtGoal' => $this->getCaughtGoal(),
      ]);
    } else if ($reason == 'round') {
      $this->bga->notify->all('message', clienttranslate('Game over! Novices ran out of time'));
    }

    $count = count($winners);
    $message = clienttranslate('${player_name} wins!');
    if ($count == 2) {
      $message = clienttranslate('${player_name} and ${player_name2} win!');
    } else if ($count == 3) {
      $message = clienttranslate('${player_name}, ${player_name2}, and ${player_name3} win!');
    } else if ($count == 4) {
      $message = clienttranslate('${player_name}, ${player_name2}, ${player_name3}, and ${player_name4} win!');
    } else if ($count == 5) {
      $message = clienttranslate('${player_name}, ${player_name2}, ${player_name3}, ${player_name4}, and ${player_name5} win!');
    } else if ($count == 6) {
      $message = clienttranslate('${player_name}, ${player_name2}, ${player_name3}, ${player_name4}, ${player_name5}, and ${player_name6} win!');
    }
    $x = '';
    foreach ($winners as $playerId => $winner) {
      $args['player_id' . $x] = $playerId;
      $args['player_name' . $x] = $winner['playerName'];
      if ($x == '') {
        $x = 2;
      } else {
        $x++;
      }
    }
    $this->bga->notify->all('win', $message, $args);
  }

  function zombieNovice(int $playerId)
  {
    $novice = $this->getNoviceList()->get($playerId);
    $save = false;
    if ($novice->move->action == null) {
      $save = true;
      $novice->move->action = 'stand';
      $this->bga->playerStats->inc('standMove', 1, $novice->playerId);
    }
    if ($novice->location != $novice->startLocation) {
      $save = true;
      $novice->hasWish = false;
      $novice->location = $novice->startLocation;
      $this->bga->notify->all('noviceMove', '', [
        'preserve' => ['location', 'player_id'],
        'location' => $novice->startLocation,
        'player_id' => $novice->playerId,
      ]);
    }
    if (!$novice->caught) {
      $save = true;
      $novice->caught = true;
      $novice->hasWish = false;
      $this->bga->tableStats->inc('caught', 1);
      $this->bga->playerStats->inc('caughtTimes', 1, $novice->playerId);
      $this->bga->notify->all('noviceCaught', '', [
        'preserve' => ['caught', 'caughtMeter', 'player_id2'],
        'caught' => $novice->caught,
        'caughtMeter' => $this->getCaught(),
        'player_id2' => $novice->playerId,
      ]);
    }
    if ($save) {
      $this->saveNovice($novice);
    }
  }

  function zombieNun(int $playerId): ?int
  {
    $playerIds = $this->getPlayerIds(1);
    if (!empty($playerIds)) {
      $playerId = reset($playerIds);
      $nuns = $this->getNunList();
      foreach ($nuns as &$nun) {
        if ($nun->playerId != $playerId) {
          $this->bga->notify->all('message', "🪦 Zombie $playerId: Reassign {$nun->role} to player $playerId");
          $nun->playerId = $playerId;
          $nun->playerName = $this->getPlayerNameById($nun->playerId);
          $this->bga->notify->all('nunZombie', '', [
            'player_id' => $nun->playerId,
            'player_name' => $nun->playerName,
            'role' => $nun->role,
          ]);
        }
      }
      $this->saveNuns($nuns);
      return $playerId;
    }
    return null;
  }

  public function debug_win()
  {
    $state = $this->gamestate->getCurrentMainStateClass();
    $novices = $this->getNoviceList();
    $nuns = $this->getNunList();
    $args = [
      'novices' => $novices->getAllDatas(-1, $state, $nuns),
      'nuns' => $nuns->getAllDatas(-1, $state),
      'reason' => 'round',
    ];
    $this->bga->notify->all('win', 'debug_win', $args);
  }

  /**
   * Example of debug function.
   * Here, jump to a state you want to test (by default, jump to next player state)
   * You can trigger it on Studio using the Debug button on the right of the top bar.
   */
  public function debug_goToState(int $state = 3)
  {
    $this->gamestate->jumpToState($state);
  }

  /**
   * Another example of debug function, to easily test the zombie code.
   */
  public function debug_playOneMove()
  {
    $this->bga->debug->playUntil(fn(int $count) => $count == 1);
  }

  public function debug_playerColors()
  {
    $colors = [];
    $players = $this->loadPlayersBasicInfos();
    foreach ($players as $playerId => $player) {
      $colors[$playerId] = Game::COLOR_ORANGE;
    }
    $this->bga->notify->all('playerColors', '', [
      'colors' => $colors
    ]);
  }
}

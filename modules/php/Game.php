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

use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\Games\NunsOnTheRun\States\NoviceTurnMultiState;
use Random\Randomizer;

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

  public const OPTION_BLESSINGS = 100;
  public const OPTION_SLOW_NOVICES = 101;
  public const OPTION_SLOW_NUNS = 102;
  public const OPTION_CAUGHT_GOAL = 103;

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
    return max($caughtProgression, $roundProgression);
  }

  public function checkVersion(int $clientVersion): void
  {
    if ($clientVersion != $this->bga->tableOptions->getGameVersion()) {
      throw new UserException('!!!checkVersion');
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
      'version' => $this->bga->tableOptions->getGameVersion(),
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

  public function getColorName(string $color): ?string
  {
    switch ($color) {
      case Game::BGA_ORANGE:
      case Game::COLOR_ORANGE:
        return 'orange';
      case Game::BGA_BLUE:
      case Game::COLOR_BLUE:
        return 'blue';
      case Game::BGA_RED:
      case Game::COLOR_RED:
        return 'red';
      case Game::BGA_GREEN:
      case Game::COLOR_GREEN:
        return 'green';
      case Game::BGA_PURPLE:
      case Game::COLOR_PURPLE:
        return 'purple';
      case Game::BGA_YELLOW:
      case Game::COLOR_YELLOW:
        return 'yellow';
      case Game::COLOR_BLACK:
        return 'black';
      case Game::COLOR_WHITE:
        return 'white';
      default:
        throw new SystemException("Unknown color: $color");
    }
  }

  /**
   * This method is called only once, when a new game is launched. In this method, you must setup the game
   *  according to the game rules, so that the game is ready to be played.
   */
  protected function setupNewGame($players, $options = [])
  {
    $r = new Randomizer();
    $gameinfos = $this->getGameinfos();
    $playerCount = count($players);

    // Assign nun colors
    $insertNuns = [];
    $nunColors = ['000000', 'ffffff'];
    $nunCount = $playerCount == 8 ? 2 : 1;
    $nunIds = $r->pickArrayKeys($players, $nunCount);
    foreach ($nunIds as $playerId) {
      $player = $players[$playerId];
      $color = array_shift($nunColors);
      $insertNuns[] = vsprintf("(%s, '%s', '%s', 1)", [
        $playerId,
        $color,
        addslashes($player['player_name']),
      ]);
      unset($players[$playerId]);
    }

    // Assign novice colors
    $insertNovices = [];
    $colors = $gameinfos['player_colors'];
    foreach ($players as $playerId => $player) {
      $color = array_shift($colors);
      $insertNovices[] = vsprintf("(%s, '%s', '%s', 0)", [
        $playerId,
        $color,
        addslashes($player["player_name"]),
      ]);
    }

    // Create novices
    static::DbQuery(sprintf("INSERT INTO `player` (`player_id`, `player_color`, `player_name`, `nun`) VALUES %s", implode(",", $insertNovices)));
    $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);

    // Create nuns
    static::DbQuery(sprintf("INSERT INTO `player` (`player_id`, `player_color`, `player_name`, `nun`) VALUES %s", implode(",", $insertNuns)));
    $this->reloadPlayersBasicInfos();

    // Setup novices
    $novices = new NoviceList();
    $novicePlayers = $this->getCollectionFromDb("SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 0 ORDER BY `player_no`");
    $wishes = $r->shuffleArray(['dessert', 'game', 'letter', 'magazine', 'makeup', 'perfume', 'phone', 'wine']);
    $location = 0;
    foreach ($novicePlayers as $playerId => $player) {
      $color = $player['player_color'];
      $location++;
      $novice = new Novice(
        color: $this->getColorName($color),
        location: $location,
        move: new Move(
          start: $location,
        ),
        playerId: $playerId,
        playerName: $player['player_name'],
        room: $this->board->getRoomId($location),
        startLocation: $location,
        wish: array_shift($wishes),
      );
      $novices->add($novice);
      $this->bga->notify->all(
        'message',
        clienttranslate('${player_name} starts at ${startLocation}'),
        [
          'i18n' => ['role'],
          'player_id' => $novice->playerId,
          'player_name' => $novice->playerName,
          'role' => 'novice',
          'startLocation' => $novice->location,
        ]
      );
      $this->bga->notify->player(
        $playerId,
        'wish',
        clienttranslate('Your secret wish is ${wish}'),
        [
          'i18n' => ['wish'],
          'keyLocation' => $novice->keyLocation,
          'preserve' => [
            'keyLocation',
            'wishIcon',
            'wishLocation',
          ],
          'wish' => $novice->wish,
          'wishIcon' => $novice->wish,
          'wishLocation' => $novice->wishLocation,
        ]
      );
      $this->bga->playerStats->set('caughtTimes', 0, $playerId);
      $this->bga->playerStats->set('keyLocation', $novice->keyLocation, $playerId);
      $this->bga->playerStats->set('keyObtained', 0, $playerId);
      $this->bga->playerStats->set('noiseTokens', 0, $playerId);
      $this->bga->playerStats->set('role', 0, $playerId);
      $this->bga->playerStats->set('runMove', 0, $playerId);
      $this->bga->playerStats->set('sneakMove', 0, $playerId);
      $this->bga->playerStats->set('spaces', 0, $playerId);
      $this->bga->playerStats->set('standMove', 0, $playerId);
      $this->bga->playerStats->set('startLocation', $novice->location, $playerId);
      $this->bga->playerStats->set('vanishTokens', 0, $playerId);
      $this->bga->playerStats->set('walkMove', 0, $playerId);
      $this->bga->playerStats->set('wishLocation', $novice->wishLocation, $playerId);
      $this->bga->playerStats->set('wishObtained', 0, $playerId);
    }
    $this->saveNovices($novices);

    // Setup nuns
    $nuns = new NunList();
    $nunColors = ['000000', 'ffffff'];
    if (count($nunIds) == 1) {
      $nunIds[1] = $nunIds[0];
    }
    $nunPlayers = $this->getCollectionFromDb("SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 1 ORDER BY `player_no`");
    foreach (['abbess', 'prioress'] as $role) {
      $playerId = array_shift($nunIds);
      $player = $nunPlayers[$playerId];
      $color = array_shift($nunColors);
      $nun = new Nun(
        color: $this->getColorName($color),
        location: 26,
        move: new Move(
          start: 26,
          undo: [],
        ),
        playerId: $playerId,
        playerName: $player['player_name'],
        role: $role,
        room: $this->board->getRoomId(26),
      );
      $nuns->add($nun);
      $this->bga->notify->all(
        'message',
        clienttranslate('${roleName} ${player_name} starts at ${location}'),
        [
          'i18n' => ['roleName'],
          'preserve' => ['role'],
          'location' => $nun->location,
          'player_id' => $nun->playerId,
          'player_name' => $nun->playerName,
          'role' => $nun->role,
          'roleName' => $nun->roleName,
        ]
      );
      $this->bga->playerStats->set('caught', 0, $playerId);
      $this->bga->playerStats->set('role', 1, $playerId);
      $this->bga->playerStats->set('runMove', 0, $playerId);
      $this->bga->playerStats->set('spaces', 0, $playerId);
      $this->bga->playerStats->set('walkMove', 0, $playerId);
    }
    $this->saveNuns($nuns);

    // Table statistics
    $this->incRound();
    $caughtGoal = $this->bga->tableOptions->get(Game::OPTION_CAUGHT_GOAL) == 1 ? count($novices) : $playerCount;
    $this->bga->tableStats->set('caughtGoal', $caughtGoal);
    $this->bga->tableStats->set('noiseTokens', 0);
    $this->bga->tableStats->set('vanishTokens', 0);

    $this->bga->notify->all('message', clienttranslate('Nuns must catch ${caughtGoal} novices to win'), [
      'caughtGoal' => $caughtGoal,
    ]);

    return NoviceTurnMultiState::class;
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
    $this->bga->notify->all('round', clienttranslate('Round ${round} of ${roundMax}'), [
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
      $this->bga->notify->all('message', clienttranslate('Game over! Nuns caught ${caught} novices'), [
        'caught' => $this->getCaught(),
      ]);
    } else if ($reason == 'round') {
      $this->bga->notify->all('message', clienttranslate('Game over! Novices ran out of time'));
    }

    $count = count($winners);
    $message = '';
    if ($count == 1) {
      $message = clienttranslate('${player_name} wins!');
    } else if ($count == 2) {
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
}

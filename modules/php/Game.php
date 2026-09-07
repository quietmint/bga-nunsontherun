<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\Components\Counters\PlayerCounter;
use Bga\Games\NunsOnTheRun\States\NoviceTurnMultiState;
use Bga\Games\NunsOnTheRun\States\NunMovePlayerState;

class Game extends \Bga\GameFramework\Table
{
  public Board $board;

  /**
   * Your global variables labels:
   *
   * Here, you can assign labels to global variables you are using for this game. You can use any number of global
   * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
   *
   * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
   * `setGameStateValue` functions.
   */
  public function __construct()
  {
    parent::__construct();
    $this->bga->notify->alwaysMergePrivate();
    $this->board = new Board($this);

    /* example of notification decorator.
    // automatically complete notification args when needed
    $this->bga->notify->addDecorator(function(string $message, array $args) {
        if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
            $args['player_name'] = $this->getPlayerNameById($args['player_id']);
        }
    
        if (isset($args['card_id']) && !isset($args['card_name']) && str_contains($message, '${card_name}')) {
            $args['card_name'] = self::$CARD_TYPES[$args['card_id']]['card_name'];
            $args['i18n'][] = ['card_name'];
        }
        
        return $args;
    });*/
  }

  /**
   * Compute and return the current game progression.
   *
   * The number returned must be an integer between 0 and 100.
   *
   * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
   *
   * @return int
   */
  public function getGameProgression()
  {
    $caughtGoal = $this->getCaughtGoal();
    $caughtProgression = round($this->getCaught() / $caughtGoal * 100);
    $roundProgression = round(($this->getRound() - 1) / 0.15);
    return max($caughtProgression, $roundProgression);
  }

  /**
   * Migrate database.
   *
   * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
   * method is called everytime the system detects a game running with your old database scheme. In this case, if you
   * change your database scheme, you just have to apply the needed changes in order to update the game database and
   * allow the game to continue to run with your new version.
   *
   * @param int $from_version
   * @return void
   */
  public function upgradeTableDb($from_version)
  {
    //       if ($from_version <= 1404301345)
    //       {
    //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
    //
    //            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
    //            $this->applyDbUpgradeToAllDB( $sql );
    //       }
    //
    //       if ($from_version <= 1405061421)
    //       {
    //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
    //
    //            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
    //            $this->applyDbUpgradeToAllDB( $sql );
    //       }
  }

  /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
  protected function getAllDatas(int $currentPlayerId): array
  {
    $state = $this->gamestate->getCurrentMainStateClass();
    $nuns = $this->getNunList();
    $novices = $this->getNoviceList();
    $result = [
      'caught' => $this->getCaught(),
      'caughtGoal' => $this->getCaughtGoal(),
      'novices' => $novices->getAllDatas($currentPlayerId, $state, $nuns),
      'nuns' => $nuns->getAllDatas($currentPlayerId, $state),
      'players' => $this->getCollectionFromDb('SELECT `player_id` AS `id`, `player_score` AS `score`, `colorName` FROM `player`'),
      'round' => $this->getRound(),
    ];
    return $result;
  }

  function getPlayerIds(int $dbNun): array
  {
    return $this->getObjectListFromDB(
      "SELECT `player_id` FROM `player` WHERE `nun` = $dbNun AND `player_zombie` = 0 AND `player_eliminated` = 0",
      true
    );
  }

  function getNoviceList(): NoviceList
  {
    return new NoviceList($this->bga->globals->get('novices'));
  }

  function saveNovice(Novice $novice)
  {
    $noviceList = $this->getNoviceList();
    $noviceList->add($novice);
    $this->saveNovices($noviceList);
  }

  function saveNovices(NoviceList $noviceList)
  {
    $this->bga->globals->set('novices', $noviceList);
  }

  function getNunList(): NunList
  {
    return new NunList($this->bga->globals->get('nuns'));
  }

  function saveNun(Nun $nun)
  {
    $nunList = $this->getNunList();
    $nunList->add($nun);
    $this->saveNuns($nunList);
  }

  function saveNuns(NunList $nunList)
  {
    $this->bga->globals->set('nuns', $nunList);
  }

  function getSpecificColorPairings(): array
  {
    return [
      'f07f16' /* Orange */      => 'ff9800', // orange-500
      '0000ff' /* Blue */        => '03a9f4', // light-blue-500
      'ff0000' /* Red */         => 'e91e63', // pink-500
      '008000' /* Green */       => '8bc34a', // light-green-500
      '982fff' /* Purple */      => '9c27b0', // purple-500
      'ffa500' /* Yellow */      => 'ffeb3b', // yellow-500
    ];
  }

  public function getColorName(string $color): ?string
  {
    switch ($color) {
      case 'f07f16': // bga orange
      case 'ff9800': // orange-500
        return 'orange';
      case '0000ff': // bga blue
      case '03a9f4': // light-blue-500
        return 'blue';
      case 'ff0000': // bga red
      case 'e91e63': // pink-500
        return 'red';
      case '008000': // bga green
      case '8bc34a': // light-green-500
        return 'green';
      case '982fff': // bga purple
      case '9c27b0': // purple-500
        return 'purple';
      case 'ffa500': // bga yellow
      case 'ffeb3b': // yellow-500
        return 'yellow';
      case '000000':
        return 'black';
      case 'ffffff':
        return 'white';
      default:
        return null;
    }
  }

  /**
   * This method is called only once, when a new game is launched. In this method, you must setup the game
   *  according to the game rules, so that the game is ready to be played.
   */
  protected function setupNewGame($players, $options = [])
  {
    $insert = [];

    // Assign nun colors
    $r = new \Random\Randomizer();
    $nunColors = ['000000', 'ffffff'];
    $nunCount = count($players) == 8 ? 2 : 1;
    $nunIds = $r->pickArrayKeys($players, $nunCount);
    foreach ($nunIds as $playerId) {
      $player = $players[$playerId];
      $color = array_shift($nunColors);
      $insert[] = vsprintf("(%s, '%s', '%s', 1)", [
        $playerId,
        $color,
        addslashes($player['player_name']),
      ]);
      unset($players[$playerId]);
    }

    // Assign novice colors
    $gameinfos = $this->getGameinfos();
    foreach ($players as $playerId => $player) {
      // Now you can access both $player_id and $player array
      $color = array_shift($gameinfos['player_colors']);
      $insert[] = vsprintf("(%s, '%s', '%s', 0)", [
        $playerId,
        $color,
        addslashes($player["player_name"]),
      ]);
    }

    // Create players
    static::DbQuery(sprintf("INSERT INTO `player` (`player_id`, `player_color`, `player_name`, `nun`) VALUES %s", implode(",", $insert)));
    $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
    $this->reloadPlayersBasicInfos();

    // Setup nuns
    $nuns = new NunList();
    $nunColors = ['000000', 'ffffff'];
    if (count($nunIds) == 1) {
      $nunIds[1] = $nunIds[0];
    }
    $players = $this->getCollectionFromDb(
      "SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 1 ORDER BY `player_no`"
    );
    foreach (['abbess', 'prioress'] as $role) {
      $playerId = array_shift($nunIds);
      $player = $players[$playerId];
      $color = array_shift($nunColors);
      $colorName = $this->getColorName($color);
      $this->DbQuery("UPDATE `player` SET `colorName` = '$colorName' WHERE `player_color` = '$color'");
      $nun = new Nun();
      $nun->color = $colorName;
      $nun->location = 26;
      $nun->playerId = $playerId;
      $nun->playerName = $player['player_name'];
      $nun->role = $role;
      $nun->room = $this->board->getRoomId($nun->location);
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

    // Setup novices
    $novices = new NoviceList();
    $players = $this->getCollectionFromDb(
      "SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 0 ORDER BY `player_no`"
    );
    $wishes = $r->shuffleArray(['dessert', 'game', 'letter', 'magazine', 'makeup', 'perfume', 'phone', 'wine']);
    $location = 1;
    foreach ($players as $playerId => $player) {
      $color = $player['player_color'];
      $colorName = $this->getColorName($color);
      $this->DbQuery("UPDATE `player` SET `colorName` = '$colorName' WHERE `player_color` = '$color'");
      $novice = new Novice();
      $novice->color = $colorName;
      $novice->location = $location++;
      $novice->playerId = $playerId;
      $novice->playerName = $player['player_name'];
      $novice->room = $this->board->getRoomId($novice->location);
      $novice->startLocation = $novice->location;
      $novice->wish = array_shift($wishes);
      $novices->add($novice);
      $this->bga->notify->all(
        'message',
        clienttranslate('${player_name} starts at ${location}'),
        [
          'i18n' => ['role'],
          'location' => $novice->location,
          'player_id' => $novice->playerId,
          'player_name' => $novice->playerName,
          'role' => 'novice',
        ]
      );
      $this->bga->notify->player(
        $playerId,
        'wish',
        clienttranslate('Your secret wish is ${wish}'),
        [
          'i18n' => ['wish'],
          'keyLocation' => $novice->getKeyLocation(),
          'preserve' => [
            'keyLocation',
            'wishIcon',
            'wishLocation',
          ],
          'wish' => $novice->wish,
          'wishIcon' => $novice->wish,
          'wishLocation' => $novice->getWishLocation(),
        ]
      );
      $this->bga->playerStats->set('caughtTimes', 0, $playerId);
      $this->bga->playerStats->set('keyLocation', $novice->getKeyLocation(), $playerId);
      $this->bga->playerStats->set('keyObtained', 0, $playerId);
      $this->bga->playerStats->set('role', 0, $playerId);
      $this->bga->playerStats->set('runMove', 0, $playerId);
      $this->bga->playerStats->set('sneakMove', 0, $playerId);
      $this->bga->playerStats->set('spaces', 0, $playerId);
      $this->bga->playerStats->set('standMove', 0, $playerId);
      $this->bga->playerStats->set('startLocation', $novice->location, $playerId);
      $this->bga->playerStats->set('walkMove', 0, $playerId);
      $this->bga->playerStats->set('wishLocation', $novice->getKeyLocation(), $playerId);
      $this->bga->playerStats->set('wishObtained', 0, $playerId);
    }
    $this->saveNovices($novices);

    // Table statistics
    $this->incRound();

    return NoviceTurnMultiState::class;
  }

  public function getCaught(): int
  {
    return $this->tableStats->get('caught');
  }

  public function getCaughtGoal(): int
  {
    // TODO: game option
    // "If it turns out that the nuns’ task is too difficult, you can reduce
    // the number of novices they need to catch to win to the number of novices
    // in the game (instead of the total number of players)."
    $playerCount = (int) $this->getUniqueValueFromDB('SELECT COUNT(1) FROM `player`');
    return $playerCount;
  }

  public function getRound(): int
  {
    return $this->tableStats->get('round');
  }

  public function incRound(): int
  {
    $this->tableStats->inc('round', 1);
    $round = $this->getRound();
    $message = clienttranslate('Round ${round} of 15');
    if ($round == 15) {
      $message = clienttranslate('Round ${round} of 15. The novices are out of time!');
    } else if ($round == 14) {
      $message = clienttranslate('Round ${round} of 15. This is the final round!');
    }
    $this->bga->notify->all('round', $message, [
      'round' => $round
    ]);
    return $round;
  }

  public function getMoveNoise(string $move): ?int
  {
    switch ($move) {
      case 'run':
        return 1;
      case 'walk':
        return -1;
      case 'sneak':
        return -2;
      case 'still':
        return -3;
      default:
        return null;
    }
  }

  public function winGame(int $dbNun) {}

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

  /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }
    */
}

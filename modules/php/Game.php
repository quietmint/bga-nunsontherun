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
use Bga\Games\NunsOnTheRun\States\NoviceMove;

class Game extends \Bga\GameFramework\Table
{
  private Board $board;

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
    return 0;
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
    $result = [];
    $result['players'] = $this->getCollectionFromDb(
      'SELECT `player_id` AS `id`, `player_score` AS `score` FROM `player`'
    );
    $novices = $this->bga->globals->get('novices');
    foreach ($novices as $id => &$novice) {
      if ($id == $currentPlayerId) {
        $novice['keyLocation'] = $this->getKeyLocation($novice['wish']);
        $novice['wishLocation'] = $this->getWishLocation($novice['wish']);
      } else {
        unset($novice['wish']);
      }
    }
    $result['novices'] = $novices;
    $result['nuns'] = $this->bga->globals->get('nuns');
    return $result;
  }

  function getSpecificColorPairings(): array
  {
    return [
      'f07f16' /* Orange */      => 'ff5722', // deep-orange-500
      '0000ff' /* Blue */        => '03a9f4', // light-blue-500
      'ff0000' /* Red */         => 'e91e63', // pink-500
      '008000' /* Green */       => '8bc34a', // light-green-500
      '982fff' /* Purple */      => '9c27b0', // purple-500
      'ffa500' /* Yellow */      => 'ffc107', // amber-500
    ];
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
    $noviceColors = $r->shuffleArray($gameinfos['player_colors']);
    foreach ($players as $playerId => $player) {
      // Now you can access both $player_id and $player array
      $color = array_shift($noviceColors);
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
    $nuns = [];
    $nunColors = ['000000', 'ffffff'];
    if (count($nunIds) == 1) {
      $nunIds[1] = $nunIds[0];
    }
    $players = $this->getCollectionFromDb(
      "SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 1"
    );
    foreach (['abbess', 'prioress'] as $nun) {
      $playerId = array_shift($nunIds);
      $player = $players[$playerId];
      $color = array_shift($nunColors);
      $nuns[$nun] = [
        'color' => $color,
        'location' => 26,
        'nun' => $nun,
        'playerId' => $playerId,
      ];
      $this->bga->notify->all(
        'message',
        clienttranslate('${player_name} (${nun}) starts at ${location}.'),
        [
          'i18n' => ['nun'],
          'location' => 26,
          'nun' => $nun,
          'player_id' => $playerId,
          'player_name' => $player['player_name'],
        ]
      );
    }
    $this->bga->globals->set('nuns', $nuns);

    // Setup novices
    $novices = [];
    $players = $this->getCollectionFromDb(
      "SELECT `player_id`, `player_color`, `player_name` FROM `player` WHERE `nun` = 0"
    );
    $wishes = $r->shuffleArray(['dessert', 'game', 'letter', 'magazine', 'makeup', 'perfume', 'phone', 'wine']);
    $starts = [
      "ff5722" => 1, // Orange (was f07f16) - starts at 1
      "03a9f4" => 2, // Blue (was 0000ff) - starts at 2
      "e91e63" => 3, // Red (was ff0000) - starts at 3
      "8bc34a" => 4, // Green (was 008000) - starts at 4
      "9c27b0" => 5, // Purple (was 982fff) - starts at 5
      "ffc107" => 6, // Yellow (was ffa500) - starts at 6
    ];
    foreach ($players as $playerId => $player) {
      $color = $player['player_color'];
      $start = $starts[$color];
      $wish = array_shift($wishes);
      $novices[$playerId] = [
        'color' => $color,
        'location' => $start,
        'playerId' => $playerId,
        'wish' => $wish,
      ];
      $this->bga->notify->all(
        'message',
        clienttranslate('${player_name} (${nun}) starts at ${location}.'),
        [
          'i18n' => ['nun'],
          'location' => $start,
          'nun' => 'novice',
          'player_id' => $playerId,
          'player_name' => $player['player_name'],
        ]
      );
      $this->bga->notify->player(
        $playerId,
        'wish',
        clienttranslate('Your secret wish is ${wish}'),
        [
          'i18n' => ['wish'],
          'keyLocation' => $this->getKeyLocation($wish),
          'preserve' => [
            'keyLocation',
            'wishIcon',
            'wishLocation',
          ],
          'wish' => $wish,
          'wishIcon' => $wish,
          'wishLocation' => $this->getWishLocation($wish),
        ]
      );
    }
    $this->bga->globals->set('novices', $novices);

    // Init global values with their initial values.

    // Init game statistics.
    //
    // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

    // Dummy content.
    // $this->tableStats->init('table_teststat1', 0);
    // $this->playerStats->init('player_teststat1', 0);

    // TODO: Setup the initial game situation here.

    return NoviceMove::class;
  }

  public function getKeyLocation(string $wish): ?int
  {
    switch ($wish) {
      case 'dessert':
        return 36;
      case 'game':
        return 67;
      case 'letter':
        return 72;
      case 'magazine':
        return 107;
      case 'makeup':
        return 130;
      case 'perfume':
        return 149;
      case 'phone':
        return 82;
      case 'wine':
        return 36;
      default:
        return null;
    }
  }

  public function getWishLocation(string $wish): ?int
  {
    switch ($wish) {
      case 'dessert':
        return 148;
      case 'game':
        return 109;
      case 'letter':
        return 110;
      case 'magazine':
        return 119;
      case 'makeup':
        return 121;
      case 'perfume':
        return 121;
      case 'phone':
        return 118;
      case 'wine':
        return 155;
      default:
        return null;
    }
  }

  public function getMoveDistance(string $move): ?array
  {
    switch ($move) {
      case 'run':
        return [1, 5];
      case 'walk':
        return [3, 4];
      case 'sneak':
        return [1, 2];
      case 'still':
        return [0];
      default:
        return null;
    }
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

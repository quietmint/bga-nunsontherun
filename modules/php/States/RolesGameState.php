<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;
use Bga\Games\NunsOnTheRun\Novice;
use Bga\Games\NunsOnTheRun\NoviceList;
use Bga\Games\NunsOnTheRun\Nun;
use Bga\Games\NunsOnTheRun\NunList;
use Random\Randomizer;

class RolesGameState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 42,
      type: StateType::GAME,
    );
  }

  function onEnteringState()
  {
    $r = new Randomizer();
    $players = $this->bga->globals->get('players');
    $roles = $this->bga->globals->get('roles');
    $noviceIds = array_keys($roles, 'novice');
    $gameinfos = $this->game->getGameinfos();
    $playersReattribute = array_intersect_key($players, array_flip($noviceIds));
    $this->game->reattributeColorsBasedOnPreferences($playersReattribute, $gameinfos['player_colors']);
    $nunIds = array_keys($roles, 'nun');
    if (count($nunIds) == 1) {
      $nunIds[] = $nunIds[0];
    }
    $nunInfos = [
      'abbess' => [
        'color' =>  Game::COLOR_BLACK,
        'role' => 'abbess',
        'playerId' => $nunIds[0],
      ],
      'prioress' => [
        'color' => Game::COLOR_WHITE,
        'role' => 'prioress',
        'playerId' => $nunIds[1],
      ],
    ];
    foreach (array_reverse($nunInfos, true) as $role => $info) {
      $this->game->DbQuery("UPDATE `player` SET `nun` = 1, `player_color` = '{$info['color']}' WHERE `player_id` = {$info['playerId']}");
    }
    $this->game->reloadPlayersBasicInfos();
    $players = $this->game->loadPlayersBasicInfos();
    $this->notifyColors($players);

    // Setup novices
    $this->notifyNoviceNames($noviceIds, $players);
    $novices = new NoviceList();
    $blessings = $this->bga->tableOptions->get(Game::OPTION_BLESSINGS) ? $r->shuffleArray(Game::BLESSINGS) : [];
    $locations = $r->shuffleArray(Game::START_LOCATIONS);
    $wishes = $r->shuffleArray(Game::WISHES);
    foreach ($noviceIds as $playerId) {
      $color = $players[$playerId]['player_color'];
      $blessing = array_shift($blessings);
      $location = array_shift($locations);
      $room = $this->game->board->getRoomId($location);
      $novice = new Novice(
        blessing: $blessing,
        color: $this->getColorName($color),
        location: $location,
        move: new Move(
          start: $location,
          startRoom: $room,
        ),
        playerId: $playerId,
        playerName: $players[$playerId]['player_name'],
        room: $room,
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
    $this->game->saveNovices($novices);

    // Setup nuns
    $this->notifyNunNames(array_unique($nunIds), $players);
    $nuns = new NunList();
    foreach ($nunInfos as $role => $info) {
      $playerId = $info['playerId'];
      $blessing = array_shift($blessings);
      $room = $this->game->board->getRoomId(26);
      $nun = new Nun(
        blessing: $blessing,
        color: $this->getColorName($info['color']),
        location: 26,
        move: new Move(
          start: 26,
          startRoom: $room,
          undo: [],
        ),
        playerId: $playerId,
        playerName: $players[$playerId]['player_name'],
        role: $info['role'],
        room: $room,
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
      $this->bga->playerStats->set('startLocation', $nun->location, $playerId);
      $this->bga->playerStats->set('walkMove', 0, $playerId);
    }
    $this->game->saveNuns($nuns);

    // Caught goal
    $playerCount = count($players);
    $caughtGoal = $this->bga->tableOptions->get(Game::OPTION_CAUGHT_GOAL) == 1
      ? ($playerCount == 8 ? 6 : $playerCount - 1)
      : $playerCount;
    $this->bga->tableStats->set('caughtGoal', $caughtGoal);
    $this->bga->notify->all('message', clienttranslate('Nuns must catch ${caughtGoal} novices to win'), [
      'caughtGoal' => $caughtGoal,
    ]);
    $this->game->incRound();

    $this->notifyRoles($novices, $nuns);
    $this->notifyWish($novices);
    return NoviceTurnMultiState::class;
  }

  private function getColorName(string $color): ?string
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

  private function notifyColors(array $players): void
  {
    $colors = [];
    foreach ($players as $playerId => $player) {
      $colors[$playerId] = $player['player_color'];
    }
    $this->bga->notify->all('playerColors', '', [
      'colors' => $colors
    ]);
  }

  private function notifyNoviceNames(array $noviceIds, array $players)
  {
    $count = count($noviceIds);
    $args = [];
    $message = clienttranslate('Novices are ${player_name}');
    if ($count == 2) {
      $message = clienttranslate('Novices are ${player_name} and ${player_name2}');
    } else if ($count == 3) {
      $message = clienttranslate('Novices are ${player_name}, ${player_name2}, and ${player_name3}');
    } else if ($count == 4) {
      $message = clienttranslate('Novices are ${player_name}, ${player_name2}, ${player_name3}, and ${player_name4}');
    } else if ($count == 5) {
      $message = clienttranslate('Novices are ${player_name}, ${player_name2}, ${player_name3}, ${player_name4}, and ${player_name5}');
    } else if ($count == 6) {
      $message = clienttranslate('Novices are ${player_name}, ${player_name2}, ${player_name3}, ${player_name4}, ${player_name5}, and ${player_name6}');
    }
    $x = '';
    foreach ($noviceIds as $playerId) {
      $args['player_id' . $x] = $playerId;
      $args['player_name' . $x] = $players[$playerId]['player_name'];
      if ($x == '') {
        $x = 2;
      } else {
        $x++;
      }
    }
    $this->bga->notify->all('message', $message, $args);
  }

  private function notifyNunNames(array $nunIds, array $players)
  {
    // Nuns
    $count = count($nunIds);
    $args = [];
    $message = '';
    if ($count == 1) {
      $message = clienttranslate('Nuns are ${player_name}');
    } else if ($count == 2) {
      $message = clienttranslate('Nuns are ${player_name} and ${player_name2}');
    }
    $x = '';
    foreach ($nunIds as $playerId) {
      $args['player_id' . $x] = $playerId;
      $args['player_name' . $x] = $players[$playerId]['player_name'];
      if ($x == '') {
        $x = 2;
      } else {
        $x++;
      }
    }
    $this->bga->notify->all('message', $message, $args);
  }

  private function notifyRoles(NoviceList $novices, NunList $nuns): void
  {
    $args = [
      'novices' => $novices->getAllDatas(0, $this, $nuns),
      'nuns' => $nuns->getAllDatas(0, $this),
      '_private' => [],
    ];
    foreach ($novices as $playerId => $novice) {
      $private = $novices->getAllDatas($playerId, $this, $nuns);
      $args['_private'][$playerId]['novice'] = $private[$playerId];
    }
    $this->bga->notify->all('roles', '', $args);
  }

  private function notifyWish(NoviceList $novices): void
  {
    foreach ($novices as $novice) {
      $this->bga->notify->player(
        $novice->playerId,
        'wish',
        clienttranslate('Your secret wish is ${wish}'),
        [
          'i18n' => ['wish'],
          'preserve' => [
            'keyLocation',
            'startLocation',
            'wishIcon',
            'wishLocation',
          ],
          'keyLocation' => $novice->keyLocation,
          'startLocation' => $novice->startLocation,
          'wish' => $novice->wish,
          'wishIcon' => $novice->wish,
          'wishLocation' => $novice->wishLocation,
        ]
      );
    }
  }
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;

const TRAVERSE_UNLOCKED = 1;
const TRAVERSE_SINGLE_ROOM = 2;
const TRAVERSE_ZERO = 4;
const TRAVERSE_TARGET = 8;
const TRAVERSE_RESTRICT = 16;

class Board
{
	private Game $game;
	public array $spaces = [];
	public array $paths = [];

	public function __construct(Game $game)
	{
		$this->game = $game;

		$rooms = [
			[
				1 => [21],
			],
			[
				2 => [20],
			],
			[
				3 => [19],
			],
			[
				4 => [18],
			],
			[
				5 => [17],
			],
			[
				6 => [16],
			],
			[
				// showers
				7 => [8, 14],
				8 => [7, 9],
				9 => [8, 10, 12],
			],
			[
				// key 1 in showers storage
				10 => [9, 11],
				11 => [10, 32],
				32 => [11, 31],
			],
			[
				// garden
				12 => [9, 13, 31],
				13 => [12, 14],
				14 => [7, 13, 15],
				15 => [14, 16, 30],
				30 => [15, 29, 35],
				31 => [12, 32, 34],
				34 => [31, 33, 54],
				35 => [30, 51],
			],
			[
				// bedroom hallway left
				16 => [6, 15, 17],
				17 => [5, 16, 18],
				18 => [4, 17, 19, 27],
			],
			[
				// bedroom hallway right
				19 => [3, 18, 20, -26],
				20 => [2, 19, 21],
				21 => [1, 20, 22],
				22 => [21, 23],
			],
			[
				// entry hallway bottom
				23 => [22, 43],
			],
			[
				// hub bedroom
				24 => [25, 42],
				25 => [24, -26],
			],
			[
				// hub
				26 => [-19, -25, -40]
			],
			[
				// hub hallway
				27 => [18, 39],
			],
			[
				// hub desk
				28 => [38],
			],
			[
				// toilets
				29 => [30],
			],
			[
				// storage
				33 => [34, 55],
			],
			[
				// key 2 in toilets storage
				36 => [50],
			],
			[
				// courtyard
				37 => [38, 49],
				38 => [28, 37, 39],
				39 => [27, 38, 40, 48],
				40 => [-26, 39, 41],
				41 => [40, 46],
				46 => [41, 45, 47, 68],
				47 => [46, 66],
				48 => [39, 49, 63],
				49 => [37, 48, 50, 62],
				62 => [49, 76],
				63 => [48, 64, 66],
				64 => [63, 65, 75],
				65 => [64, 66, 74],
				66 => [47, 63, 65],
				68 => [46, 67, 73],
				73 => [68, 94],
				74 => [65, 93],
				75 => [64, 91],
				76 => [62, 77, 90],
				90 => [76, 91],
				91 => [75, 90, 92, 102],
				92 => [91, 93, 101],
				93 => [74, 92, 94, 100],
				94 => [73, 93, 95, 99],
			],
			[
				// entry
				42 => [24, 43, 45],
				43 => [23, 42, 44],
				44 => [43, 45, 70],
				45 => [42, 46, 69, 44],
				69 => [45, 70],
				70 => [44, 69, 71],
			],
			[
				// chapel hallway bottom
				50 => [36, 49, 51],
				51 => [35, 50, 52],
				52 => [51, 53],
			],
			[
				// shrine garden
				53 => [52, 54, 58],
				54 => [34, 53, 55, 57],
				55 => [33, 54, 56],
				56 => [55, 83],
				57 => [54, 82],
				58 => [53, 80],
				80 => [58, 79, 81, 86],
				81 => [80, 82],
				83 => [56, 84],
				84 => [83, 85, -109],
				85 => [84, 86, 108],
				86 => [80, 85],
			],
			[
				// chapel
				59 => [60, 79],
				60 => [51, 59, 61, 78],
				61 => [60, 77],
				79 => [59, 78, 80, 87],
				78 => [60, 77, 79, 88],
				77 => [61, 76, 78, 89],
				87 => [79, 88, 106],
				88 => [78, 87, 89, 105],
				89 => [77, 88, 104],
				104 => [89, 103, 105, 115],
				105 => [88, 104, 106, 114],
				106 => [87, 105, 107, 113],
				113 => [106, 112, 114, 131],
				114 => [105, 113, 115, 130],
				115 => [104, 114, 116, 129],
				131 => [113, 130, 134],
				130 => [114, 129, 131],
				129 => [115, 130, 135],
			],
			[
				// key 7 in courtyard tower
				67 => [68],
			],
			[
				// entry hallway top
				71 => [70, 72, 96],
				96 => [71, 95, 97],
				97 => [96, 98, 122],
				122 => [97, -121, 123],
			],
			[
				// key 8 in entry storage
				72 => [71]
			],
			[
				// key 3 in shrine
				82 => [57, 81],
			],
			[
				// garden hallway
				95 => [94, 96],
			],
			[
				// pharmacy storage
				98 => [97],
			],
			[
				// library hallway right
				99 => [94, 120],
				120 => [99, 125],
			],
			[
				// library desk
				100 => [93, 101],
				101 => [92, 100, -118],
			],
			[
				// chapel hallway right
				102 => [91, 117],
				117 => [102, 116, 128],
				128 => [117, 127, 137],
				137 => [128, 136],
			],
			[
				// confessional right
				103 => [104],
			],
			[
				// key 4 in confessional left
				107 => [106],
			],
			[
				// chapel storage left
				108 => [85, 111],
				111 => [108, 112],
			],
			[
				// shrine storage
				109 => [-84, -110],
			],
			[
				// outside left
				110 => [-109, 132],
				132 => [110, 133],
				133 => [132, 151],
				151 => [133, 152],
			],
			[
				// chapel curtain left
				112 => [111, 113],
			],
			[
				// chapel curtain right
				116 => [115, 117],
			],
			[
				// library
				118 => [-101, 119],
				119 => [118, -126],
			],
			[
				// pharmacy
				121 => [-122],
			],
			[
				// dining hallway
				123 => [122, 124, 142],
				124 => [123, 125],
				125 => [120, 124, 126],
				126 => [-119, 125, 127, 139],
				127 => [126, 128, 138],
			],
			[
				// chapel curtain back
				134 => [131, 150],
				150 => [134, 135],
				135 => [129, 149, 150],
			],
			[
				// key 6 in chapel storage
				136 => [137, 149],
				149 => [135, 136],
			],
			[
				// kitchen
				138 => [127, 147],
				147 => [138, 146, -148],
			],
			[
				// dining
				139 => [126, 140, 146],
				140 => [139, 141],
				141 => [140, 142],
				142 => [123, 141, 143],
				143 => [142, 144],
				144 => [143, 145],
				145 => [144, 146],
				146 => [139, 145, 147],
			],
			[
				// kitchen storage
				148 => [-147, 155],
				155 => [-154, 148],
			],
			[
				// outside right
				152 => [151, 153],
				153 => [152, 154],
				154 => [153, -155],
			],
		];

		// Create spaces
		foreach ($rooms as $roomId => $room) {
			foreach ($room as $location => $neighborIds) {
				if (array_key_exists($location, $this->spaces)) {
					throw new SystemException("Space already exists: $location");
				}
				$this->spaces[$location] = new Space($location, $roomId);
			}
		}

		// Add neighbors
		foreach ($rooms as $roomId => $room) {
			foreach ($room as $location => $neighborIds) {
				$space = $this->spaces[$location];
				foreach ($neighborIds as $neighborId) {
					$neighbor = $this->spaces[abs($neighborId)];
					$space->addNeighbor($neighbor, $neighborId < 0);
				}
			}
		}

		// Create paths
		$path = new Path(
			color: 'blue',
			spaces: [26, 19, 18, 17, 16, 15, 14, 13, 12, 31, 34, 33, 55, 56, 83, 84],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'blue',
			spaces: [26, 19, 18, 17, 16, 15, 14, 13, 12, 31, 34, 33, 55, 56, 83, 84],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'blue',
			spaces: [26, 40, 41, 46, 68, 73, 94, 99, 120, 125, 126, 127, 138, 147],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'blue',
			spaces: [84, 85, 86, 80, 79, 78, 77, 76, 90, 91, 92, 93, 94, 95, 96, 97, 122],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'green',
			spaces: [26, 19, 18, 17, 16, 15, 30, 35, 51, 52, 53, 58, 80, 86, 85, 84],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'green',
			spaces: [26, 40, 41, 46, 68, 73, 94, 93, 100, 101],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'green',
			spaces: [101, 92, 91, 102, 117, 128, 127, 126, 125, 124, 123, 122],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'pink',
			spaces: [84, 85, 108, 111, 112, 113, 114, 115, 116, 117, 102, 91, 92, 101],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'pink',
			spaces: [101, 100, 93, 94, 99, 120, 125, 126, 139, 146, 147],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'red',
			spaces: [26, 25, 24, 42, 45, 69, 70, 71, 96, 97, 122],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'red',
			spaces: [26, 40, 39, 38, 37, 49, 50, 51, 60, 78, 88, 105, 114, 115, 116, 117, 128, 127, 138, 147],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'red',
			spaces: [122, 123, 142, 143, 144, 145, 146, 147],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'yellow',
			spaces: [26, 40, 39, 38, 37, 49, 62, 76, 90, 91, 92, 101],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'yellow',
			spaces: [26, 19, 20, 21, 22, 23, 43, 44, 70, 71, 96, 97, 122],
		);
		$this->paths[$path->path] = $path;

		$path = new Path(
			color: 'yellow',
			spaces: [84, 85, 108, 111, 112, 113, 131, 134, 150, 135, 149, 136, 137, 128, 127, 138,	147],
		);
		$this->paths[$path->path] = $path;

		$this->paths['repeat-1'] = true;
		$this->paths['repeat-2'] = true;
	}

	public function getRoomId(int $spaceId): int
	{
		return $this->spaces[$spaceId]->roomId;
	}

	public function getNovicePossibleMoves(Novice $novice, NunList $nuns, int $round): array
	{
		$actions = $this->getNoviceActions($novice, $round);
		$distance = count($novice->move->spaces);
		if ($novice->caught) {
			$possible = $this->traverse($novice->location, $distance, 25, TRAVERSE_TARGET, [$novice->startLocation => true]);
			$this->game->debug("caught traverse from location " . $novice->location . " to start location " . $novice->startLocation . ": " . json_encode($possible) . " // ");
		} else {
			$maxDistance = $round == 1 ? 10 : 5;
			$flags = $novice->hasKey ? 0 : TRAVERSE_UNLOCKED;
			$targets = [];
			foreach ($nuns as $nun) {
				$targets[$nun->location] = true;
			}
			$possible = $this->traverse($novice->location, $distance, $maxDistance, $flags, $targets);
		}
		foreach ($possible as $location => &$p) {
			$p->actions = $this->getActionsForDistance($actions, $p->distance);
			if ($novice->caught && $location == $novice->startLocation && $p->distance < 3 && empty($p->actions)) {
				$p->actions = ['walk'];
			}
			if (empty($p->actions)) {
				// Ignore impossible moves (distance = 1 on round = 1)
				unset($possible[$location]);
			}
		}
		return $possible;
	}

	public function getNovicePossibleNoise(Novice $novice, NunList $nuns): array
	{
		// Caught novices make no noise
		if ($novice->caught) {
			return [];
		}

		$distance = $novice->move->noiseTotal;
		$nunMode = count($nuns) == 1;
		if ($nunMode) {
			$nun = $nuns->getActiveNun();
			if (array_key_exists($novice->playerId, $nun->move->noiseTokens)) {
				return [];
			}
			$distance = $nun->move->noiseTotal;
		}

		// Check each nun's hearing
		$nunHearing = [];
		$traverse = $this->traverse($novice->location, 0, $distance, TRAVERSE_ZERO);
		foreach ($nuns as $nun) {
			if (array_key_exists($nun->location, $traverse)) {
				// Determine the closest neighbor
				$neighbors = [];
				foreach ($this->spaces[$nun->location]->neighbors as $neighborId => $n) {
					if (array_key_exists($neighborId, $traverse)) {
						$neighbors[$neighborId] = $traverse[$neighborId]->distance;
					}
				}
				if (!empty($neighbors)) {
					$min = min($neighbors);
					foreach ($neighbors as $neighborId => $distance) {
						if ($distance == $min) {
							$nunHearing[$nun->role][$neighborId] = true;
						}
					}
				}
			}
		}

		// Check existing noise tokens
		if (!$nunMode && !empty($novice->move->noiseTokens)) {
			foreach ($novice->move->noiseTokens as $locationId) {
				foreach ($nunHearing as $role => $x) {
					if (array_key_exists($locationId, $nunHearing[$role])) {
						unset($nunHearing[$role]);
					}
				}
			}
		}

		// Reformat by location ID
		$possible = [];
		foreach ($nunHearing as $role => $locations) {
			foreach ($locations as $locationId => $x) {
				$possible[$locationId][] = $role;
			}
		}
		return $possible;
	}

	public function getNoviceActions(Novice $novice, int $round): array
	{
		// TODO: game option
		// "If the novices are winning too easily, you can give them a handicap. In the
		// first round, the novices may only move once (instead of the usual two times)."

		$multi = $round == 1 ? 2 : 1;
		$actions = [
			'stand' => [
				'min' => 0,
				'max' => 0,
				'name' => \clienttranslate('Stand'),
				'noise' => -3,
			],
			'sneak' => [
				'min' => 1 * $multi,
				'max' => 2 * $multi,
				'name' => \clienttranslate('Sneak'),
				'noise' => -2,
			],
			'walk' => [
				'min' => 3 * $multi,
				'max' => 4 * $multi,
				'name' => \clienttranslate('Walk'),
				'noise' => -1,
			],
			'run' => [
				'min' => 1 * $multi,
				'max' => 5 * $multi,
				'name' => \clienttranslate('Run'),
				'noise' => 1,
			],
		];
		if ($novice->caught) {
			$actions = array_intersect_key($actions, ['walk' => true]);
		}
		return $actions;
	}

	public function getNunActions(): array
	{
		// TODO: game option
		// "If an experienced nun player is playing against a few inexperienced
		// novices,you can give the nun player a handicap. The nun player may move
		// a maximum of 5 spaces per round."

		return [
			'walk' => [
				'min' => 3,
				'max' => 4,
				'name' => \clienttranslate('Walk'),
				'noise' => true,
			],
			'run' => [
				'min' => 5,
				'max' => 6,
				'name' => \clienttranslate('Run'),
				'noise' => false,
			],
		];
	}

	public function getActionsForDistance(array $actions, int $distance): array
	{
		$actionsForNow = [];
		foreach ($actions as $action => $info) {
			if ($distance >= $info['min'] && $distance <= $info['max']) {
				$actionsForNow[] = $action;
			}
		}
		return $actionsForNow;
	}

	public function getNunPossiblePaths(NunList $nuns, Nun $nun): array
	{
		$possible = [];
		$used = $nuns->getPathsUsed();
		foreach ($this->paths as $pathId => $path) {
			if (array_key_exists($pathId, $used)) {
				// Ignore used paths
				continue;
			}
			if ($path instanceof Path) {
				if ($nun->location == $path->origin) {
					$possible[$pathId] = $path;
				} else if ($nun->location == $path->destination) {
					$possible[$pathId] = $path->reverse();
				}
			} else if ($nun->path != null) {
				$possible[$pathId] = $nun->path->reverse();
				// Only keep the first 'repeat-x'
				break;
			}
		}

		// Sort by destination, color
		uasort($possible, function (Path $a, Path $b) {
			return ($a->destination <=> $b->destination)
				?? ($a->color <=> $b->color);
		});
		return $possible;
	}

	public function getNunPossibleMoves(Nun $nun): array
	{
		$distance = count($nun->move->spaces);
		$maxDistance = 6;

		$possible = $this->traverse($nun->location, $distance, $maxDistance, TRAVERSE_SINGLE_ROOM);
		if (!$nun->move->deviate) {
			// Get the nun's path, with the destination at the end
			$pathSpaces = $nun->path->spaces;
			$onPath = array_search($nun->location, $pathSpaces);
			$this->game->debug("Nun $nun onPath: $onPath // ");
			if ($onPath === false) {
				// Nun has left the path
				// Find the closest path space
				$this->game->debug("$nun path: " . json_encode($pathSpaces) . " // ");
				$this->game->debug("$nun path flip: " . json_encode(array_flip($pathSpaces)) . " // ");
				$traverse = $this->traverse($nun->location, 0, 25, TRAVERSE_TARGET, array_flip($pathSpaces));
				$this->game->debug("$nun traverse target to path: " . json_encode($traverse) . " // ");
				if (!empty($traverse)) {
					$this->game->debug("$nun possible normal: " . json_encode($possible) . " // ");
					$possible = array_intersect_key($possible, $traverse);
					$this->game->debug("$nun possible intersect: " . json_encode($possible) . " // ");
				} else {
					throw new SystemException("$nun has left the path and has no way back!");
				}
			} else {
				// Nun is on the path
				// Ignore prior path spaces
				$pathSpaces = array_slice($pathSpaces, $onPath);
				foreach ($possible as $location => &$p) {
					if (!in_array($location, $pathSpaces)) {
						unset($possible[$location]);
					}
				}
			}
		}
		$actions = $this->getNunActions();
		foreach ($possible as $location => &$p) {
			$p->actions = $this->getActionsForDistance($actions, $p->distance);
		}

		return $possible;
	}

	public function getNunDeviate(Nun $nun, NoviceList $novices): bool
	{
		// https://boardgamegeek.com/thread/3764004/continue-movement-after-catching-a-novice-return-t
		// "after a catch, the nun must use the remaining dots to return to the assigned route"
		// (unless she still sees an uncaught novice)
		$start = empty($nun->move->spaces);
		$neighbors = array_keys($this->spaces[$nun->location]->neighbors);

		// A nun can leave the path if:
		// - A nun noise token is adjacent (at start of turn)
		if ($start && !empty($nun->move->noiseTokens) && array_intersect($nun->move->noiseTokens, $neighbors)) {
			$this->game->debug("$nun can deviate from path {$nun->path->path} because a nun noise token is adjacent // ");
			return true;
		}

		foreach ($novices as $novice) {
			// - An uncaught novice is in this room (at any time)
			if (!$novice->caught && $novice->room == $nun->room) {
				$this->game->debug("$nun can deviate from path {$nun->path->path} because uncaught novice {$novice->playerId} is in the room // ");
				return true;
			}

			// - A novice vanish token is in this room (at start of turn)
			if ($start && !empty($novice->move->vanishTokens) && !empty(array_intersect($novice->move->vanishTokens, [$nun->room]))) {
				$this->game->debug("$nun can deviate from path {$nun->path->path} because novice {$novice->playerId} vanish token is in the room // ");
				return true;
			}

			// - A novice noise token is adjacent (at start of turn)
			if ($start && !empty($novice->move->noiseTokens) && !empty(array_intersect($novice->move->noiseTokens, $neighbors))) {
				$this->game->debug("$nun can deviate from path {$nun->path->path} because novice {$novice->playerId} noise token is adjacent // ");
				return true;
			}
		}

		// Otherwise, the nun must follow the path 
		$this->game->debug("$nun cannot deviate from path {$nun->path->path} // ");
		return false;
	}

	private function traverse(int $start, int $distance, int $maxDistance, int $flags = 0, array $targets = []): array
	{
		$possible = [];
		if ($maxDistance <= 0) {
			return $possible;
		}
		$startRoom = $this->getRoomId($start);
		$queue = [new PossibleMove($distance, [], $start, [])];
		$visited = [];
		while (!empty($queue)) {
			$nextQueue = [];
			foreach ($queue as $move) {
				$location = $move->location;
				$distance = $move->distance;
				if ($distance > $maxDistance) {
					continue;
				}
				if (array_key_exists($location, $visited)) {
					// Don't reprocess the same space
					continue;
				}
				$visited[$location] = true;
				if (!array_key_exists($location, $possible) || $distance < $possible[$location]->distance) {
					$possible[$location] = $move;
				}
				$space = $this->spaces[$location];
				$room = $space->roomId;
				foreach ($space->neighbors as $neighborId => $neighbor) {
					if (in_array($neighborId, $move->spaces)) {
						// Ignore backtracking
						continue;
					}
					if ($flags & TRAVERSE_UNLOCKED && $neighbor['locked']) {
						// Ignore locked doors
						continue;
					}
					if (array_key_exists($neighborId, $targets)) {
						if ($flags & TRAVERSE_TARGET) {
							$maxDistance = min($distance + 1, $maxDistance);
							$this->game->debug("TRAVERSE_TARGET got to $neighborId in maxDistance = $maxDistance via " . json_encode($move->spaces) . " // ");
						} else {
							// Ignore target spaces
							continue;
						}
					}
					if ($flags & TRAVERSE_SINGLE_ROOM && $room != $startRoom) {
						// Ignore new rooms
						continue;
					}
					$nextQueue[] = new PossibleMove($distance + 1, [], $neighborId, $move->spaces);
				}
			}
			$queue = $nextQueue;
		}
		if (!($flags & TRAVERSE_ZERO)) {
			unset($possible[$start]);
		}
		if ($flags & TRAVERSE_TARGET) {
			$this->game->debug('TRAVERSE_TARGET possible = ' . json_encode($possible) . ' // ');
			$keeps = [];
			foreach ($targets as $target => $x) {
				$keeps[] = $target;
				if (array_key_exists($target, $possible)) {
					array_push($keeps, ...$possible[$target]->spaces);
				}
			}
			$keeps = array_flip($keeps);
			$this->game->debug('TRAVERSE_TARGET keeps = ' . json_encode($keeps) . ' // ');
			$possible = array_intersect_key($possible, $keeps);
		}
		return $possible;
	}
}

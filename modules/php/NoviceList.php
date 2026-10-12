<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\States\NoviceTurnMultiState;

class NoviceList implements \Countable, \IteratorAggregate, \JsonSerializable
{
	/**
	 * @var array<int, Novice>
	 */
	private array $novices = [];

	public function __construct(
		array $novices = [],
	) {
		$this->novices = $novices;
	}

	public static function fromData(?\stdClass $data): NoviceList
	{
		$novices = [];
		if ($data) {
			foreach ($data as $playerId => $novice) {
				$novices[$playerId] = Novice::fromData($novice);
			}
		}
		return new NoviceList(
			novices: $novices,
		);
	}

	public function jsonSerialize(): array
	{
		return $this->novices;
	}

	public function count(): int
	{
		return count($this->novices);
	}

	/**
	 * @return \Traversable<int, Novice>
	 */
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->novices);
	}

	/**
	 * @return array<int, Novice>
	 */
	public function getAllDatas(int $currentPlayerId, GameState $state, NunList $nuns): array
	{
		$output = [];
		$gameEnd = $currentPlayerId == -1 || $state->name == 'gameEnd';
		$roomsVisible = $nuns->getRoomsVisible();
		foreach ($this->novices as $playerId => $novice) {
			$json = json_decode(json_encode($novice), true);
			unset($json['room']);
			if (!$gameEnd) {
				unset($json['moves']);
			} else {
				foreach ($json['moves'] as &$move) {
					unset(
						$move['active'],
						$move['deviate'],
						$move['startRoom'],
						$move['undo'],
					);
				}
			}
			if (!$gameEnd && $playerId != $currentPlayerId) {
				unset(
					$json['hasKey'],
					$json['hasWish'],
					$json['keyLocation'],
					$json['wish'],
					$json['wishLocation']
				);
				$room = $novice->room;
				if ($state instanceof NoviceTurnMultiState) {
					$room = $novice->move->startRoom;
					$json['caught'] = $novice->move->caught;
					$json['location'] = $novice->move->start;
					unset($json['move']);
				}
				$visible = array_key_exists($room, $roomsVisible);
				if (!$visible) {
					$json['location'] = $json['startLocation'];
				}
			}
			if (array_key_exists('move', $json) && $json['move'] != null) {
				unset(
					$json['move']['active'],
					$json['move']['caught'],
					$json['move']['caughtHistory'],
					$json['move']['deviate'],
					$json['move']['startRoom'],
					$json['move']['undo'],
				);
			}
			$output[$playerId] = $json;
		}
		return $output;
	}

	public function add(Novice &$novice): void
	{
		$this->novices[$novice->playerId] = $novice;
	}

	public function &get(int $playerId): ?Novice
	{
		return $this->novices[$playerId];
	}

	/**
	 * @return array<int>
	 */
	public function getPlayerIds(): array
	{
		$playerIds = [];
		foreach ($this->novices as $novice) {
			$playerIds[$novice->playerId] = true;
		}
		return array_keys($playerIds);
	}

	/**
	 * @return array<int, array<int>>
	 */
	public function getLocationsWithNovices(): array
	{
		$locations = [];
		foreach ($this->novices as $playerId => $novice) {
			if (!$novice->caught && $novice->location != $novice->startLocation) {
				$locations[$novice->location][] = $playerId;
			}
		}
		return $locations;
	}
}

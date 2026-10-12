<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\States\NunNoiseMultiState;

class NunList implements \Countable, \IteratorAggregate, \JsonSerializable
{
	/**
	 * @var array<string, Nun>
	 */
	private array $nuns;

	public function __construct(
		array $nuns = []
	) {
		$this->nuns = $nuns;
	}

	public static function fromData(?\stdClass $data): NunList
	{
		$nuns = [];
		if ($data) {
			foreach ($data as $role => $nun) {
				$nuns[$role] = Nun::fromData($nun);
			}
		}
		return new NunList(
			nuns: $nuns,
		);
	}

	public function jsonSerialize(): array
	{
		return $this->nuns;
	}

	public function count(): int
	{
		return count($this->nuns);
	}

	/**
	 * @return \Traversable<string, Nun>
	 */
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->nuns);
	}

	/**
	 * @return array<string, Nun>
	 */
	public function getAllDatas(int $currentPlayerId, GameState $state): array
	{
		$output = [];
		$gameEnd = $currentPlayerId == -1 || $state->name == 'gameEnd';
		foreach ($this->nuns as $role => $nun) {
			$json = json_decode(json_encode($nun), true);
			unset(
				$json['paths'],
				$json['room']
			);
			if (!$gameEnd) {
				unset($json['moves']);
			} else {
				foreach ($json['moves'] as &$move) {
					unset(
						$move['active'],
						$move['caught'],
						$move['startRoom'],
						$move['undo'],
					);
				}
			}
			if ($state instanceof NunNoiseMultiState && $nun->move != null && $nun->move->active) {
				$json['move']['tokens'] = array_filter($json['move']['tokens'], fn($token) => $token['playerId'] == $currentPlayerId);
			}
			if (array_key_exists('move', $json) && $json['move'] != null) {
				unset(
					$json['move']['caught'],
					$json['move']['caughtHistory'],
					$json['move']['startCaught'],
					$json['move']['startRoom'],
					$json['move']['undo'],
				);
			}
			$output[$role] = $json;
		}
		return $output;
	}

	public function add(Nun &$nun): void
	{
		$this->nuns[$nun->role] = $nun;
	}

	public function &get(string $role): Nun
	{
		if (!array_key_exists($role, $this->nuns)) {
			throw new SystemException("Nun not found for role: $role");
		}
		return $this->nuns[$role];
	}

	public function &getActiveNun(): ?Nun
	{
		foreach ($this->nuns as &$nun) {
			if ($nun->move->active) {
				return $nun;
			}
		}
		return null;
	}

	/**
	 * @return array<string>
	 */
	public function getChoices(): array
	{
		$choices = [];
		foreach ($this->nuns as $nun) {
			if (!$nun->move->action && $nun->move->action == null) {
				$choices[] = $nun->role;
			}
		}
		return $choices;
	}

	/**
	 * @return array<string, bool>
	 */
	public function getPathsUsed(): array
	{
		$paths = [];
		foreach ($this->nuns as $nun) {
			foreach ($nun->paths as $path) {
				$paths[$path] = true;
			}
		}
		return $paths;
	}

	/**
	 * @return array<int>
	 */
	public function getPlayerIds(): array
	{
		$playerIds = [];
		foreach ($this->nuns as $nun) {
			$playerIds[$nun->playerId] = true;
		}
		return array_keys($playerIds);
	}

	/**
	 * @return array<int, bool>
	 */
	public function getRoomsVisible(): array
	{
		$roomIds = [];
		foreach ($this->nuns as $nun) {
			$roomIds[$nun->room] = true;
		}
		return $roomIds;
	}

	/**
	 * @return array<int, bool>
	 */
	public function getNovicesVisible(NoviceList $novices): array
	{
		$visible = [];
		$rooms = $this->getRoomsVisible();
		foreach ($novices as $novice) {
			$visible[$novice->playerId] = array_key_exists($novice->room, $rooms);
		}
		return $visible;
	}

	public function isRoomVisible(int $roomId): bool
	{
		return array_key_exists($roomId, $this->getRoomsVisible());
	}

	public function isNoviceVisible(Novice $novice): bool
	{
		return $this->isRoomVisible($novice->room);
	}
}

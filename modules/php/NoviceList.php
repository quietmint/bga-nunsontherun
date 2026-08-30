<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\States\NoviceTurnMultiState;

class NoviceList implements \IteratorAggregate, \JsonSerializable
{
	private array $novices = [];

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			foreach ($data as $playerId => $novice) {
				$this->novices[$playerId] = new Novice($novice);
			}
		}
	}

	public function jsonSerialize(): array
	{
		return $this->novices;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->novices);
	}

	public function getAllDatas(int $currentPlayerId, GameState $state, NunList $nuns): array
	{
		$output = [];
		$visible = $nuns->getNovicesVisible($this);
		foreach ($this->novices as $playerId => $novice) {
			$json = json_decode(json_encode($novice), true);
			unset($json['moves']);
			if ($playerId != $currentPlayerId) {
				unset($json['hasKey'], $json['hasWish'], $json['room'], $json['wish']);
				if ($state instanceof NoviceTurnMultiState) {
					unset($json['move']);
				}
				if (!$visible[$playerId]) {
					$json['location'] = $json['startLocation'];
				}
			}
			$output[$playerId] = $json;
		}
		return $output;
	}

	public function add(Novice &$novice): void
	{
		$this->novices[$novice->playerId] = $novice;
	}

	public function &get(int $playerId): Novice
	{
		if (!array_key_exists($playerId, $this->novices)) {
			throw new SystemException("Novice not found for playerId: $playerId");
		}
		return $this->novices[$playerId];
	}

	public function getLocationsWithNovices(): array
	{
		$locations = [];
		foreach ($this->novices as $playerId => $novice) {
			$locations[$novice->location][] = $playerId;
		}
		return $locations;
	}
}

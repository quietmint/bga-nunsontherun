<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\States\NoviceTurnMultiState;

class NoviceList implements \Countable, \IteratorAggregate, \JsonSerializable
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

	public function count(): int
	{
		return count($this->novices);
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->novices);
	}

	public function getAllDatas(int $currentPlayerId, GameState $state, NunList $nuns): array
	{
		$output = [];
		$gameEnd = $currentPlayerId == -1 || $state->name == 'gameEnd';
		$visible = $nuns->getNovicesVisible($this);
		foreach ($this->novices as $playerId => $novice) {
			$json = json_decode(json_encode($novice), true);
			if (!$gameEnd) {
				unset($json['moves']);
			} else {
				foreach ($json['moves'] as &$move) {
					unset(
						$move['active'],
						$move['deviate'],
						$move['noiseTokens'],
						$move['undo'],
						$move['vanishTokens']
					);
				}
			}
			if (!$gameEnd && $playerId != $currentPlayerId) {
				unset($json['hasKey'], $json['hasWish'], $json['keyLocation'], $json['room'], $json['wish'], $json['wishLocation']);
				if ($state instanceof NoviceTurnMultiState) {
					unset($json['move']);
				}
				if (!$visible[$playerId]) {
					$json['location'] = $json['startLocation'];
				}
			}
			if (array_key_exists('move', $json) && $json['move'] != null) {
				unset(
					$json['move']['active'],
					$json['move']['caught'],
					$json['move']['caughtHistory'],
					$json['move']['deviate'],
					$json['move']['noiseHistory'],
					$json['move']['undo'],
					$json['move']['vanishHistory'],
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
			if (!$novice->caught) {
				$locations[$novice->location][] = $playerId;
			}
		}
		return $locations;
	}
}

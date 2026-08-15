<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\SystemException;

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

	public function add(Novice $novice): void
	{
		$this->novices[$novice->playerId] = $novice;
	}

	public function get(int $playerId): Novice
	{
		if (!array_key_exists($playerId, $this->novices)) {
			throw new SystemException("Novice not found for playerId: $playerId");
		}
		return $this->novices[$playerId];
	}
}

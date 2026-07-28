<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Space
{
	public int $spaceId;
	public int $roomId;
	public array $neighbors = [];

	public function __construct(int $spaceId, int $roomId)
	{
		$this->spaceId = $spaceId;
		$this->roomId = $roomId;
	}

	public function __toString(): string
	{
		return "Space({$this->spaceId})";
	}

	public function addNeighbor(Space $other, bool $locked)
	{
		$this->neighbors[$other->spaceId] = [
			'space' => $other,
			'locked' => $locked
		];
		$other->neighbors[$this->spaceId] = [
			'space' => $this,
			'locked' => $locked
		];
	}
}

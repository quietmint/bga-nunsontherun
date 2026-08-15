<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Space
{
	public int $location;
	public int $roomId;
	public array $neighbors = [];

	public function __construct(int $location, int $roomId)
	{
		$this->location = $location;
		$this->roomId = $roomId;
	}

	public function __toString(): string
	{
		return "Space({$this->location})";
	}

	public function addNeighbor(Space $other, bool $locked)
	{
		$this->neighbors[$other->location] = [
			'space' => $other,
			'locked' => $locked
		];
		$other->neighbors[$this->location] = [
			'space' => $this,
			'locked' => $locked
		];
	}
}

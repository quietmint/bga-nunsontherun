<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class PossibleMove
{
	public int $distance;
	public int $location;
	public array $path;

	public function __construct(int $distance, int $location, array $path)
	{
		$this->distance = $distance;
		$this->location = $location;
		$this->path = $path;
		$this->path[] = $location;
	}

	public function __toString()
	{
		return 'PossibleMove(' . $this->distance . '=' . join('>', $this->path) . ')';
	}
}

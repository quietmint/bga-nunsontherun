<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class PossibleMove
{
	public int $distance;
	public int $location;
	public array $spaces;

	public function __construct(int $distance, int $location, array $spaces)
	{
		$this->distance = $distance;
		$this->location = $location;
		$this->spaces = $spaces;
		$this->spaces[] = $location;
	}

	public function __toString()
	{
		return 'PossibleMove(' . $this->distance . '=' . join('>', $this->spaces) . ')';
	}
}

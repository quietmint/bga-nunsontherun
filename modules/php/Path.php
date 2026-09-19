<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Path
{
	public string $color;
	public int $destination;
	public int $origin;
	public string $path;
	public array $spaces;

	public function __construct(
		string $color,
		array $spaces,
		?int $destination = null,
		?int $origin = null,
		?string $path = null,
	) {
		if (!$origin) {
			$origin = $spaces[0];
		}
		if (!$destination) {
			$destination = $spaces[count($spaces) - 1];
		}
		if (!$path) {
			$path = "{$color}-{$origin}-{$destination}";
		}

		$this->color = $color;
		$this->destination = $destination;
		$this->origin = $origin;
		$this->path = $path;
		$this->spaces = $spaces;
	}

	public static function fromData(\stdClass $data): Path
	{
		return new Path(
			color: $data->color,
			destination: $data->destination,
			origin: $data->origin,
			path: $data->path,
			spaces: $data->spaces,
		);
	}

	public function reverse(): Path
	{
		return new Path(
			color: $this->color,
			destination: $this->origin,
			origin: $this->destination,
			path: $this->path,
			spaces: array_reverse($this->spaces),
		);
	}
}

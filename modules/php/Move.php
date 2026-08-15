<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move implements \JsonSerializable
{
	public ?string $move = null;
	public array $spaces = [];
	public int $start;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->move = $data->move;
			$this->spaces = $data->spaces;
			$this->start = $data->start;
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'move' => $this->move,
			'spaces' => $this->spaces,
			'start' => $this->start,
		];
	}
}

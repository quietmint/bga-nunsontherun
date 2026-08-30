<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Nun
{
	public string $color;
	public int $location;
	public ?Move $move = null;
	public array $moves = [];
	public int $playerId;
	public string $playerName;
	public string $role;
	public ?string $path = null;
	public array $paths = [];
	public int $room;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->color = $data->color;
			$this->location = $data->location;
			$this->move = property_exists($data, 'move') && !is_null($data->move) ? new Move($data->move) : null;
			$this->path = property_exists($data, 'path') && !is_null($data->path) ? $data->path : null;
			$this->paths = $data->paths;
			$this->playerId = $data->playerId;
			$this->playerName = $data->playerName;
			$this->role = $data->role;
			$this->room = $data->room;
			foreach ($data->moves as $move) {
				array_push($this->moves, new Move($move));
			}
		}
	}

	public function __toString()
	{
		return 'Nun(' . $this->playerId . '/' . $this->role . ')';
	}
}

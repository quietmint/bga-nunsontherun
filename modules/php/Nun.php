<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Nun implements \JsonSerializable
{
	public string $color;
	public int $location;
	// public ?Path $path = null;
	public ?Move $move = null;
	public array $moves = [];
	public int $playerId;
	public string $playerName;
	public string $role;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->color = $data->color;
			$this->location = $data->location;
			$this->move = property_exists($data, 'move') && !is_null($data->move) ? new Move($data->move) : null;
			$this->playerId = $data->playerId;
			$this->playerName = $data->playerName;
			$this->role = $data->role;
			// foreach ($data->moves as $move) {
			// 	array_push($this->moves, new Move($move));
			// }
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'color' => $this->color,
			'location' => $this->location,
			'move' => $this->move,
			'moves' => $this->moves,
			'playerId' => $this->playerId,
			'playerName' => $this->playerName,
			'role' => $this->role,
		];
	}
}

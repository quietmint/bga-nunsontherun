<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Nun
{
	public ?string $blessing;
	public string $color;
	public int $location;
	public ?Move $move;
	public array $moves;
	public ?Path $path;
	public array $paths;
	public int $playerId;
	public string $playerName;
	public string $role;
	public int $room;

	public string $roleName {
		&get {
			$roleName = $this->role == 'abbess' ? clienttranslate('Abbess') : clienttranslate('Prioress');
			return $roleName;
		}
	}

	public function __construct(
		string $color,
		int $location,
		Move $move,
		int $playerId,
		string $playerName,
		string $role,
		int $room,
		?string $blessing = null,
		array $moves = [],
		?Path $path = null,
		array $paths = [],
	) {
		$this->blessing = $blessing;
		$this->color = $color;
		$this->location = $location;
		$this->move = $move;
		$this->moves = $moves;
		$this->path = $path;
		$this->paths = $paths;
		$this->playerId = $playerId;
		$this->playerName = $playerName;
		$this->role = $role;
		$this->room = $room;
	}

	public static function fromData(\stdClass $data): Nun
	{
		$moves = [];
		foreach ($data->moves as $move) {
			$moves[] = Move::fromData($move);
		}
		return new Nun(
			blessing: $data->blessing,
			color: $data->color,
			location: $data->location,
			move: Move::fromData($data->move),
			moves: $moves,
			path: property_exists($data, 'path') && !is_null($data->path) ? Path::fromData($data->path) : null,
			paths: $data->paths,
			playerId: $data->playerId,
			playerName: $data->playerName,
			role: $data->role,
			room: $data->room,
		);
	}

	public function __toString()
	{
		return 'Nun(' . $this->playerId . '/' . $this->role . ')';
	}
}

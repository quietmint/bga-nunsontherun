<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Nun
{
	public string $color;
	public int $location;
	public ?Move $move;
	public array $moves = [];
	public ?string $path;
	public ?string $pathColor;
	public ?int $pathDestination;
	public ?int $pathOrigin;
	public array $paths = [];
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

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->color = $data->color;
			$this->location = $data->location;
			$this->move = property_exists($data, 'move') && !is_null($data->move) ? new Move($data->move) : null;
			$this->path = property_exists($data, 'path') ? $data->path : null;
			$this->pathColor = property_exists($data, 'pathColor') ? $data->pathColor : null;
			$this->pathDestination = property_exists($data, 'pathDestination') ? $data->pathDestination : null;
			$this->pathOrigin = property_exists($data, 'pathOrigin') ? $data->pathOrigin : null;
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

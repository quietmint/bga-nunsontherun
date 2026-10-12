<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Token
{
	public bool $history;
	public string $type;
	public int $room;
	public int $location;
	public int $playerId;

	public function __construct(
		int $location,
		int $playerId,
		int $room,
		string $type,
		bool $history = false,
	) {
		$this->history = $history;
		$this->location = $location;
		$this->playerId = $playerId;
		$this->room = $room;
		$this->type = $type;
	}

	public static function fromData(\stdClass $data): Token
	{
		return new Token(
			history: $data->history,
			location: $data->location,
			playerId: $data->playerId,
			room: $data->room,
			type: $data->type,
		);
	}
}

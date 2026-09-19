<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Novice
{
	public ?string $blessing;
	public bool $caught;
	public string $color;
	public bool $hasKey;
	public bool $hasWish;
	public int $location;
	public ?Move $move;
	public array $moves;
	public int $playerId;
	public string $playerName;
	public int $room;
	public int $startLocation;
	public string $wish;

	public ?int $keyLocation {
		&get {
			$keyLocation = null;
			if ($this->wish == 'dessert') {
				$keyLocation = 36;
			} else if ($this->wish == 'game') {
				$keyLocation = 67;
			} else if ($this->wish == 'letter') {
				$keyLocation = 72;
			} else if ($this->wish == 'magazine') {
				$keyLocation = 107;
			} else if ($this->wish == 'makeup') {
				$keyLocation = 130;
			} else if ($this->wish == 'perfume') {
				$keyLocation = 149;
			} else if ($this->wish == 'phone') {
				$keyLocation = 82;
			} else if ($this->wish == 'wine') {
				$keyLocation = 36;
			}
			return $keyLocation;
		}
	}

	public ?int $wishLocation {
		&get {
			$wishLocation = null;
			if ($this->wish == 'dessert') {
				$wishLocation = 148;
			} else if ($this->wish == 'game') {
				$wishLocation = 109;
			} else if ($this->wish == 'letter') {
				$wishLocation = 110;
			} else if ($this->wish == 'magazine') {
				$wishLocation = 119;
			} else if ($this->wish == 'makeup') {
				$wishLocation = 121;
			} else if ($this->wish == 'perfume') {
				$wishLocation = 121;
			} else if ($this->wish == 'phone') {
				$wishLocation = 118;
			} else if ($this->wish == 'wine') {
				$wishLocation = 155;
			}
			return $wishLocation;
		}
	}

	public function __construct(
		string $color,
		int $location,
		Move $move,
		int $playerId,
		string $playerName,
		int $room,
		int $startLocation,
		string $wish,
		?string $blessing = null,
		bool $caught = false,
		bool $hasKey = false,
		bool $hasWish = false,
		array $moves = [],
	) {
		$this->blessing = $blessing;
		$this->caught = $caught;
		$this->color = $color;
		$this->hasKey = $hasKey;
		$this->hasWish = $hasWish;
		$this->location = $location;
		$this->move = $move;
		$this->moves = $moves;
		$this->playerId = $playerId;
		$this->playerName = $playerName;
		$this->room = $room;
		$this->startLocation = $startLocation;
		$this->wish = $wish;
	}

	public static function fromData(\stdClass $data): Novice
	{
		$moves = [];
		foreach ($data->moves as $move) {
			$moves[] = Move::fromData($move);
		}
		return new Novice(
			blessing: $data->blessing,
			caught: $data->caught,
			color: $data->color,
			hasKey: $data->hasKey,
			hasWish: $data->hasWish,
			location: $data->location,
			move: Move::fromData($data->move),
			moves: $moves,
			playerId: $data->playerId,
			playerName: $data->playerName,
			room: $data->room,
			startLocation: $data->startLocation,
			wish: $data->wish,
		);
	}

	public function __toString()
	{
		return 'Novice(' . $this->playerId . ')';
	}
}

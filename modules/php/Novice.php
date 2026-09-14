<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Novice
{
	public ?string $blessing = null;
	public bool $caught = false;
	public string $color;
	public bool $hasKey = false;
	public bool $hasWish = false;
	public int $location;
	public ?Move $move;
	public array $moves = [];
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

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->blessing = property_exists($data, 'blessing') ? $data->blessing : null;
			$this->caught = $data->caught;
			$this->color = $data->color;
			$this->hasKey = $data->hasKey;
			$this->hasWish = $data->hasWish;
			$this->location = $data->location;
			$this->move = property_exists($data, 'move') && !is_null($data->move) ? new Move($data->move) : null;
			$this->playerId = $data->playerId;
			$this->playerName = $data->playerName;
			$this->room = $data->room;
			$this->startLocation = $data->startLocation;
			$this->wish = $data->wish;
			foreach ($data->moves as $move) {
				array_push($this->moves, new Move($move));
			}
		}
	}

	public function __toString()
	{
		return 'Novice(' . $this->playerId . ')';
	}
}

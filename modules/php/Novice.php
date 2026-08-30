<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Novice
{
	public bool $caught = false;
	public string $color;
	public bool $hasKey = false;
	public bool $hasWish = false;
	public int $location;
	public ?Move $move = null;
	public array $moves = [];
	public int $playerId;
	public string $playerName;
	public int $room;
	public int $startLocation;
	public string $wish;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
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

	public function getKeyLocation(): ?int
	{
		switch ($this->wish) {
			case 'dessert':
				return 36;
			case 'game':
				return 67;
			case 'letter':
				return 72;
			case 'magazine':
				return 107;
			case 'makeup':
				return 130;
			case 'perfume':
				return 149;
			case 'phone':
				return 82;
			case 'wine':
				return 36;
			default:
				return null;
		}
	}

	public function getWishLocation(): ?int
	{
		switch ($this->wish) {
			case 'dessert':
				return 148;
			case 'game':
				return 109;
			case 'letter':
				return 110;
			case 'magazine':
				return 119;
			case 'makeup':
				return 121;
			case 'perfume':
				return 121;
			case 'phone':
				return 118;
			case 'wine':
				return 155;
			default:
				return null;
		}
	}
}

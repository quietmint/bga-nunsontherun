<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Novice implements \JsonSerializable
{
	public string $color;
	public bool $hasKey = false;
	public bool $hasWish = false;
	public int $location;
	public ?Move $move = null;
	public array $moves;
	public int $playerId;
	public string $playerName;
	public string $wish;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->color = $data->color;
			$this->hasKey = property_exists($data, 'hasKey') && $data->hasKey;
			$this->hasWish = property_exists($data, 'hasWish') && $data->hasWish;
			$this->location = $data->location;
			$this->move = property_exists($data, 'move') && !is_null($data->move) ? new Move($data->move) : null;
			$this->playerId = $data->playerId;
			$this->playerName = $data->playerName;
			$this->wish = $data->wish;
		}
	}

	public function __toString()
	{
		return 'Novice(' . $this->playerId . ')';
	}

	public function jsonSerialize(): array
	{
		return [
			'color' => $this->color,
			'hasKey' => $this->hasKey,
			'hasWish' => $this->hasWish,
			'keyLocation' => $this->getKeyLocation(),
			'location' => $this->location,
			'move' => $this->move,
			'playerId' => $this->playerId,
			'playerName' => $this->playerName,
			'wish' => $this->wish,
			'wishLocation' => $this->getWishLocation(),
		];
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

	public function getCurrentDistance(): int
	{
		return 0;
	}

	public function getMaxDistance(): int
	{
		return 10;
	}
}

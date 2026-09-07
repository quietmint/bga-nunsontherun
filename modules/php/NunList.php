<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\SystemException;

class NunList implements \IteratorAggregate, \JsonSerializable
{
	private array $nuns = [];

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			foreach ($data as $role => $nun) {
				$this->nuns[$role] = new Nun($nun);
			}
		}
	}

	public function jsonSerialize(): array
	{
		return $this->nuns;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->nuns);
	}

	public function getAllDatas(int $currentPlayerId, GameState $state): array
	{
		$output = [];
		foreach ($this->nuns as $role => $nun) {
			$json = json_decode(json_encode($nun), true);
			unset($json['moves'], $json['paths'], $json['room']);
			$output[$role] = $json;
		}
		return $output;
	}

	public function add(Nun &$nun): void
	{
		$this->nuns[$nun->role] = $nun;
	}

	public function &get(string $role): Nun
	{
		if (!array_key_exists($role, $this->nuns)) {
			throw new SystemException("Nun not found for role: $role");
		}
		return $this->nuns[$role];
	}

	public function &getActiveNun(): ?Nun
	{
		foreach ($this->nuns as &$nun) {
			if ($nun->move != null && $nun->move->active) {
				return $nun;
			}
		}
		return null;
	}

	public function &getInactiveNun(): ?Nun
	{
		foreach ($this->nuns as &$nun) {
			if ($nun->move == null) {
				return $nun;
			}
		}
		return null;
	}

	public function getChoices(): array
	{
		$choices = [];
		foreach ($this->nuns as $nun) {
			if ($nun->move == null) {
				$choices[] = $nun->role;
			}
		}
		return $choices;
	}

	public function getRoomsVisible(): array
	{
		$roomIds = [];
		foreach ($this->nuns as $nun) {
			$roomIds[$nun->room] = true;
		}
		return $roomIds;
	}

	public function getNovicesVisible(NoviceList $novices): array
	{
		$visible = [];
		$rooms = $this->getRoomsVisible();
		foreach ($novices as $novice) {
			$visible[$novice->playerId] = array_key_exists($novice->room, $rooms);
		}
		return $visible;
	}

	public function isRoomVisible(int $roomId): bool
	{
		return array_key_exists($roomId, $this->getRoomsVisible());
	}

	public function isNoviceVisible(Novice $novice): bool
	{
		return $this->isRoomVisible($novice->room);
	}
}

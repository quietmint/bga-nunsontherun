<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

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

	public function add(Nun $nun): void
	{
		$this->nuns[$nun->role] = $nun;
	}

	public function get(string $role): ?Nun
	{
		return array_key_exists($role, $this->nuns) ? $this->nuns[$role] : null;
	}

	public function getChoices(): array
	{
		$choices = [];
		if ($this->nuns['abbess']->move == null) {
			$choices[] = 'abbess';
		}
		if ($this->nuns['prioress']->move == null) {
			$choices[] = 'prioress';
		}
		return $choices;
	}

	public function getCurrentRole(): ?string
	{
		if ($this->nuns['abbess']->move != null && $this->nuns['abbess']->move->current) {
			return 'abbess';
		} else if ($this->nuns['prioress']->move != null && $this->nuns['prioress']->move->current) {
			return 'prioress';
		} else {
			return null;
		}
	}

	public function getCurrentNun(): ?Nun
	{
		return $this->get($this->getCurrentRole());
	}

	public function getRoomIds(): array
	{
		$roomIds = [];
		foreach ($this->nuns as $nun) {
			$roomIds[] = $nun->room;
		}
		return $roomIds;
	}

	public function isRoomVisible(int $roomId): bool
	{
		return array_key_exists($roomId, $this->getRoomIds());
	}
}

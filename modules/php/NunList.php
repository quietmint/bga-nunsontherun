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
}

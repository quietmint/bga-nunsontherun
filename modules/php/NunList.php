<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class NunList implements \IteratorAggregate, \JsonSerializable
{
	private array $nuns = [];

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			foreach ($data as $type => $nun) {
				$this->nuns[$type] = new Nun($nun);
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
		$this->nuns[$nun->type] = $nun;
	}

	public function get(string $type): ?Nun
	{
		return array_key_exists($type, $this->nuns) ? $this->nuns[$type] : null;
	}
}

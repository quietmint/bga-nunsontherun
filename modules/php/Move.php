<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move implements \JsonSerializable
{
	public ?string $action = null;
	public bool $current = false;
	public ?int $noiseRoll = null;
	public array $noiseTokens = [];
	public ?int $noiseTotal = null;
	public ?array $path = null;
	public array $spaces = [];
	public int $start;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->action = $data->action;
			$this->current = $data->current;
			$this->noiseRoll = $data->noiseRoll;
			$this->noiseTokens = $data->noiseTokens;
			$this->noiseTotal = $data->noiseTotal;
			$this->path = $data->path;
			$this->spaces = $data->spaces;
			$this->start = $data->start;
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'action' => $this->action,
			'current' => $this->current,
			'noiseRoll' => $this->noiseRoll,
			'noiseTokens' => $this->noiseTokens,
			'noiseTotal' => $this->noiseTotal,
			'path' => $this->path,
			'spaces' => $this->spaces,
			'start' => $this->start,
		];
	}
}

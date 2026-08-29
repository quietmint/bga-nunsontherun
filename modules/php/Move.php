<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move implements \JsonSerializable
{
	public ?string $action = null;
	public ?int $noiseRoll = null;
	public array $noiseTokens = [];
	public ?int $noiseTotal = null;
	public array $spaces = [];
	public int $start;

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->action = $data->action;
			$this->noiseRoll = $data->noiseRoll;
			$this->noiseTokens = $data->noiseTokens;
			$this->noiseTotal = $data->noiseTotal;
			$this->spaces = $data->spaces;
			$this->start = $data->start;
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'action' => $this->action,
			'noiseRoll' => $this->noiseRoll,
			'noiseTokens' => $this->noiseTokens,
			'noiseTotal' => $this->noiseTotal,
			'spaces' => $this->spaces,
			'start' => $this->start,
		];
	}
}

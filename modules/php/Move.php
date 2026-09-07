<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move
{
	public ?string $action;
	public ?bool $active;
	public ?bool $deviate;
	public ?int $noiseRoll;
	public array $noiseTokens = [];
	public ?int $noiseTotal;
	public array $spaces = [];
	public int $start;
	public array $vanishTokens = [];

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->action = property_exists($data, 'action') ? $data->action : null;
			$this->active = property_exists($data, 'active') ? $data->active : null;
			$this->deviate = property_exists($data, 'deviate') ? $data->deviate : null;
			$this->noiseRoll = property_exists($data, 'noiseRoll') ? $data->noiseRoll : null;
			$this->noiseTokens = $data->noiseTokens;
			$this->noiseTotal = property_exists($data, 'noiseTotal') ? $data->noiseTotal : null;
			$this->spaces = $data->spaces;
			$this->start = $data->start;
			$this->vanishTokens = property_exists($data, 'vanishTokens') ? $data->vanishTokens : [];
		}
	}
}

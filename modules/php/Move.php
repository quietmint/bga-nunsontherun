<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move
{
	public ?string $action = null;
	public bool $active = false;
	public ?string $blessing = null;
	public bool $caught = false;
	public bool $caughtHistory = false;
	public bool $deviate = false;
	public ?int $noiseRoll = null;
	public array $noiseHistory = [];
	public array $noiseTokens = [];
	public ?int $noiseTotal = null;
	public array $spaces = [];
	public int $start;
	public ?array $undo = null;
	public array $vanishHistory = [];
	public array $vanishTokens = [];

	public ?string $actionName {
		&get {
			$actionName = null;
			if ($this->action == 'stand') {
				$actionName = clienttranslate("Stand");
			} else if ($this->action == 'sneak') {
				$actionName = clienttranslate("Sneak");
			} else if ($this->action == 'walk') {
				$actionName = clienttranslate("Walk");
			} else if ($this->action == 'run') {
				$actionName = clienttranslate("Run");
			}
			return $actionName;
		}
	}

	public function __construct(?\stdClass $data = null)
	{
		if ($data != null) {
			$this->action = property_exists($data, 'action') ? $data->action : null;
			$this->active = property_exists($data, 'active') && $data->active;
			$this->blessing = property_exists($data, 'blessing') ? $data->blessing : null;
			$this->caught = property_exists($data, 'caught') && $data->caught;
			$this->caughtHistory = property_exists($data, 'caughtHistory') && $data->caughtHistory;
			$this->deviate = property_exists($data, 'deviate') && $data->deviate;
			$this->noiseRoll = property_exists($data, 'noiseRoll') ? $data->noiseRoll : null;
			$this->noiseHistory = property_exists($data, 'noiseHistory') ? $data->noiseHistory : [];
			$this->noiseTokens = property_exists($data, 'noiseTokens') ? $data->noiseTokens : [];
			$this->noiseTotal = property_exists($data, 'noiseTotal') ? $data->noiseTotal : null;
			$this->spaces = property_exists($data, 'spaces') ? $data->spaces : [];
			$this->start = $data->start;
			$this->undo = property_exists($data, 'undo') ? $data->undo : null;
			$this->vanishHistory = property_exists($data, 'vanishHistory') ? $data->vanishHistory : [];
			$this->vanishTokens = property_exists($data, 'vanishTokens') ? $data->vanishTokens : [];
		}
	}
}

<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun;

class Move
{
	public ?string $action;
	public bool $active;
	public ?string $blessing;
	public bool $caught;
	public bool $caughtHistory;
	public bool $deviate;
	public ?int $noiseRoll;
	public array $noiseHistory;
	public array $noiseTokens;
	public ?int $noiseTotal;
	public array $spaces;
	public int $start;
	public ?array $undo;
	public array $vanishHistory;
	public array $vanishTokens;

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

	public function __construct(
		int $start,
		?string $action = null,
		bool $active = false,
		?string $blessing = null,
		bool $caught = false,
		bool $caughtHistory = false,
		bool $deviate = false,
		?int $noiseRoll = null,
		array $noiseHistory = [],
		array $noiseTokens = [],
		?int $noiseTotal = null,
		array $spaces = [],
		?array $undo = null,
		array $vanishHistory = [],
		array $vanishTokens = [],
	) {
		$this->action = $action;
		$this->active = $active;
		$this->blessing = $blessing;
		$this->caught = $caught;
		$this->caughtHistory = $caughtHistory;
		$this->deviate = $deviate;
		$this->noiseHistory = $noiseHistory;
		$this->noiseRoll = $noiseRoll;
		$this->noiseTokens = $noiseTokens;
		$this->noiseTotal = $noiseTotal;
		$this->spaces = $spaces;
		$this->start = $start;
		$this->undo = $undo;
		$this->vanishHistory = $vanishHistory;
		$this->vanishTokens = $vanishTokens;
	}

	public static function fromData(\stdClass $data): Move
	{
		return new Move(
			action: $data->action,
			active: $data->active,
			blessing: $data->blessing,
			caught: $data->caught,
			caughtHistory: $data->caughtHistory,
			deviate: $data->deviate,
			noiseHistory: $data->noiseHistory,
			noiseRoll: $data->noiseRoll,
			noiseTokens: $data->noiseTokens,
			noiseTotal: $data->noiseTotal,
			spaces: $data->spaces,
			start: $data->start,
			undo: $data->undo,
			vanishHistory: $data->vanishHistory,
			vanishTokens: $data->vanishTokens,
		);
	}
}

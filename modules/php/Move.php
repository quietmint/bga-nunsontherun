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
	public ?int $noiseTotal;
	/**
	 * @var array<int>
	 */
	public array $spaces;
	public int $start;
	public int $startRoom;
	/**
	 * @var array<Token>
	 */
	public array $tokens;
	public ?array $undo;

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
		int $startRoom,
		?string $action = null,
		bool $active = false,
		?string $blessing = null,
		bool $caught = false,
		bool $caughtHistory = false,
		bool $deviate = false,
		?int $noiseRoll = null,
		?int $noiseTotal = null,
		array $spaces = [],
		array $tokens = [],
		?array $undo = null,
	) {
		$this->action = $action;
		$this->active = $active;
		$this->blessing = $blessing;
		$this->caught = $caught;
		$this->caughtHistory = $caughtHistory;
		$this->deviate = $deviate;
		$this->noiseRoll = $noiseRoll;
		$this->noiseTotal = $noiseTotal;
		$this->spaces = $spaces;
		$this->start = $start;
		$this->startRoom = $startRoom;
		$this->tokens = $tokens;
		$this->undo = $undo;
	}

	public static function fromData(\stdClass $data): Move
	{
		$tokens = [];
		if (property_exists($data, 'tokens')) {
			foreach ($data->tokens as $token) {
				$tokens[] = Token::fromData($token);
			}
		}
		return new Move(
			action: $data->action,
			active: $data->active,
			blessing: $data->blessing,
			caught: $data->caught,
			caughtHistory: $data->caughtHistory,
			deviate: $data->deviate,
			noiseRoll: $data->noiseRoll,
			noiseTotal: $data->noiseTotal,
			spaces: $data->spaces,
			start: $data->start,
			startRoom: $data->startRoom,
			tokens: $tokens,
			undo: $data->undo,
		);
	}

	/**
	 * @return array<Token>
	 */
	public function getActiveTokens(): array
	{
		return array_filter($this->tokens, fn($token) => !$token->history);
	}

	/**
	 * @return array<Token>
	 */
	public function getNoiseTokens(?int $playerId = null): array
	{
		$tokens = [];
		foreach ($this->tokens as $token) {
			if (!$token->history && $token->type == 'noise' && ($playerId == null || $token->playerId == $playerId)) {
				$tokens[] = $token;
			}
		}
		return $tokens;
	}

	/**
	 * @return array<Token>
	 */
	public function getNoiseTokensInLocations(array $locations): array
	{
		return array_filter($this->tokens, fn($token) => !$token->history && $token->type == 'noise' && in_array($token->location, $locations));
	}

	/**
	 * @return array<Token>
	 */
	public function getVanishTokensInRoom(int $room): array
	{
		return array_filter($this->tokens, fn($token) => !$token->history && $token->type == 'vanish' && $token->room == $room);
	}
}

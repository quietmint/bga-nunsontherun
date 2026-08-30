<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\Actions\CheckAction;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;

class NoviceRecapGameState extends GameState
{
	function __construct(
		protected Game $game,
	) {
		parent::__construct(
			$game,
			id: 20,
			type: StateType::GAME,
			description: clienttranslate('Novices must take their turns'),
		);
	}

	public function onEnteringState()
	{
		$novices = $this->game->getNoviceList();
		$nuns = $this->game->getNunList();
		foreach ($novices as &$novice) {
			// Notify each novice's move type
			switch ($novice->move->action) {
				case 'stand':
					$message = clienttranslate('${player_name} stands still');
					break;
				case 'sneak':
					$message = clienttranslate('${player_name} sneaks');
					break;
				case 'walk':
					$message = clienttranslate('${player_name} walks');
					break;
				case 'run':
					$message = clienttranslate('${player_name} runs');
					break;
			}
			$this->bga->notify->all('noviceRecap', $message, [
				'preserve' => ['player_id', 'recap'],
				'player_id' => $novice->playerId,
				'player_name' => $novice->playerName,
				'recap' => true,
			]);

			// Notify when the novice appears/vanishes
			$oldSpaceId = $novice->move->start;
			$oldVisible = $nuns->isRoomVisible($this->game->board->getRoomId($novice->location));
			foreach ($novice->move->spaces as $spaceId) {
				$visible = $nuns->isRoomVisible($this->game->board->getRoomId($spaceId));
				if ($visible) {
					if (!$oldVisible) {
						$this->bga->notify->all('noviceRecap', clienttranslate('${player_name} is visible at ${location}'), [
							'preserve' => ['player_id', 'recap'],
							'location' => $spaceId,
							'player_id' => $novice->playerId,
							'player_name' => $novice->playerName,
							'recap' => true,
						]);
					}
					$this->bga->notify->all('noviceMove', clienttranslate('${player_name} moves to ${location}'), [
						'preserve' => ['player_id', 'recap'],
						'location' => $spaceId,
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
					]);
				} else if ($oldVisible) {
					$this->bga->notify->all('noviceVanish', clienttranslate('${player_name} vanishes at ${vanishLocation}'), [
						'preserve' => ['player_id', 'recap'],
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
						'vanishLocation' => $oldSpaceId,
					]);
				}
				$oldSpaceId = $spaceId;
				$oldVisible = $visible;
			}

			// Notify each novice's noise
			$this->bga->notify->all('noviceRoll', clienttranslate('${player_name} rolls ${roll} for noise'), [
				'preserve' => ['player_id', 'recap'],
				'player_id' => $novice->playerId,
				'player_name' => $novice->playerName,
				'recap' => true,
				'roll' => $novice->move->noiseRoll,
			]);
			if (!empty($novice->move->noiseTokens)) {
				foreach ($novice->move->noiseTokens as $noiseLocation) {
					$this->bga->notify->all('noviceNoise', clienttranslate('${player_name} places a noise token at ${noiseLocation}'), [
						'preserve' => ['player_id', 'recap'],
						'noiseLocation' => $noiseLocation,
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
					]);
				}
			}
		}
		return NunChoiceMultiState::class;
	}
}

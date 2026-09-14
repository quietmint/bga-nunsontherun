<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\NunsOnTheRun\Game;

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
			$this->bga->notify->all('noviceAction', $message, [
				'preserve' => ['action', 'actionName', 'player_id', 'recap'],
				'action' => $novice->move->action,
				'actionName' => $novice->move->actionName,
				'player_id' => $novice->playerId,
				'player_name' => $novice->playerName,
				'recap' => true,
			]);

			// Notify when the novice is visible/vanishes
			$oldSpaceId = $novice->move->start;
			$oldVisible = $nuns->isRoomVisible($this->game->board->getRoomId($novice->location));
			foreach ($novice->move->spaces as $spaceId) {
				$visible = $nuns->isRoomVisible($this->game->board->getRoomId($spaceId));
				if ($visible) {
					$this->bga->notify->all('noviceMove', clienttranslate('${player_name} is visible at ${visibleLocation}'), [
						'preserve' => ['player_id', 'recap'],
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
						'visibleLocation' => $spaceId,
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

			if ($novice->move->caught) {
				if (!$novice->caught) {
					$this->bga->notify->all('noviceCaught', clienttranslate('${player_name} is back on the run'), [
						'preserve' => ['caught', 'player_id', 'recap'],
						'caught' => $novice->caught,
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
					]);
				}
			} else {
				// Notify blessing
				// TODO!
				if ($novice->move->blessing) {
					$this->bga->notify->all('blessing', clienttranslate('${player_name} uses a blessing to make less noise'), [
						'preserve' => ['player_id', 'recap'],
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
					]);

					$this->bga->notify->all('blessing', clienttranslate('${player_name} uses a blessing to reroll'), [
						'preserve' => ['player_id', 'recap'],
						'player_id' => $novice->playerId,
						'player_name' => $novice->playerName,
						'recap' => true,
					]);
				}

				// Notify noise
				$this->bga->notify->all('noviceRoll', clienttranslate('${player_name} rolls ${roll} and makes noise ${noiseTotal} spaces away'), [
					'preserve' => ['player_id', 'recap'],
					'noiseTotal' => $novice->move->noiseTotal,
					'player_id' => $novice->playerId,
					'player_name' => $novice->playerName,
					'recap' => true,
					'roll' => $novice->move->noiseRoll,
				]);
				if (!empty($novice->move->noiseTokens)) {
					foreach ($novice->move->noiseTokens as $noiseLocation) {
						$this->bga->notify->all('noviceNoise', clienttranslate('${player_name} makes noise at ${noiseLocation}'), [
							'preserve' => ['player_id', 'recap'],
							'noiseLocation' => $noiseLocation,
							'player_id' => $novice->playerId,
							'player_name' => $novice->playerName,
							'recap' => true,
						]);
					}
				}
			}
		}
		return NunChoiceMultiState::class;
	}
}

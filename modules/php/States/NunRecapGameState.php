<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\NunsOnTheRun\Game;

class NunRecapGameState extends GameState
{
	function __construct(
		protected Game $game,
	) {
		parent::__construct(
			$game,
			id: 37,
			type: StateType::GAME,
			description: clienttranslate('Novices make noise'),
		);
	}

	public function onEnteringState()
	{
		$novices = $this->game->getNoviceList();
		foreach ($novices as $novice) {
			// Notify blessing
			// TODO!
			if (false) {
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
		}

		$nun = $this->game->getNunList()->getActiveNun();
		if (!empty($nun->noiseTokens)) {
			foreach ($nun->noiseTokens as $playerId => $noiseLocation) {
				$novice = $novices->get($playerId);
				$this->bga->notify->all('noviceNoise', clienttranslate('${player_name} makes noise at ${noiseLocation}'), [
					'preserve' => ['player_id', 'recap'],
					'noiseLocation' => $noiseLocation,
					'player_id' => $novice->playerId,
					'player_name' => $novice->playerName,
					'recap' => true,
				]);
			}
		}
		$nun->move->active = false;
		$this->game->saveNun($nun);
		return NunNoiseGameState::class;
	}
}

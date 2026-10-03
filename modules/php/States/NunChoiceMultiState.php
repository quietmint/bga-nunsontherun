<?php

declare(strict_types=1);

namespace Bga\Games\NunsOnTheRun\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\NunsOnTheRun\Game;
use Bga\Games\NunsOnTheRun\Move;

class NunChoiceMultiState extends GameState
{
  function __construct(
    protected Game $game,
  ) {
    parent::__construct(
      $game,
      id: 30,
      type: StateType::MULTIPLE_ACTIVE_PLAYER,
      description: clienttranslate('Nuns must choose which nun to activate'),
      descriptionMyTurn: clienttranslate('${you} must choose which nun to activate'),
    );
  }

  public function getArgs(): array
  {
    $nuns = $this->game->getNunList();
    $choices = $nuns->getChoices();
    return [
      '_no_notify' => count($choices) < 2,
      'choices' => $choices,
    ];
  }

  function onEnteringState(array $args)
  {
    if ($args['_no_notify']) {
      if (empty($args['choices'])) {
        // 2c. Remove noise and vanish tokens
        $novices = $this->game->getNoviceList();
        foreach ($novices as &$novice) {
          $novice->move->noiseHistory = $novice->move->noiseTokens;
          $novice->move->noiseTokens = [];
          $novice->move->vanishHistory = array_keys($novice->move->vanishTokens);
          $novice->move->vanishTokens = [];
        }
        $this->game->saveNovices($novices);
        $nuns = $this->game->getNunList();
        foreach ($nuns as &$nun) {
          $nun->move->noiseHistory = $nun->move->noiseTokens;
          $nun->move->noiseTokens = [];
        }
        $this->game->saveNuns($nuns);
        $this->bga->notify->all('clearNoise');
        return NunNoiseGameState::class;
      } else {
        // Choose the other nun
        $role = reset($args['choices']);
        $this->actChoose($role, $this->bga->tableOptions->getGameVersion());
        return;
      }
    }

    // Activate all nuns
    $this->gamestate->setPlayersMultiactive($this->game->getPlayerIds(1), '', true);
  }

  #[PossibleAction]
  public function actChoose(string $role, int $version)
  {
    $this->game->checkVersion($version);
    $novices = $this->game->getNoviceList();
    $nun = $this->game->getNunList()->get($role);
    $nun->move->active = true;
    $nun->move->deviate = $this->game->board->getNunDeviate($nun, $novices);
    $this->game->saveNun($nun);

    $this->bga->notify->all('message', clienttranslate('${roleName} ${player_name} activates'), [
      'i18n' => ['roleName'],
      'player_id' => $nun->playerId,
      'player_name' => $nun->playerName,
      'role' => $nun->role,
      'roleName' => $nun->roleName,
    ]);

    $this->gamestate->changeActivePlayer($nun->playerId);
    $this->game->giveExtraTime($nun->playerId);
    if ($nun->path == null) {
      $this->gamestate->setAllPlayersNonMultiactive(NunPathPlayerState::class);
    } else {
      $this->gamestate->setAllPlayersNonMultiactive(NunMovePlayerState::class);
    }
  }

  function zombie(int $playerId, array $args)
  {
    $this->bga->notify->all('message', "🪦 Zombie $playerId: " . $this->name);
    if (!$args['_no_notify']) {
      $nunIds = $this->game->getPlayerIds(1);
      if (empty($nunIds)) {
        $this->bga->notify->all('message', "🪦 Zombie $playerId: Choose abbess.");
        $this->actChoose('abbess', $this->bga->tableOptions->getGameVersion());
      } else {
        $this->bga->notify->all('message', "🪦 Zombie $playerId: The remaining player will choose.");
      }
    } else {
      $this->bga->notify->all('message', "🪦 Zombie $playerId: _no_notify = true so do nothing");
    }
  }
}

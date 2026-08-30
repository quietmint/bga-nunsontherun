/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { NoviceTurnMultiState } from "./States/NoviceTurnMultiState.js";
import { NoviceMovePrivateState } from "./States/NoviceMovePrivateState.js";
import { NoviceNoisePrivateState } from "./States/NoviceNoisePrivateState.js";
import { NunChoiceMultiState } from "./States/NunChoiceMultiState.js";
import { NunPathMultiState } from "./States/NunPathMultiState.js";

export class Game {
  constructor(bga) {
    console.log("Nuns on the Run!");
    this.bga = bga;
    this.bga.states.logger = console.log;
    this.bga.states.register("NoviceTurnMultiState", new NoviceTurnMultiState(this, bga));
    this.bga.states.register("NoviceMovePrivateState", new NoviceMovePrivateState(this, bga));
    this.bga.states.register("NoviceNoisePrivateState", new NoviceNoisePrivateState(this, bga));
    this.bga.states.register("NunChoiceMultiState", new NunChoiceMultiState(this, bga));
    this.bga.states.register("NunPathMultiState", new NunPathMultiState(this, bga));

    this.classLocations = [];
    for (let i = 1; i <= 155; i++) {
      this.classLocations.push("notr-" + i);
    }
  }

  isNovice() {
    const playerId = this.bga.players.getCurrentPlayerId();
    return this.gamedatas.novices[playerId] != null;
  }

  getNovice(playerId) {
    if (playerId == null) {
      playerId = this.bga.players.getCurrentPlayerId();
    }
    return this.gamedatas.novices[playerId];
  }

  isNun() {
    const playerId = this.bga.players.getCurrentPlayerId();
    return this.gamedatas.nuns.abbess.playerId == playerId || this.gamedatas.nuns.prioress.playerId == playerId;
  }

  setup(gamedatas) {
    this.gamedatas = gamedatas;
    console.log("Setup", gamedatas);

    // Object.values(gamedatas.players).forEach((player) => {
    //   player.avatarUrl = this.bga.players.getPlayerAvatarUrl(player.id);
    // });
    Object.values(gamedatas.novices).forEach((novice) => {
      novice.avatarUrl = this.bga.players.getPlayerAvatarUrl(novice.playerId);
    });
    Object.values(gamedatas.nuns).forEach((nun) => {
      nun.avatarUrl = this.bga.players.getPlayerAvatarUrl(nun.playerId);
    });
    this.setupBoard();
    this.setupPanels();
    this.setupNotifications();
  }

  setupBoard() {
    this.bga.gameArea.getElement().insertAdjacentHTML("beforeend", `<div id="notr-board"></div>`);
    const boardEl = document.getElementById("notr-board");
    Object.values(this.gamedatas.nuns).forEach((nun) => {
      boardEl.insertAdjacentHTML("beforeend", this.html_playerNun(nun));
    });
    Object.values(this.gamedatas.novices).forEach((novice) => {
      boardEl.insertAdjacentHTML("beforeend", this.html_playerNovice(novice));
      if (novice.move && novice.move.noiseTokens && novice.move.noiseTokens.length > 0) {
        novice.move.noiseTokens.forEach((locationId) => {
          let holderEl = document.getElementById(`notr-noise-holder-${locationId}`);
          if (holderEl == null) {
            boardEl.insertAdjacentHTML("beforeend", this.html_noiseContainer(locationId));
            holderEl = document.getElementById(`notr-noise-holder-${locationId}`);
          }
          holderEl.insertAdjacentHTML("beforeend", this.html_noiseToken(novice));
        });
      }
    });
  }

  setupPanels() {
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const panelEl = this.bga.playerPanels.getElement(novice.playerId);
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div class="notr-panel notr-${novice.color}">
  <div class="notr-caught notr-caught-${novice.caught}">${_(novice.statusText)}</div>
  <div class="notr-move">
    <div class="notr-move-title">${_("Movement")}</div>
    <div class="notr-move-icon notr-move-${novice.move || "unknown"}" title="${_(novice.move || "?")}"></div>
    <div class="notr-move-distance" title="${_("Distance")}">👣${novice.moveDistance || "?"}</div>
    <div class="notr-move-noise" title="${_("Noise Modifier")}">🔈${novice.moveNoise || "?"}</div>
  </div>
  <div class="notr-wish">
    <div class="notr-wish-title">${_("Secret Wish")}</div>
    <div class="notr-wish-icon notr-wish-${novice.wish || "unknown"}" title="${_(novice.wish || "?")}"></div>
    <div class="notr-wish-key" title="${_("Key")}">🔑${novice.keyLocation || "?"}</div>
    <div class="notr-wish-loc" title="${_("Secret Wish")}">🌟${novice.wishLocation || "?"}</div>
  </div>
</div>`,
      );
    });
  }

  setupNotifications() {
    this.bga.notifications.setupPromiseNotifications({
      logger: console.log,
    });
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceAppear", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceMove", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceNoise", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceRecap", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceRoll", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceVanish", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
  }

  async notif_noviceMove(args) {
    console.log("doing notif_noviceMove", args);
    const noviceEl = document.getElementById("notr-novice-" + args.player_id);
    if (noviceEl == null) {
      console.error(`notr-novice-${args.player_id} not found`);
      return;
    }
    noviceEl.classList.remove(...this.classLocations);
    noviceEl.classList.add("notr-" + args.location);
  }

  async notif_noviceNoise(args) {
    console.log("doing notif_noviceNoise", args);
    if (args.recap && args.player_id == this.bga.players.getCurrentPlayerId()) {
      // ignore
      return;
    }
    const novice = this.getNovice(args.playerId);
    const boardEl = document.getElementById("notr-board");
    let holderEl = document.getElementById(`notr-noise-holder-${args.noiseLocation}`);
    if (holderEl == null) {
      boardEl.insertAdjacentHTML("beforeend", this.html_noiseContainer(args.noiseLocation));
      holderEl = document.getElementById(`notr-noise-holder-${args.noiseLocation}`);
    }
    holderEl.insertAdjacentHTML("beforeend", this.html_noiseToken(novice));
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  html_playerNovice(novice) {
    return `<div id="notr-novice-${novice.playerId}" class="notr-player notr-${novice.color} notr-${novice.location}" style="background-image: url(${novice.avatarUrl})" title="${novice.playerName}"></div>`;
  }

  html_playerNun(nun) {
    return `<div id="notr-nun-${nun.role}" class="notr-player notr-${nun.color} notr-${nun.location}" style="background-image: url(${nun.avatarUrl})" title="${nun.playerName} (${_(nun.role)})"></div>`;
  }

  html_noiseContainer(locationId) {
    return `<div id="notr-noise-holder-${locationId}" class="notr-noise-holder notr-${locationId}"></div>`;
  }

  html_noiseToken(novice) {
    return `<div class="notr-noise notr-${novice.color}" title="${novice.playerName}"><span class="notr-icon notr-icon-noise"></span></div>`;
  }

  html_dice() {
    return `<div id="notr-die" class="notr-die">
  <div class="face face-1"><div class="pip pip-1"></div></div>
  <div class="face face-2"><div class="pip pip-1"></div><div class="pip pip-2"></div></div>
  <div class="face face-3"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div></div>
  <div class="face face-4"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div></div>
  <div class="face face-5"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div><div class="pip pip-5"></div></div>
  <div class="face face-6"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div><div class="pip pip-5"></div><div class="pip pip-6"></div></div>
</div>`;
  }

  bgaFormatText(log, args) {
    try {
      if (log && args && !args.processed) {
        args.processed = true;
        // if (args.keyLocation) {
        //   args.keyLocation = `<b>🔑${args.keyLocation}</b>`;
        // }
        if (args.location) {
          args.location = `<b>🚩${args.location}</b>`;
        }
        if (args.startLocation) {
          args.startLocation = `<b>🚩${args.startLocation}</b>`;
        }
        if (args.noiseLocation) {
          args.noiseLocation = `<span class="notr-icon notr-icon-noise"></span> <b>${args.noiseLocation}</b>`;
        }
        if (args.wishIcon) {
          log += `<div class="notr-notify notr-wish">
  <div class="notr-wish-icon notr-wish-${args.wishIcon}" title="${_(args.wish)}"></div>
  <div class="notr-wish-key" title="${_("Key")}">🔑${args.keyLocation}</div>
  <div class="notr-wish-loc" title="${_("Secret Wish")}">🌟${args.wishLocation}</div>
</div>`;
        }
      }
    } catch (e) {
      console.error(log, args, "Exception thrown", e.stack);
    }
    return { log, args };
  }
}

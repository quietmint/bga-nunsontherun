/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { NoviceMove } from "./States/NoviceMove.js";
import { NovicesMove } from "./States/NovicesMove.js";

export class Game {
  constructor(bga) {
    console.log("Nuns on the Run!");
    this.bga = bga;
    this.bga.states.logger = console.log;
    this.bga.states.register("NoviceMove", new NoviceMove(this, bga));
    this.bga.states.register("NovicesMove", new NovicesMove(this, bga));

    this.classLocations = [];
    for (let i = 1; i <= 155; i++) {
      this.classLocations.push("notr-" + i);
    }
  }

  isNovice() {
    const playerId = this.bga.players.getCurrentPlayerId();
    return this.gamedatas.novices[playerId] != null;
  }

  isNun() {
    const playerId = this.bga.players.getCurrentPlayerId();
    return this.gamedatas.nuns.abbess.playerId == playerId || this.gamedatas.nuns.prioress.playerId == playerId;
  }

  setup(gamedatas) {
    this.gamedatas = gamedatas;
    console.log("Setup", gamedatas);

    Object.values(gamedatas.players).forEach((player) => {
      player.avatarUrl = this.bga.players.getPlayerAvatarUrl(player.id);
    });
    this.setupBoard();
    this.setupPanels();
    this.setupNotifications();
  }

  setupBoard() {
    this.bga.gameArea.getElement().insertAdjacentHTML("beforeend", `<div id="notr-board"></div>`);
    const boardEl = document.getElementById("notr-board");
    Object.values(this.gamedatas.nuns).forEach((nun) => {
      const player = this.gamedatas.players[nun.playerId];
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-nun-${nun.role}" class="notr-player notr-${nun.color} notr-${nun.location}" style="background-image: url(${player.avatarUrl})" title="${player.name} (${_(nun.role)})"></div>`);
    });
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const player = this.gamedatas.players[novice.playerId];
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-novice-${novice.playerId}" class="notr-player notr-${novice.color} notr-${novice.location}" style="background-image: url(${player.avatarUrl})" title="${player.name}"></div>`);
    });
  }

  setupPanels() {
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const player = this.gamedatas.players[novice.playerId];
      const panelEl = this.bga.playerPanels.getElement(novice.playerId);
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div class="notr-panel notr-${novice.color}">
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
  }

  async notif_noviceMove(args) {
    const noviceEl = document.getElementById("notr-novice-" + args.player_id);
    if (noviceEl == null) {
      console.error(`notr-novice-${args.player_id} not found`);
      return;
    }
    noviceEl.classList.remove(...this.classLocations);
    noviceEl.classList.add("notr-" + args.location);
  }

  ///////////////////////////////////////////////////
  //// Utility methods

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
        // if (args.wishLocation) {
        //   args.wishLocation = `<b>🌟${args.wishLocation}</b>`;
        // }
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

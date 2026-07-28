/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { PlayerTurn } from "./States/PlayerTurn.js";

export class Game {
  constructor(bga) {
    console.log("Nuns on the Run!");
    this.bga = bga;
    this.bga.states.logger = console.log;
    this.bga.states.register("PlayerTurn", new PlayerTurn(this, bga));
  }

  setup(gamedatas) {
    this.gamedatas = gamedatas;
    console.log("Setup", gamedatas);

    Object.values(gamedatas.players).forEach((player) => {
      player.avatarUrl = this.bga.players.getPlayerAvatarUrl(player.id);
      if (player.color == "ff5722") {
        player.colorName = "orange";
      } else if (player.color == "03a9f4") {
        player.colorName = "blue";
      } else if (player.color == "e91e63") {
        player.colorName = "red";
      } else if (player.color == "8bc34a") {
        player.colorName = "green";
      } else if (player.color == "9c27b0") {
        player.colorName = "purple";
      } else if (player.color == "ffc107") {
        player.colorName = "yellow";
      }
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
      const title = nun.nun;
      boardEl.insertAdjacentHTML("beforeend", `<div class="notr-player notr-color-${nun.color} notr-${nun.location}" style="background-image: url(${player.avatarUrl}); border-color: #${nun.color}" title="${player.name} (${title})"></div>`);
    });
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const player = this.gamedatas.players[novice.playerId];
      boardEl.insertAdjacentHTML("beforeend", `<div class="notr-player notr-color-${novice.color} notr-${novice.location}" style="background-image: url(${player.avatarUrl}); border-color: #${novice.color}" title="${player.name}"></div>`);
    });
  }

  setupPanels() {
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const player = this.gamedatas.players[novice.playerId];
      const panelEl = this.bga.playerPanels.getElement(novice.playerId);
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div class="notr-panel notr-panel-${player.colorName}">
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
    console.log("notifications subscriptions setup");

    // automatically listen to the notifications, based on the `notif_xxx` function on this class.
    // Uncomment the logger param to see debug information in the console about notifications.
    this.bga.notifications.setupPromiseNotifications({
      // logger: console.log
    });
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

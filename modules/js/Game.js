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
import { NoviceRollPrivateState } from "./States/NoviceRollPrivateState.js";
import { NunChoiceMultiState } from "./States/NunChoiceMultiState.js";
import { NunPathPlayerState } from "./States/NunPathPlayerState.js";
import { NunMovePlayerState } from "./States/NunMovePlayerState.js";
import { NunRollPlayerState } from "./States/NunRollPlayerState.js";

export class Game {
  constructor(bga) {
    console.log("Nuns on the Run!");
    this.bga = bga;
    this.bga.states.logger = console.log;
    this.bga.states.register("NoviceTurnMultiState", new NoviceTurnMultiState(this, bga));
    this.bga.states.register("NoviceMovePrivateState", new NoviceMovePrivateState(this, bga));
    this.bga.states.register("NoviceRollPrivateState", new NoviceRollPrivateState(this, bga));
    this.bga.states.register("NoviceOwnNoisePrivateState", new NoviceNoisePrivateState(this, bga));
    this.bga.states.register("NoviceNunNoisePrivateState", new NoviceNoisePrivateState(this, bga));
    this.bga.states.register("NunChoiceMultiState", new NunChoiceMultiState(this, bga));
    this.bga.states.register("NunPathPlayerState", new NunPathPlayerState(this, bga));
    this.bga.states.register("NunMovePlayerState", new NunMovePlayerState(this, bga));
    this.bga.states.register("NunRollPlayerState", new NunRollPlayerState(this, bga));

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

  getNun(role) {
    return this.gamedatas.nuns[role];
  }

  isNun() {
    const playerId = this.bga.players.getCurrentPlayerId();
    return this.gamedatas.nuns.abbess.playerId == playerId || this.gamedatas.nuns.prioress.playerId == playerId;
  }

  setup(gamedatas) {
    this.gamedatas = gamedatas;
    console.log("Setup", gamedatas);

    Object.values(gamedatas.novices).forEach((novice) => {
      novice.avatarUrl = this.bga.players.getPlayerAvatarUrl(novice.playerId);
    });
    Object.values(gamedatas.nuns).forEach((nun) => {
      nun.avatarUrl = this.bga.players.getPlayerAvatarUrl(nun.playerId);
    });
    this.setupBoard();
    this.setupPanels();
    this.setupNotifications();
    if (gamedatas.round == gamedatas.roundMax - 1) {
      this.bga.gameArea.addLastTurnBanner();
    }
  }

  setupBoard() {
    this.bga.gameArea.getElement().insertAdjacentHTML("beforeend", `<div id="notr-board"></div>`);
    const boardEl = document.getElementById("notr-board");
    const playerId = this.bga.players.getCurrentPlayerId();
    Object.values(this.gamedatas.novices).forEach((novice) => {
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-novice-${novice.playerId}" class="notr-player notr-${novice.color} notr-${novice.location}" style="background-image: url(${novice.avatarUrl})" title="${novice.playerName}"></div>`);
      if (novice.move && novice.move.noiseTokens) {
        for (let location in novice.move.noiseTokens) {
          this.addNoviceNoise(novice, location);
        }
      }
      if (novice.move && novice.move.vanishTokens) {
        for (let location in novice.move.vanishTokens) {
          this.addNoviceVanish(novice, location);
        }
      }
      if (novice.playerId == playerId) {
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-bed" class="notr-my notr-${novice.color} notr-${novice.startLocation}"><span class="notr-icon notr-icon-bed"></span></div>`);
        if (!novice.hasKey) {
          boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-key" class="notr-my notr-${novice.color} notr-${novice.keyLocation}"><span class="notr-icon notr-icon-key"></span></div>`);
        }
        if (!novice.hasWish) {
          boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-wish" class="notr-my notr-${novice.color} notr-${novice.wishLocation}"><span class="notr-icon notr-icon-wish"></span></div>`);
        }
      }
    });
    Object.values(this.gamedatas.nuns).forEach((nun) => {
      const title = this.bga.gameui.format_string(_("${roleName} ${player_name}"), {
        roleName: this.emoji(nun.role) + _(nun.roleName),
        player_name: nun.playerName,
      });
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-nun-${nun.role}" class="notr-player notr-${nun.color} notr-${nun.location}" style="background-image: url(${nun.avatarUrl})" title="${title}"></div>`);
    });
  }

  setupPanels() {
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const panelEl = this.bga.playerPanels.getElement(novice.playerId);
      const statusText = novice.caught ? _("Caught") : _("On The Run");
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div id="notr-panel-${novice.playerId}" class="notr-panel notr-${novice.color}">
  <div id="notr-caught-${novice.playerId}" class="notr-caught notr-caught-${novice.caught}">${statusText}</div>
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

    Object.values(this.gamedatas.nuns).forEach((nun) => {
      const panelEl = this.bga.playerPanels.getElement(nun.playerId);
      let path = "?";
      if (nun.path) {
        path = this.html_pathTag(nun.pathColor, nun.pathOrigin, nun.pathDestination);
      }
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div id="notr-panel-${nun.role}" class="notr-panel-nun notr-${nun.color}">
  <div class="notr-panel-title">${this.emoji(nun.role)}${_(nun.roleName)}</div>
  <div class="notr-panel-path">${_("Path")}: <span id="notr-panel-${nun.role}-path">${path}</span></div>
</div>`,
      );
      if (nun.path) {
        this.bga.gameui.addTooltipHtml(`notr-panel-${nun.role}-path`, `<div class="notr-path-image notr-path-${nun.path}"></div>`);
      }
    });
  }

  setupNotifications() {
    this.bga.notifications.setupPromiseNotifications({
      logger: console.log,
    });
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceMove", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceNoise", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceRecap", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceRoll", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceVanish", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
  }

  getHolderEl(location) {
    let holderEl = document.getElementById(`notr-holder-${location}`);
    if (holderEl == null) {
      const boardEl = document.getElementById("notr-board");
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-holder-${location}" class="notr-holder notr-${location}"></div>`);
      holderEl = document.getElementById(`notr-holder-${location}`);
    }
    return holderEl;
  }

  addNoviceNoise(novice, location) {
    const holderEl = this.getHolderEl(location);
    holderEl.insertAdjacentHTML("beforeend", `<div class="notr-tag notr-${novice.color} notr-noise-${novice.playerId}" title="${novice.playerName}"><span class="notr-icon notr-icon-noise"></span></div>`);
  }

  addNoviceVanish(novice, location) {
    const holderEl = this.getHolderEl(location);
    holderEl.insertAdjacentHTML("beforeend", `<div class="notr-tag notr-${novice.color} notr-vanish-${novice.playerId}" title="${novice.playerName}"><span class="notr-icon notr-icon-vanish"></span></div>`);
  }

  async notif_noviceCaught(args) {
    console.log("doing notif_noviceCaught", args);
    const novice = this.getNovice(args.player_id2);
    novice.hasWish = false;
    novice.caught = true;

    const caughtEl = document.getElementById(`notr-caught-${args.player_id2}`);
    caughtEl.innerText = _("Caught");
    caughtEl.classList.remove("notr-caught-false");
    caughtEl.classList.add("notr-caught-true");
  }

  async notif_noviceKey(args) {
    const novice = this.getNovice(args.player_id);
    novice.hasKey = true;
  }

  async notif_noviceMove(args) {
    console.log("doing notif_noviceMove", args);
    const location = args.visibleLocation || args.location;
    const novice = this.getNovice(args.player_id);
    novice.location = location;

    const noviceEl = document.getElementById("notr-novice-" + args.player_id);
    if (noviceEl == null) {
      console.error(`notr-novice-${args.player_id} not found`);
      return;
    }
    noviceEl.classList.remove(...this.classLocations);
    noviceEl.classList.add("notr-" + location);
  }

  async notif_noviceNoise(args) {
    console.log("doing notif_noviceNoise", args);
    const novice = this.getNovice(args.player_id);
    this.addNoviceNoise(novice, args.noiseLocation);
  }

  async notif_noviceNoiseUndo(args) {
    console.log("doing notif_noviceNoiseUndo", args);
    const noiseEls = document.getElementsByClassName(`notr-noise-${args.player_id}`);
    // Remove all noise
    console.log("noiseEls", noiseEls.length);
    while (noiseEls.length > 0) {
      noiseEls[0].remove();
    }
    // Add remaining noise
    const novice = this.getNovice(args.player_id);
    if (args.noiseTokens) {
      for (let location in args.noiseTokens) {
        this.addNoviceNoise(novice, location);
      }
    }
  }

  async notif_noviceUndo(args) {
    console.log("doing notif_noviceUndo", args);
    const vanishEls = document.getElementsByClassName(`notr-vanish-${args.player_id}`);
    console.log("vanishEls", vanishEls.length);
    while (vanishEls.length > 0) {
      vanishEls[0].remove();
    }
    await this.notif_noviceNoiseUndo(args);
    await this.notif_noviceMove(args);
  }

  async notif_noviceVanish(args) {
    console.log("doing notif_noviceVanish", args);
    const novice = this.getNovice(args.player_id);
    this.addNoviceVanish(novice, args.vanishLocation);
    await this.notif_noviceMove({ player_id: args.player_id, location: novice.startLocation });
  }

  async notif_noviceWish(args) {
    const novice = this.getNovice(args.player_id);
    novice.hasWish = true;
  }

  async notif_nunPath(args) {
    console.log("doing notif_nunPath", args);
    const el = document.getElementById(`notr-panel-${args.role}-path`);
    el.innerHTML = this.html_pathTag(args.pathColor, args.pathOrigin, args.pathDestination);
    this.bga.gameui.removeTooltip(el.id);
    this.bga.gameui.addTooltipHtml(el.id, `<div class="notr-path-image notr-path-${args.path}"></div>`);
  }

  async notif_nunMove(args) {
    console.log("doing notif_nunMove", args);
    const nunEl = document.getElementById("notr-nun-" + args.role);
    if (nunEl == null) {
      console.error(`notr-nun-${args.role} not found`);
      return;
    }
    nunEl.classList.remove(...this.classLocations);
    nunEl.classList.add("notr-" + args.location);
  }

  async notif_round(args) {
    if (args.round == args.roundMax - 1) {
      this.bga.gameArea.addLastTurnBanner();
    }
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  html_pathTag(color, origin, destination) {
    return `<span class="notr-tag notr-${color}"><span class="notr-icon notr-icon-path-${color}"></span> ${origin}▸${destination}</span>`;
  }

  html_dieAnimate(face) {
    const id = "notr-die-" + crypto.randomUUID();
    setTimeout(() => {
      const el = document.getElementById(id);
      if (el != null) {
        el.classList.add("show-" + face);
      }
    }, 50);
    return `<div id="${id}" class="notr-die">${this.html_dieFace(1)}${this.html_dieFace(2)}${this.html_dieFace(3)}${this.html_dieFace(4)}${this.html_dieFace(5)}${this.html_dieFace(6)}</div>`;
  }

  html_dieSingle(face) {
    return `<div class="notr-die show-${face}">${this.html_dieFace(face)}</div>`;
  }

  html_dieFace(face) {
    let html = `<div class="face face-${face}" title="${face}">`;
    for (let pip = 1; pip <= face; pip++) {
      html += `<div class="pip pip-${pip}"></div>`;
    }
    html += "</div>";
    return html;
  }

  emoji(arg) {
    switch (arg) {
      case "white":
      case "prioress":
        return "⚪";
      case "black":
      case "abbess":
        return "⚫";
    }
  }

  bgaFormatText(log, args) {
    try {
      if (log != null && args && !args.processed) {
        args.processed = true;
        if (args.keyLocation) {
          args.keyLocation = `<b><span class="notr-icon notr-icon-key"></span> ${args.keyLocation}</b>`;
        }
        if (args.location) {
          args.location = `<b>${args.location}</b>`;
        }
        if (args.noiseLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.noiseLocation = `<span class="notr-tag notr-${novice.color}"><span class="notr-icon notr-icon-noise"></span> ${args.noiseLocation}</span>`;
        }
        if (args.pathName && args.path && args.pathColor && args.pathDestination && args.pathOrigin) {
          args.pathName = this.html_pathTag(args.pathColor, args.pathOrigin, args.pathDestination);
          log += `<div class="notr-notify notr-path-image notr-path-${args.path}"></div>`;
        }
        if (args.roleName && args.role) {
          args.roleName = `${this.emoji(args.role)}${args.roleName}`;
        }
        if (args.roll) {
          args.roll = args.rollAnimate ? this.html_dieAnimate(args.roll) : this.html_dieSingle(args.roll);
        }
        if (args.startLocation) {
          args.startLocation = `<b>${args.startLocation}</b>`;
        }
        if (args.vanishLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.vanishLocation = `<span class="notr-tag notr-${novice.color}"><span class="notr-icon notr-icon-vanish"></span> ${args.vanishLocation}</span>`;
        }
        if (args.visibleLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.visibleLocation = `<span class="notr-tag notr-${novice.color}"><span class="notr-icon notr-icon-visible"></span> ${args.visibleLocation}</span>`;
        }
        if (args.wishIcon) {
          log += `<div class="notr-notify notr-wish">
  <div class="notr-wish-icon notr-wish-${args.wishIcon}" title="${_(args.wish)}"></div>
  <div class="notr-wish-key" title="${_("Key")}">🔑${args.keyLocation}</div>
  <div class="notr-wish-loc" title="${_("Secret Wish")}">🌟${args.wishLocation}</div>
</div>`;
        }
        if (args.wishLocation) {
          args.wishLocation = `<b><span class="notr-icon notr-icon-wish"></span> ${args.wishLocation}</b>`;
        }
      }
    } catch (e) {
      console.error(log, args, "Exception thrown", e.stack);
    }
    return { log, args };
  }
}

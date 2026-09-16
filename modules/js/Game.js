/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * NunsOnTheRun implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { NoviceCaughtPrivateState } from "./States/NoviceCaughtPrivateState.js";
import { NoviceMovePrivateState } from "./States/NoviceMovePrivateState.js";
import { NoviceNoisePrivateState } from "./States/NoviceNoisePrivateState.js";
import { NoviceRollPrivateState } from "./States/NoviceRollPrivateState.js";
import { NoviceTurnMultiState } from "./States/NoviceTurnMultiState.js";
import { NunChoiceMultiState } from "./States/NunChoiceMultiState.js";
import { NunMovePlayerState } from "./States/NunMovePlayerState.js";
import { NunPathPlayerState } from "./States/NunPathPlayerState.js";
import { NunRollPlayerState } from "./States/NunRollPlayerState.js";

export class Game {
  constructor(bga) {
    console.log("Nuns on the Run!");
    this.bga = bga;
    this.bga.states.logger = console.log;
    this.bga.states.register("NoviceCaughtPrivateState", new NoviceCaughtPrivateState(this, bga));
    this.bga.states.register("NoviceMovePrivateState", new NoviceMovePrivateState(this, bga));
    this.bga.states.register("NoviceNunNoisePrivateState", new NoviceNoisePrivateState(this, bga));
    this.bga.states.register("NoviceOwnNoisePrivateState", new NoviceNoisePrivateState(this, bga));
    this.bga.states.register("NoviceRollPrivateState", new NoviceRollPrivateState(this, bga));
    this.bga.states.register("NoviceTurnMultiState", new NoviceTurnMultiState(this, bga));
    this.bga.states.register("NunChoiceMultiState", new NunChoiceMultiState(this, bga));
    this.bga.states.register("NunMovePlayerState", new NunMovePlayerState(this, bga));
    this.bga.states.register("NunPathPlayerState", new NunPathPlayerState(this, bga));
    this.bga.states.register("NunRollPlayerState", new NunRollPlayerState(this, bga));

    this.classLocations = ["notr-offset"];
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

    // Novices
    Object.values(this.gamedatas.novices).forEach((novice) => {
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-novice-${novice.playerId}" class="notr-player notr-player-${novice.color} notr-${novice.location}" title="${novice.playerName}"></div>`);
      this.playerOffset(boardEl, novice.location);
      if (novice.playerId == playerId) {
        document.getElementById(`notr-novice-${novice.playerId}`).classList.add("notr-player-me");
        this.addMyStart(boardEl, novice);
        if (!novice.hasKey) {
          this.addMyKey(boardEl, novice);
        }
        if (!novice.hasWish) {
          this.addMyWish(boardEl, novice);
        }
      }

      if (novice.move && novice.move.noiseTokens) {
        console.log("novice noiseTokens", novice.move.noiseTokens);
        for (let location of novice.move.noiseTokens) {
          console.log("novice noiseToken location", location);
          this.addNoviceNoise(novice, location);
        }
      }
      if (novice.move && novice.move.vanishTokens) {
        for (let location in novice.move.vanishTokens) {
          this.addNoviceVanish(novice, location);
        }
      }
    });

    // Nuns
    Object.values(this.gamedatas.nuns).forEach((nun) => {
      const title = this.bga.gameui.format_string(_("${roleName} ${player_name}"), {
        roleName: this.emoji(nun.role) + _(nun.roleName),
        player_name: nun.playerName,
      });
      boardEl.insertAdjacentHTML("beforeend", `<div id="notr-nun-${nun.role}" class="notr-player notr-player-${nun.color} notr-${nun.location}" title="${title}"></div>`);
      this.playerOffset(boardEl, nun.location);
      if (nun.playerId == playerId) {
        document.getElementById(`notr-nun-${nun.role}`).classList.add("notr-player-me");
      }

      if (nun.noiseTokens) {
        console.log("nun noiseTokens", nun.noiseTokens);
        for (let playerId in nun.noiseTokens) {
          const location = nun.noiseTokens[playerId];
          console.log("nun noiseToken", playerId, location);
          const novice = this.getNovice(playerId);
          this.addNoviceNoise(novice, location);
        }
      }
    });
  }

  setupPanels() {
    // Novices
    Object.values(this.gamedatas.novices).forEach((novice) => {
      const panelEl = this.bga.playerPanels.getElement(novice.playerId);
      const statusText = novice.caught ? _("Caught") : _("On The Run");
      const key = novice.hasKey ? `<span class="notr-icon notr-icon-circle-yes"></span>` : novice.keyLocation || "-";
      const wish = novice.hasWish ? `<span class="notr-icon notr-icon-circle-yes"></span>` : novice.wishLocation || "-";
      let privateHtml = "";
      if (novice.playerId == this.bga.players.getCurrentPlayerId()) {
        privateHtml = `<div id="notr-panel-${novice.playerId}-private" class="notr-locations">
  <div title="${_("Current Location")}"><span class="notr-label notr-icon notr-icon-location"></span><div id="notr-panel-${novice.playerId}-location">${novice.location}</div></div>
  <div title="${_("Key Location")}"><span class="notr-label notr-icon notr-icon-key"></span><div id="notr-panel-${novice.playerId}-key">${key}</div></div>
  <div title="${_("Secret Wish Location")}"><span class="notr-label notr-icon notr-icon-wish"></span><div id="notr-panel-${novice.playerId}-wish">${wish}</div></div>
</div>`;
      }
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div id="notr-panel-${novice.playerId}" class="notr-panel  notr-panel-novice notr-player-${novice.color}">
  <div class="notr-portrait"></div>
  <div id="notr-title-${novice.playerId}" class="notr-title ${novice.caught ? "notr-caught" : ""}">${statusText}</div>
  <div class="notr-locations">
    <div title="${_("Move")}"><span class="notr-label notr-icon notr-icon-move"></span><div id="notr-panel-${novice.playerId}-move">${_(novice.move?.actionName || "-")}</div></div>
    <div title="${_("Noise")}"><span class="notr-label notr-icon notr-icon-noise"></span><div id="notr-panel-${novice.playerId}-noise">${novice.move?.noiseTotal || "-"}</div></div>
    <div title="${_("Start Location")}"><span class="notr-label notr-icon notr-icon-start"></span><div>${novice.startLocation}</div></div>
  </div>
  ${privateHtml}
</div>`,
      );
    });

    // Nuns
    Object.values(this.gamedatas.nuns).forEach((nun) => {
      const panelEl = this.bga.playerPanels.getElement(nun.playerId);
      const path = nun.path ? `<span class="notr-tag notr-path-${nun.pathColor}"><span class="notr-icon notr-icon-path-${nun.pathColor}"></span></span><div>${nun.pathOrigin}▸${nun.pathDestination}</div>` : _("No Path");
      let noise = "-";
      if (nun.move) {
        if (nun.move.noiseTotal) {
          noise = nun.move.noiseTotal;
        } else if (nun.move.action == "walk") {
          noise = '<span class="notr-icon notr-icon-circle-yes"></span>';
        } else if (nun.move.action == "run") {
          noise = '<span class="notr-icon notr-icon-circle-no"></span>';
        }
      }
      panelEl.insertAdjacentHTML(
        "beforeend",
        `<div id="notr-panel-${nun.role}" class="notr-panel notr-panel-nun notr-player-${nun.color}">
  <div class="notr-portrait"></div>
  <div class="notr-title">${this.emoji(nun.role)}${_(nun.roleName)}</div>
  <div class="notr-locations">
    <div title="${_("Current Location")}"><span class="notr-label notr-icon notr-icon-location"></span><div id="notr-panel-${nun.role}-location">${nun.location}</div></div>
    <div id="notr-panel-${nun.role}-path" title="${_("Path")}">${path}</div>
  </div>
  <div class="notr-locations">
    <div title="${_("Move")}"><span class="notr-label notr-icon notr-icon-move"></span><div id="notr-panel-${nun.role}-move">${_(nun.move?.actionName || "-")}</div></div>
    <div title="${_("Noise")}"><span class="notr-label notr-icon notr-icon-noise"></span><div id="notr-panel-${nun.role}-noise">${noise}</div></div>
  </div>
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
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceAction", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
    this.bga.gameui.notifqueue.setIgnoreNotificationCheck("noviceCaught", (notif) => notif.args.recap && notif.args.player_id == this.bga.players.getCurrentPlayerId());
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
    holderEl.insertAdjacentHTML("beforeend", `<div class="notr-tag notr-player-${novice.color} notr-noise notr-noise-${novice.playerId}" title="${novice.playerName}"><span class="notr-icon notr-icon-noise"></span></div>`);
  }

  addNoviceVanish(novice, location) {
    const holderEl = this.getHolderEl(location);
    holderEl.insertAdjacentHTML("beforeend", `<div class="notr-tag notr-player-${novice.color} notr-vanish notr-vanish-${novice.playerId}" title="${novice.playerName}"><span class="notr-icon notr-icon-vanish"></span></div>`);
  }

  async notif_clearNoise(args) {
    console.log("doing notif_clearNoise", args);
    let els = document.getElementsByClassName("notr-noise");
    while (els.length > 0) {
      els[0].remove();
    }
    els = document.getElementsByClassName("notr-vanish");
    while (els.length > 0) {
      els[0].remove();
    }
  }

  async notif_noviceAction(args) {
    console.log("doing notif_noviceAction", args);
    const el = document.getElementById(`notr-panel-${args.player_id}-move`);
    if (el != null) {
      el.innerText = _(args.actionName);
    }
  }

  async notif_noviceCaught(args) {
    console.log("doing notif_noviceCaught", args);
    const novice = this.getNovice(args.player_id2 || args.player_id);
    const caughtEl = document.getElementById(`notr-title-${novice.playerId}`);
    novice.caught = args.caught;
    if (novice.caught) {
      novice.hasWish = false;
      this.bga.sounds.play("caught");
      this.bga.gameui.disableNextMoveSound();
      if (novice.playerId == this.bga.players.getCurrentPlayerId()) {
        const myWishEl = document.getElementById("notr-my-wish");
        if (myWishEl == null) {
          const boardEl = document.getElementById("notr-board");
          this.addMyWish(boardEl, novice);
        }
      }
      caughtEl.innerText = _("Caught");
      caughtEl.classList.add("notr-caught");
    } else {
      caughtEl.innerText = _("On The Run");
      caughtEl.classList.remove("notr-caught");
    }
  }

  async notif_noviceKey(args) {
    const novice = this.getNovice(args.player_id);
    novice.hasKey = args.hasKey;
    const el = document.getElementById(`notr-panel-${novice.playerId}-key`);
    if (el != null) {
      el.innerHTML = novice.hasKey ? `<span class="notr-icon notr-icon-circle-yes"></span>` : novice.keyLocation || "-";
    }
    if (novice.playerId == this.bga.players.getCurrentPlayerId()) {
      const myKeyEl = document.getElementById("notr-my-key");
      if (myKeyEl != null) {
        myKeyEl.remove();
      }
    }
  }

  async notif_noviceMove(args) {
    console.log("doing notif_noviceMove", args);
    const novice = this.getNovice(args.player_id);
    const oldLocation = novice.location;
    novice.location = args.visibleLocation || args.location;

    const locationEl = document.getElementById(`notr-panel-${novice.playerId}-location`);
    if (locationEl != null) {
      locationEl.innerText = novice.location;
    }
    const noviceEl = document.getElementById("notr-novice-" + novice.playerId);
    if (noviceEl == null) {
      console.error(`#notr-novice-${novice.playerId} not found`);
      return;
    }
    noviceEl.classList.remove(...this.classLocations);
    noviceEl.classList.add("notr-" + novice.location);
    const boardEl = document.getElementById("notr-board");
    this.playerOffset(boardEl, oldLocation);
    this.playerOffset(boardEl, novice.location);
  }

  async notif_noviceNoise(args) {
    console.log("doing notif_noviceNoise", args);
    this.bga.sounds.play("noise" + (Math.floor(Math.random() * 4) + 1));
    this.bga.gameui.disableNextMoveSound();
    const novice = this.getNovice(args.player_id);
    this.addNoviceNoise(novice, args.noiseLocation);
  }

  async notif_noviceNoiseUndo(args) {
    console.log("doing notif_noviceNoiseUndo", args);
    const noiseEls = document.getElementsByClassName(`notr-noise-${args.player_id}`);
    // Remove all noise
    while (noiseEls.length > 0) {
      noiseEls[0].remove();
    }
    // Add remaining noise
    const novice = this.getNovice(args.player_id);
    if (args.noiseTokens) {
      for (let location of args.noiseTokens) {
        this.addNoviceNoise(novice, location);
      }
    }
  }

  async notif_noviceRoll(args) {
    console.log("doing notif_noviceRoll", args);
    const el = document.getElementById(`notr-panel-${args.player_id}-noise`);
    if (el != null) {
      el.innerText = args.noiseTotal;
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
    if (args.player_id != this.bga.players.getCurrentPlayerId()) {
      await this.notif_noviceMove({ player_id: args.player_id, location: novice.startLocation });
    }
  }

  async notif_noviceWish(args) {
    const novice = this.getNovice(args.player_id);
    novice.hasWish = args.hasWish;
    const el = document.getElementById(`notr-panel-${novice.playerId}-key`);
    if (el != null) {
      el.innerHTML = novice.hasKey ? `<span class="notr-icon notr-icon-circle-yes"></span>` : novice.wishLocation || "-";
    }
    if (novice.playerId == this.bga.players.getCurrentPlayerId()) {
      const myWishEl = document.getElementById("notr-my-wish");
      if (novice.hasWish && myWishEl != null) {
        myWishEl.remove();
      } else if (!novice.hasWish && myWishEl == null) {
        const boardEl = document.getElementById("notr-board");
        this.addMyWish(boardEl, novice);
      }
    }
  }

  async notif_nunAction(args) {
    console.log("doing notif_nunAction", args);
    const moveEl = document.getElementById(`notr-panel-${args.role}-move`);
    if (moveEl != null) {
      moveEl.innerText = _(args.actionName);
    }
    const noiseEl = document.getElementById(`notr-panel-${args.role}-noise`);
    if (noiseEl != null) {
      noiseEl.innerHTML = args.action == "walk" ? '<span class="notr-icon notr-icon-circle-yes"></span>' : '<span class="notr-icon notr-icon-circle-no"></span>';
    }
  }

  async notif_nunMove(args) {
    console.log("doing notif_nunMove", args);
    const nun = this.getNun(args.role);
    const oldLocation = nun.location;
    nun.location = args.location;

    const locationEl = document.getElementById(`notr-panel-${nun.role}-location`);
    locationEl.innerText = nun.location;
    const nunEl = document.getElementById(`notr-nun-${nun.role}`);
    if (nunEl == null) {
      console.error(`#notr-nun-${nun.role} not found`);
      return;
    }
    nunEl.classList.remove(...this.classLocations);
    nunEl.classList.add("notr-" + nun.location);
    const boardEl = document.getElementById("notr-board");
    this.playerOffset(boardEl, oldLocation);
    this.playerOffset(boardEl, nun.location);
  }

  async notif_nunPath(args) {
    console.log("doing notif_nunPath", args);
    const nun = this.getNun(args.role);
    nun.path = args.path;
    nun.pathColor = args.pathColor;
    nun.pathDestination = args.pathDestination;
    nun.pathOrigin = args.pathOrigin;

    const el = document.getElementById(`notr-panel-${nun.role}-path`);
    if (el != null) {
      el.innerHTML = `<span class="notr-tag notr-path-${nun.pathColor}"><span class="notr-icon notr-icon-path-${nun.pathColor}"></span></span><div>${nun.pathOrigin}▸${nun.pathDestination}</div>`;
      this.bga.gameui.removeTooltip(el.id);
      this.bga.gameui.addTooltipHtml(el.id, `<div class="notr-path-image notr-path-${nun.path}"></div>`);
    }
  }

  async notif_nunRoll(args) {
    console.log("doing notif_nunRoll", args);
    const nun = this.getNun(args.role);
    nun.noiseTotal = args.noiseTotal;

    const el = document.getElementById(`notr-panel-${nun.role}-noise`);
    if (el != null) {
      el.innerText = args.noiseTotal;
    }
  }

  async notif_round(args) {
    if (args.round == args.roundMax - 1) {
      this.bga.gameArea.addLastTurnBanner();
    }
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  addMyStart(boardEl, novice) {
    boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-start" class="notr-my notr-player-${novice.color} notr-${novice.startLocation}" title="${_("Start Location")}"><span class="notr-icon notr-icon-start"></span></div>`);
  }

  addMyKey(boardEl, novice) {
    boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-key" class="notr-my notr-player-${novice.color} notr-${novice.keyLocation}" title="${_("Key Location")}"><span class="notr-icon notr-icon-key"></span></div>`);
  }

  addMyWish(boardEl, novice) {
    boardEl.insertAdjacentHTML("beforeend", `<div id="notr-my-wish" class="notr-my notr-player-${novice.color} notr-${novice.wishLocation}" title="${_("Secret Wish Location")}"><span class="notr-icon notr-icon-wish"></span></div>`);
  }

  playerOffset(boardEl, location) {
    boardEl.querySelectorAll(`.notr-player.notr-${location}`).forEach((el, i) => el.classList.toggle("notr-offset", i > 0));
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
        if (args.location1) {
          args.location1 = `<b>${args.location1}</b>`;
        }
        if (args.location2) {
          args.location2 = `<b>${args.location2}</b>`;
        }
        if (args.noiseLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.noiseLocation = `<b><span class="notr-tag notr-player-${novice.color}"><span class="notr-icon notr-icon-noise"></span></span> ${args.noiseLocation}</b>`;
        }
        if (args.pathName && args.path && args.pathColor && args.pathDestination && args.pathOrigin) {
          args.pathName = `<b><span class="notr-tag notr-path-${args.pathColor}"><span class="notr-icon notr-icon-path-${args.pathColor}"></span></span> ${args.pathOrigin}▸${args.pathDestination}</b>`;
          log += `<div class="notr-notify notr-path-image notr-path-${args.path}"></div>`;
        }
        if (args.roleName && args.role) {
          args.roleName = `${this.emoji(args.role)}${args.roleName}`;
        }
        if (args.roll) {
          args.roll = args.rollAnimate ? this.html_dieAnimate(args.roll) : this.html_dieSingle(args.roll);
        }
        if (args.startLocation) {
          args.startLocation = `<b><span class="notr-icon notr-icon-start"></span> ${args.startLocation}</b>`;
        }
        if (args.vanishLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.vanishLocation = `<b><span class="notr-tag notr-player-${novice.color}"><span class="notr-icon notr-icon-vanish"></span></span> ${args.vanishLocation}</b>`;
        }
        if (args.visibleLocation) {
          const novice = this.gamedatas.novices[args.player_id] || {};
          args.visibleLocation = `<b><span class="notr-icon notr-icon-visible"></span> ${args.visibleLocation}</b>`;
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

export class NoviceMovePrivateState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      // Actions
      const novice = this.game.getNovice();
      for (const action in args.actions) {
        const info = args.actions[action];
        const noiseText = info.noise > 0 ? "+" + info.noise : info.noise;
        const distances = info.min == info.max ? info.min : `${info.min} - ${info.max}`;
        this.bga.statusBar.addActionButton(
          `<div class="notr-move-action">
  <div class="notr-move-icon notr-move-${action}"></div>
  <div>
    <div>${info.name}</div>
    <div class="notr-move-info">${_("Distance")}: ${distances}</div>
    <div class="notr-move-info">${_("Noise")}: ${noiseText}</div>
  </div>
</div>`,
          () => this.bga.actions.performAction("actConfirm", { confirmAction: action }),
          { disabled: info.disabled },
        );
      }
      if (args.blessing == "move") {
        this.bga.statusBar.addActionButton(_("Blessing: +1"), () => this.bga.actions.performAction("actBlessingMove"), { color: "secondary" });
      }
      if (args.undo) {
        this.bga.statusBar.addActionButton(_("Undo"), () => this.bga.actions.performAction("actUndo"), { color: "secondary" });
      }

      let specialMessage = null;
      let confirmMessage = null;
      if (!novice.hasKey && novice.location == novice.keyLocation) {
        // key
        const args = this.game.bgaFormatText("", {
          keyLocation: novice.keyLocation,
        }).args;
        specialMessage = this.bga.gameui.format_string(_("You can pick up your key at ${keyLocation}"), args);
      } else if (!novice.caught && !novice.hasWish && novice.location == novice.wishLocation) {
        // wish
        const args = this.game.bgaFormatText("", {
          wishLocation: novice.wishLocation,
        }).args;
        specialMessage = this.bga.gameui.format_string(_("You can pick up your secret wish at ${wishLocation}"), args);
      } else if (novice.hasWish && novice.location == novice.startLocation) {
        // win
        const args = this.game.bgaFormatText("", {
          startLocation: novice.startLocation,
        }).args;
        specialMessage = this.bga.gameui.format_string(_("You can end the game at ${startLocation}"), args);
      }
      const specialEl = document.getElementById("notr-special");
      if (specialMessage) {
        confirmMessage = `${specialMessage}<br><br>${_("Do you want to keep moving instead?")}`;
        if (!specialEl) {
          const gaEl = document.getElementById("generalactions");
          gaEl.insertAdjacentHTML("beforebegin", `<div id="notr-special">${specialMessage}</div>`);
        } else {
          specialEl.innerHTML = specialMessage;
        }
      } else if (specialEl) {
        specialEl.remove();
      }

      // Board possible moves
      const boardEl = document.getElementById("notr-board");
      for (const i in args.possible) {
        const move = args.possible[i];
        const action = move.actions[0];
        let str = move.distance;
        if (!novice.hasKey && move.location == novice.keyLocation) {
          str = `<span class="notr-icon notr-icon-key"></span>`;
        } else if (!novice.hasWish && move.location == novice.wishLocation) {
          str = `<span class="notr-icon notr-icon-wish"></span>`;
        }
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${move.location}" class="notr-possible notr-possible-${action} notr-${move.location}">${str}</div>`);
        const el = document.getElementById(`notr-possible-${move.location}`);
        el.addEventListener("click", () => {
          if (confirmMessage) {
            this.bga.dialogs.confirmation(confirmMessage).then((result) => {
              if (result) {
                this.bga.actions.performAction("actMove", { location: move.location });
              }
            });
          } else {
            this.bga.actions.performAction("actMove", { location: move.location });
          }
        });
      }
    }
  }

  onLeavingState(args, isCurrentPlayerActive) {
    const specialEl = document.getElementById("notr-special");
    if (specialEl) {
      specialEl.remove();
    }
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-possible")) {
      el.remove();
    }
  }
}

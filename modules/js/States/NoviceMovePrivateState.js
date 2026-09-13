export class NoviceMovePrivateState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      const novice = this.game.getNovice();
      // Actions
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
      if (args.undo) {
        this.bga.statusBar.addActionButton(_("Undo"), () => this.bga.actions.performAction("actUndo"), { color: "secondary" });
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
          let dialog = null;
          console.log("novice.location", novice.location, "novice.keyLocation", novice.keyLocation, "novice.wishLocation", novice.wishLocation);
          if (!novice.hasKey && novice.location == novice.keyLocation) {
            dialog = this.bga.gameui.format_string(
              _("If you keep moving, you won't pick up your key at ${keyLocation}."),
              this.game.bgaFormatText("", {
                keyLocation: novice.keyLocation,
              }).args,
            );
          } else if (!novice.hasWish && novice.location == novice.wishLocation) {
            dialog = this.bga.gameui.format_string(
              _("If you keep moving, you won't pick up your secret wish at ${wishLocation}."),
              this.game.bgaFormatText("", {
                wishLocation: novice.wishLocation,
              }),
            );
          }
          if (dialog) {
            this.bga.dialogs.confirmation(dialog).then((result) => {
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
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-possible")) {
      el.remove();
    }
  }
}

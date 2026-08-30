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
      this.bga.statusBar.addActionButton(_("Restart Turn"), () => this.bga.actions.performAction("actReset"), { color: "red" });

      // Board possible moves
      const boardEl = document.getElementById("notr-board");
      for (const i in args.possible) {
        const move = args.possible[i];
        const action = move.actions[0];
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${move.location}" class="notr-possible notr-possible-${action} notr-${move.location}">${move.distance}</div>`);
        const el = document.getElementById(`notr-possible-${move.location}`);
        el.addEventListener("click", () => this.bga.actions.performAction("actMove", { location: move.location }));
      }
    }
  }

  onLeavingState(args, isCurrentPlayerActive) {
    // Cleanup board possible moves
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-possible")) {
      el.remove();
    }
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {}
}

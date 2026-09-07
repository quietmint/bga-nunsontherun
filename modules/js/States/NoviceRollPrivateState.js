export class NoviceRollPrivateState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      // Board possible noise
      const boardEl = document.getElementById("notr-board");
      for (const location in args.possible) {
        const role = args.possible[location][0];
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${location}" class="notr-noclick notr-possible notr-possible-${role} notr-${location}"><span class="notr-icon notr-icon-noise"></span></div>`);
      }

      // Actions
      if (args.heard) {
        this.bga.statusBar.addActionButton(_("Make Noise"), () => this.bga.actions.performAction("actContinue"), { color: "red" });
      } else {
        this.bga.statusBar.addActionButton(_("No Noise"), () => this.bga.actions.performAction("actContinue"));
      }
      this.bga.statusBar.addActionButton(_("Blessing: -1"), () => this.bga.actions.performAction("actBlessingAdjust"), { color: "secondary" });
      this.bga.statusBar.addActionButton(_("Blessing: Reroll"), () => this.bga.actions.performAction("actBlessingReroll"), { color: "secondary" });
    }
  }

  onLeavingState(args, isCurrentPlayerActive) {
    const containerEl = document.getElementById("notr-roll");
    if (containerEl != null) {
      containerEl.remove();
    }

    // Cleanup board possible noise
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-possible")) {
      el.remove();
    }
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {}
}

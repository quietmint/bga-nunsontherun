export class NoviceNoisePrivateState {
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
        // Action button
        this.bga.statusBar.addActionButton(`<span class="notr-icon notr-icon-noise"></span> ${location}`, () => this.game.performActionWrapper("actNoise", { location }));

        // Board space
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${location}" class="notr-possible notr-possible-${role} notr-${location}"><span class="notr-icon notr-icon-noise"></span></div>`);
        const el = document.getElementById(`notr-possible-${location}`);
        el.addEventListener("click", () => this.game.performActionWrapper("actNoise", { location }));
      }

      if (args.possible.length == 0) {
        this.bga.statusBar.addActionButton(_("Confirm"), () => this.game.performActionWrapper("actConfirm"));
      }

      if (args.undo) {
        this.bga.statusBar.addActionButton(_("Undo"), () => this.game.performActionWrapper("actUndo"), { color: "secondary" });
      }
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

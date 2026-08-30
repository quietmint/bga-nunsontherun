export class NoviceNoisePrivateState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      const totalText = this.bga.gameui.format_string(_("${noise} spaces away"), args);
      // Die
      const gaEl = document.getElementById("generalactions");
      gaEl.insertAdjacentHTML(
        "afterend",
        `<div id="notr-roll">${this.game.html_dice()} ${args.formula} (${args.action}) = ${totalText}</div>`,
      );
      setTimeout(() => {
        document.getElementById("notr-die").classList.add("show-" + args.roll);
      }, 50);

      // Board possible noise
      const boardEl = document.getElementById("notr-board");
      for (const location in args.possible) {
        const role = args.possible[location][0];
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${location}" class="notr-possible notr-possible-${role} notr-${location}"><span class="notr-icon notr-icon-noise"></span></div>`);
        const el = document.getElementById(`notr-possible-${location}`);
        el.addEventListener("click", () => this.bga.actions.performAction("actNoise", { location }));
      }

      if (args.possible.length == 0) {
        this.bga.statusBar.addActionButton(_("No Noise Token"), () => this.bga.actions.performAction("actSilent"));
      }

      this.bga.statusBar.addActionButton(_("Go Back"), () => this.bga.actions.performAction("actBack"), { color: "secondary" });
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

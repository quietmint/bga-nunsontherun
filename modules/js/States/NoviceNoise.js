export class NoviceNoise {
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
        `<div id="notr-roll">
  <div id="notr-die" class="notr-die">
    <div class="face face-1"><div class="pip pip-1"></div></div>
    <div class="face face-2"><div class="pip pip-1"></div><div class="pip pip-2"></div></div>
    <div class="face face-3"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div></div>
    <div class="face face-4"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div></div>
    <div class="face face-5"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div><div class="pip pip-5"></div></div>
    <div class="face face-6"><div class="pip pip-1"></div><div class="pip pip-2"></div><div class="pip pip-3"></div><div class="pip pip-4"></div><div class="pip pip-5"></div><div class="pip pip-6"></div></div>
  </div> ${args.formula} (${args.action}) = ${totalText}
</div>`,
      );
      setTimeout(() => {
        document.getElementById("notr-die").classList.add("show-" + args.roll);
      }, 50);

      // Board possible noise
      const boardEl = document.getElementById("notr-board");
      for (const role in args.possible) {
        for (const location in args.possible[role]) {
          boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${location}" class="notr-possible notr-possible-noise notr-${location}">👂</div>`);
          const el = document.getElementById(`notr-possible-${location}`);
          el.addEventListener("click", () => this.bga.actions.performAction("actNoise", { location }));
        }
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

export class NoviceMove {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  /**
   * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
   */
  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      const boardEl = document.getElementById("notr-board");
      for (const i in args.possible) {
        const move = args.possible[i];
        boardEl.insertAdjacentHTML("beforeend", `<div id="notr-possible-${move.location}" class="notr-possible notr-${move.location}">${move.spaces.length - 1}</div>`);
        const el = document.getElementById("notr-possible-" + move.location);
        el.addEventListener("click", () => this.onClickMove(move.location));
      }

      // this.bga.statusBar.setTitle(_("${you} must play a card or pass"));
      this.bga.statusBar.addActionButton(_("Done"), () => this.bga.actions.performAction("actDone"));
      this.bga.statusBar.addActionButton(_("Restart Turn"), () => this.bga.actions.performAction("actReset"), { color: "red" });
    }
  }

  /**
   * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
   */
  onLeavingState(args, isCurrentPlayerActive) {
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-possible")) {
      el.remove();
    }
  }

  /**
   * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
   * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
   * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
   */
  onPlayerActivationChange(args, isCurrentPlayerActive) {}

  onClickMove(location) {
    console.log("onClickMove", location);
    this.bga.actions.performAction("actMove", { location });
  }
}

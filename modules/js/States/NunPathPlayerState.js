export class NunPathPlayerState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  /**
   * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
   */
  onEnteringState(args, isCurrentPlayerActive) {
    this.onPlayerActivationChange(args, isCurrentPlayerActive);
    if (isCurrentPlayerActive) {
      // Board possible paths
      const boardEl = document.getElementById("notr-board");
      for (const path in args.possible) {
        const p = args.possible[path];
        let destination = p.path[0];
        if (destination == args.start) {
          destination = p.path.at(-1);
        }
        let holderEl = document.getElementById(`notr-path-holder-${destination}`);
        if (holderEl == null) {
          boardEl.insertAdjacentHTML("beforeend", this.game.html_pathHolder(destination));
          holderEl = document.getElementById(`notr-path-holder-${destination}`);
        }
        holderEl.insertAdjacentHTML("beforeend", this.game.html_pathToken({ id: path, color: p.color, destination }));
        const pathEl = document.getElementById(`notr-path-${path}`);
        pathEl.addEventListener("click", () => this.bga.actions.performAction("actPath", { path }));
      }
    }
  }

  /**
   * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
   * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
   * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
   */
  onPlayerActivationChange(args, isCurrentPlayerActive) {}
}

export class NovicesMove {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  /**
   * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
   */
  onEnteringState(args, isCurrentPlayerActive) {
    // this.bga.statusBar.addActionButton(_("Reset"), () => this.bga.actions.performAction("actReset", {}, { checkAction: false, checkPossibleActions: true }), { color: "secondary" });
    // this.onPlayerActivationChange(args, isCurrentPlayerActive);
  }

  /**
   * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
   * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
   * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
   */
  onPlayerActivationChange(args, isCurrentPlayerActive) {
    if (!isCurrentPlayerActive && this.game.isNovice()) {
      this.bga.players.getCurrentPlayerId();
      this.bga.statusBar.addActionButton(_("Go Back"), () => this.bga.actions.performAction("actActivate", {}, { checkAction: false, checkPossibleActions: true }), { color: "secondary" });
    }
  }
}

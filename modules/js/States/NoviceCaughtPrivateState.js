export class NoviceCaughtPrivateState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      // Actions
      this.bga.statusBar.addActionButton(_("On The Run"), () => this.bga.actions.performAction("actConfirm", { caught: false }));
      this.bga.statusBar.addActionButton(_("Caught"), () => this.bga.actions.performAction("actConfirm", { caught: true }), { color: "red" });
    }
  }
}

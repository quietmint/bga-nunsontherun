export class NunRollPlayerState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      this.bga.statusBar.addActionButton(_("Continue"), () => this.bga.actions.performAction("actContinue"));
      this.bga.statusBar.addActionButton(_("Blessing: +1"), () => this.bga.actions.performAction("actBlessingAdjust"), { color: "secondary" });
      this.bga.statusBar.addActionButton(_("Blessing: Reroll"), () => this.bga.actions.performAction("actBlessingReroll"), { color: "secondary" });
    }
  }
}

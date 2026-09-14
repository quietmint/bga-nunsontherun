export class NunRollPlayerState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      this.bga.statusBar.addActionButton(_("Confirm"), () => this.bga.actions.performAction("actConfirm"));
      if (args.blessing == "adjust") {
        this.bga.statusBar.addActionButton(_("Blessing: +1"), () => this.bga.actions.performAction("actBlessingAdjust"), { color: "secondary" });
      } else if (args.blessing == "reroll") {
        this.bga.statusBar.addActionButton(_("Blessing: Reroll"), () => this.bga.actions.performAction("actBlessingReroll"), { color: "secondary" });
      }
    }
  }
}

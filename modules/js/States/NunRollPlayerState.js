export class NunRollPlayerState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      this.bga.statusBar.addActionButton(_("Confirm"), () => this.game.performActionWrapper("actConfirm"));
      if (args.blessing == "adjust") {
        this.bga.statusBar.addActionButton(_("Blessing: +1"), () => this.game.performActionWrapper("actBlessingAdjust"), { color: "secondary" });
      } else if (args.blessing == "reroll") {
        this.bga.statusBar.addActionButton(_("Blessing: Reroll"), () => this.game.performActionWrapper("actBlessingReroll"), { color: "secondary" });
      }
    }
  }
}

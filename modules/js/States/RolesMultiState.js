export class RolesMultiState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    this.onPlayerActivationChange(args, isCurrentPlayerActive);
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      this.bga.statusBar.removeActionButtons();
      this.bga.statusBar.addActionButton(_("Novice"), () => this.game.performActionWrapper("actChoose", { role: "novice" }));
      this.bga.statusBar.addActionButton(_("Nun"), () => this.game.performActionWrapper("actChoose", { role: "nun" }));
    }
  }
}

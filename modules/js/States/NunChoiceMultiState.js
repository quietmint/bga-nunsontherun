export class NunChoiceMultiState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      this.bga.statusBar.addActionButton("⚫ " + _("Abbess"), () => this.bga.actions.performAction("actChoose", { role: "abbess" }));
      this.bga.statusBar.addActionButton("⚪ " + _("Prioress"), () => this.bga.actions.performAction("actChoose", { role: "prioress" }));
    }
  }
}

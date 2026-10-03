export class NoviceTurnMultiState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {
    // if (!isCurrentPlayerActive && this.game.isNovice()) {
    //   this.bga.statusBar.addActionButton(_("Go Back"), () => this.game.performActionWrapper("actActivate", {}, { checkAction: false, checkPossibleActions: true }), { color: "secondary" });
    // }
  }
}

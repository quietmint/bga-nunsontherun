export class NunChoiceMultiState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onPlayerActivationChange(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      Object.values(this.game.gamedatas.nuns).forEach((nun) => {
        const title = this.bga.gameui.format_string(_("${roleName} ${player_name}"), {
          roleName: this.game.emoji(nun.role) + _(nun.roleName),
          player_name: nun.playerName,
        });
        this.bga.statusBar.addActionButton(title, () => this.game.performActionWrapper("actChoose", { role: nun.role }));
      });
    }
  }
}

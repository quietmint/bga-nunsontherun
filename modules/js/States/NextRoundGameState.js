export class NextRoundGameState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    for (const el of document.getElementsByClassName("notr-panel-move")) {
      el.innerText = "-";
    }
    for (const el of document.getElementsByClassName("notr-panel-noise")) {
      el.innerText = "-";
    }
  }
}

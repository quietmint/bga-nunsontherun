export class NunPathPlayerState {
  constructor(game, bga) {
    this.game = game;
    this.bga = bga;
  }

  onEnteringState(args, isCurrentPlayerActive) {
    if (isCurrentPlayerActive) {
      for (const path in args.possible) {
        const p = args.possible[path];
        const tooltipHtml = `<div class="notr-path-image notr-path-${p.path}"></div>`;

        // Action button with tooltip
        this.bga.statusBar.addActionButton(`<div class="notr-tag notr-${p.color}"><span class="notr-icon notr-icon-path-${p.color}"></span> ${p.origin}▸${p.destination}</div>`, () => this.bga.actions.performAction("actPath", { path }), { id: `action_path-${path}` });
        this.bga.gameui.addTooltipHtml(`action_path-${path}`, tooltipHtml);

        // Board space with tooltip
        const holderEl = this.game.getHolderEl(p.destination);
        holderEl.insertAdjacentHTML("beforeend", `<div id="notr-path-${path}" class="notr-tag notr-tag-path notr-${p.color}"><span class="notr-icon notr-icon-path-${p.color}"></span></div>`);
        const pathEl = document.getElementById(`notr-path-${path}`);
        pathEl.addEventListener("click", () => this.bga.actions.performAction("actPath", { path }));
        this.bga.gameui.addTooltipHtml(pathEl.id, tooltipHtml);
      }
    }
  }

  onLeavingState(args, isCurrentPlayerActive) {
    const boardEl = document.getElementById("notr-board");
    for (const el of boardEl.querySelectorAll(".notr-tag-path")) {
      el.remove();
    }
  }
}

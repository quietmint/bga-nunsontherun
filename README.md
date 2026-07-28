BGA projects documentation : https://en.doc.boardgamearena.com/Studio

The PHP files will be in `modules/php`
The JS files will be in `modules/js`
The CSS file is in the root folder
The JSONC files (project configuration) are in the root folder

If you want to use TypeScript, rename `src-disabled` to `src`, run `npm i` once to populate the `node_modules` folder, then run `npm run watch:ts` when you work on the project so the TS files are automatically built into `modules/js/Game.js`.
See https://en.doc.boardgamearena.com/Using_Typescript_and_Scss for more details about the TypeScript setup.
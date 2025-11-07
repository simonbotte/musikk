// vetur.config.js
/** @type {import('vls').VeturConfig} */
module.exports = {
    // Plusieurs projets si besoin ; ici on cible seulement "front"
    projects: [
        {
            root: './front',                     // dossier du projet Vue
            tsconfig: './tsconfig.json',   // chemin vers le tsconfig du front
            package: './package.json',     // aide Vetur à résoudre deps/Types
            // Optionnel : auto-import de composants globaux pour les types
            globalComponents: [
                './components/**/*.vue',
                './layouts/**/*.vue'
            ]
        }
    ]
}

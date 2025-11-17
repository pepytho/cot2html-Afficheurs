# 📝 Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### À venir
- Support multi-langues (FR/EN)
- Mode plein écran automatique
- Thèmes personnalisables
- Export PDF des résultats

## [2.0.0] - 2024-01-XX

### ✨ Ajouté
- **Scroll fluide** avec interpolation temporelle (60 FPS)
- **Système de vitesse** avec 12 niveaux (0.2x à 2.4x)
- **Sauvegarde des préférences** dans cookie + URL
- **Rectangles de couleur** pour les matchs (style FencingTimeLive)
  - 6 couleurs distinctes : Jaune, Vert, Bleu, Rouge, Violet, Orange
- **En-têtes sticky** pour les phases de tableau (T128, T64, T32, etc.)
- **Affichage des noms complets** sans troncation
- **Lignes de connexion** légères et claires entre les matchs
- **Support touch** optimisé pour écrans tactiles
- **Pause intelligente** du scroll lors de l'interaction utilisateur
- **Transition fluide** vers le haut de page en fin de scroll

### 🔧 Amélioré
- **Performance du scroll** avec `requestAnimationFrame`
- **CSS optimisé** : suppression de tous les `!important`
- **Résolution des conflits** entre `const.css` et `style.css`
- **Consolidation des styles** : élimination des doublons
- **Lisibilité du code** : commentaires et structure améliorés
- **Compatibilité mobile** : `-webkit-overflow-scrolling: touch`
- **Affichage des clubs** : limité à 10 caractères avec style distinct

### 🐛 Corrigé
- Correction de l'affichage des noms tronqués dans les tableaux
- Correction des conflits CSS entre fichiers
- Correction de la stabilité du scroll automatique
- Correction de l'alignement des cellules de tableau
- Correction des lignes de connexion peu visibles
- Correction du positionnement des en-têtes lors du scroll

### 🗑️ Supprimé
- Tous les `!important` des fichiers CSS (200+ occurrences)
- Styles en double entre `const.css` et `style.css`
- Anciennes classes CSS non utilisées
- Code JavaScript obsolète

### 🔒 Sécurité
- Validation des entrées utilisateur
- Protection contre les injections XSS
- Échappement des données XML

## [1.5.0] - 2023-12-XX

### ✨ Ajouté
- Sélecteur de taille de tableau (T512 à T2)
- Sélecteur de vitesse de scroll
- Support des suites de tableaux (repêchages)
- Affichage des drapeaux de nationalité

### 🔧 Amélioré
- Interface utilisateur modernisée
- Thème sombre pour meilleure lisibilité
- Responsive design amélioré

### 🐛 Corrigé
- Problèmes d'affichage sur petits écrans
- Bugs de rafraîchissement automatique

## [1.0.0] - 2023-06-XX

### ✨ Ajouté
- Affichage des poules
- Affichage des tableaux éliminatoires
- Affichage des classements
- Liste de présence
- Page de sélection de compétition
- Rafraîchissement automatique
- Zoom dynamique
- Support BellePoule XML

### 🔧 Amélioré
- Structure du code
- Performance de chargement

## [0.5.0] - 2023-03-XX (Beta)

### ✨ Ajouté
- Version beta initiale
- Affichage basique des résultats
- Lecture des fichiers XML BellePoule

---

## Types de changements

- `✨ Ajouté` : Nouvelles fonctionnalités
- `🔧 Amélioré` : Améliorations de fonctionnalités existantes
- `🐛 Corrigé` : Corrections de bugs
- `🗑️ Supprimé` : Fonctionnalités supprimées
- `🔒 Sécurité` : Corrections de vulnérabilités
- `📝 Documentation` : Changements dans la documentation
- `⚡ Performance` : Améliorations de performance
- `♻️ Refactoring` : Refactoring de code
- `🎨 Style` : Changements de style/formatage

## Liens

- [Comparer les versions](https://github.com/votre-username/bellepoule-tv-display/compare)
- [Toutes les releases](https://github.com/votre-username/bellepoule-tv-display/releases)

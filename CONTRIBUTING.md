# 🤝 Guide de contribution

Merci de votre intérêt pour contribuer à BellePoule TV Display ! Ce document vous guidera à travers le processus de contribution.

## 📋 Table des matières

- [Code de conduite](#code-de-conduite)
- [Comment contribuer](#comment-contribuer)
- [Signaler un bug](#signaler-un-bug)
- [Proposer une fonctionnalité](#proposer-une-fonctionnalité)
- [Processus de Pull Request](#processus-de-pull-request)
- [Standards de code](#standards-de-code)
- [Structure des commits](#structure-des-commits)

## 📜 Code de conduite

En participant à ce projet, vous acceptez de respecter notre code de conduite :

- Soyez respectueux et inclusif
- Acceptez les critiques constructives
- Concentrez-vous sur ce qui est le mieux pour la communauté
- Faites preuve d'empathie envers les autres membres

## 🚀 Comment contribuer

### 1. Fork le projet

Cliquez sur le bouton "Fork" en haut à droite de la page GitHub.

### 2. Clonez votre fork

```bash
git clone https://github.com/votre-username/bellepoule-tv-display.git
cd bellepoule-tv-display
```

### 3. Créez une branche

```bash
git checkout -b feature/ma-nouvelle-fonctionnalite
```

Nommez votre branche selon le type de contribution :
- `feature/` : Nouvelle fonctionnalité
- `fix/` : Correction de bug
- `docs/` : Documentation
- `style/` : Formatage, CSS
- `refactor/` : Refactoring de code
- `test/` : Ajout de tests
- `chore/` : Maintenance

### 4. Faites vos modifications

- Écrivez du code propre et commenté
- Suivez les standards de code (voir ci-dessous)
- Testez vos modifications

### 5. Committez vos changements

```bash
git add .
git commit -m "feat: ajout de ma nouvelle fonctionnalité"
```

### 6. Poussez vers votre fork

```bash
git push origin feature/ma-nouvelle-fonctionnalite
```

### 7. Ouvrez une Pull Request

Allez sur GitHub et cliquez sur "New Pull Request".

## 🐛 Signaler un bug

Avant de signaler un bug, vérifiez qu'il n'a pas déjà été signalé dans les [issues](https://github.com/votre-username/bellepoule-tv-display/issues).

### Template de bug report

```markdown
**Description du bug**
Une description claire et concise du bug.

**Étapes pour reproduire**
1. Aller sur '...'
2. Cliquer sur '...'
3. Faire défiler jusqu'à '...'
4. Voir l'erreur

**Comportement attendu**
Ce qui devrait se passer.

**Comportement actuel**
Ce qui se passe réellement.

**Captures d'écran**
Si applicable, ajoutez des captures d'écran.

**Environnement**
- OS: [ex: Windows 10]
- Navigateur: [ex: Chrome 120]
- Version PHP: [ex: 8.1]
- Version du projet: [ex: 2.0.0]

**Informations supplémentaires**
Tout autre contexte utile.
```

## 💡 Proposer une fonctionnalité

### Template de feature request

```markdown
**Problème à résoudre**
Une description claire du problème que cette fonctionnalité résoudrait.

**Solution proposée**
Une description claire de ce que vous voulez qu'il se passe.

**Alternatives considérées**
Autres solutions ou fonctionnalités que vous avez envisagées.

**Contexte additionnel**
Tout autre contexte ou captures d'écran.
```

## 🔄 Processus de Pull Request

### Checklist avant de soumettre

- [ ] Mon code suit les standards du projet
- [ ] J'ai commenté mon code, particulièrement les parties complexes
- [ ] J'ai mis à jour la documentation si nécessaire
- [ ] Mes changements ne génèrent pas de nouveaux warnings
- [ ] J'ai testé sur plusieurs navigateurs
- [ ] J'ai vérifié qu'il n'y a pas de conflits avec la branche main

### Description de la PR

Votre Pull Request doit inclure :

1. **Titre clair** : Résumé en une ligne
2. **Description** : Explication détaillée des changements
3. **Type de changement** :
   - [ ] Bug fix
   - [ ] Nouvelle fonctionnalité
   - [ ] Breaking change
   - [ ] Documentation
4. **Tests effectués** : Liste des tests réalisés
5. **Captures d'écran** : Si applicable

### Exemple de description de PR

```markdown
## Description
Ajout d'un système de scroll fluide avec interpolation temporelle.

## Type de changement
- [x] Nouvelle fonctionnalité
- [ ] Bug fix
- [ ] Breaking change

## Changements effectués
- Ajout de `scroll-behavior: smooth` sur tous les conteneurs
- Implémentation d'un système de delta time pour le scroll
- Ajout de la sauvegarde de vitesse dans l'URL

## Tests effectués
- [x] Chrome 120 (Windows)
- [x] Firefox 121 (Windows)
- [x] Safari 17 (macOS)
- [x] Chrome Mobile (Android)

## Captures d'écran
[Ajoutez vos captures ici]
```

## 📝 Standards de code

### PHP

```php
<?php
// Utilisez les standards PSR-12
// Indentation : 4 espaces
// Accolades sur nouvelle ligne pour les fonctions

function maFonction($param1, $param2) 
{
    if ($condition) {
        // Code ici
    }
    
    return $resultat;
}

// Commentaires en français
// Noms de variables explicites
$nombreDeTireurs = 10;
```

### JavaScript

```javascript
// Utilisez ES6+
// Indentation : 4 espaces
// camelCase pour les variables et fonctions

function maFonction(param1, param2) {
    if (condition) {
        // Code ici
    }
    
    return resultat;
}

// Commentaires en français
// Utilisez const/let au lieu de var
const nombreDeTireurs = 10;
```

### CSS

```css
/* Indentation : 4 espaces */
/* Pas de !important sauf cas exceptionnel */
/* Commentaires en français */

.ma-classe {
    display: flex;
    align-items: center;
    /* Propriétés dans l'ordre alphabétique si possible */
}

/* Utilisez les variables CSS */
:root {
    --couleur-principale: #0A1E3F;
}
```

### Conventions de nommage

- **Fichiers** : `snake_case.php`, `kebab-case.css`, `camelCase.js`
- **Classes CSS** : `kebab-case` ou `camelCase`
- **Variables PHP** : `$camelCase`
- **Variables JS** : `camelCase`
- **Constantes** : `UPPER_SNAKE_CASE`
- **Fonctions** : `camelCase`

## 📦 Structure des commits

Utilisez les [Conventional Commits](https://www.conventionalcommits.org/) :

```
<type>(<scope>): <description>

[corps optionnel]

[footer optionnel]
```

### Types de commits

- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation
- `style`: Formatage, CSS
- `refactor`: Refactoring
- `perf`: Amélioration de performance
- `test`: Ajout de tests
- `chore`: Maintenance, configuration

### Exemples

```bash
# Nouvelle fonctionnalité
git commit -m "feat(scroll): ajout du scroll fluide avec interpolation"

# Correction de bug
git commit -m "fix(tableau): correction affichage noms tronqués"

# Documentation
git commit -m "docs(readme): ajout section installation"

# Style
git commit -m "style(css): suppression des !important"

# Refactoring
git commit -m "refactor(functions): simplification de la fonction getMatchColor"
```

## 🧪 Tests

Avant de soumettre votre PR, testez sur :

### Navigateurs Desktop
- [ ] Chrome (dernière version)
- [ ] Firefox (dernière version)
- [ ] Edge (dernière version)
- [ ] Safari (si possible)

### Navigateurs Mobile
- [ ] Chrome Mobile
- [ ] Safari iOS
- [ ] Firefox Mobile

### Résolutions
- [ ] 1920x1080 (Full HD)
- [ ] 1366x768 (Laptop)
- [ ] 768x1024 (Tablette)
- [ ] 375x667 (Mobile)

## 📚 Ressources

- [Documentation BellePoule](http://betton.escrime.free.fr/index.php/documentation)
- [PHP Documentation](https://www.php.net/docs.php)
- [MDN Web Docs](https://developer.mozilla.org/)
- [Git Documentation](https://git-scm.com/doc)

## ❓ Questions

Si vous avez des questions, n'hésitez pas à :
- Ouvrir une [issue](https://github.com/votre-username/bellepoule-tv-display/issues)
- Contacter les mainteneurs
- Consulter la documentation

## 🙏 Merci !

Merci de contribuer à BellePoule TV Display ! Chaque contribution, petite ou grande, est appréciée.

---

Fait avec ❤️ pour la communauté de l'escrime

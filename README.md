# 🤺 BellePoule TV Display

> Affichage dynamique en temps réel des résultats de compétitions d'escrime pour écrans TV connectés

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

## 📋 Table des matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Captures d'écran](#captures-décran)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Personnalisation](#personnalisation)
- [Contribution](#contribution)
- [Licence](#licence)

## 🎯 À propos

**BellePoule TV Display** est une solution d'affichage dynamique conçue pour les compétitions d'escrime utilisant le logiciel open source [BellePoule](http://betton.escrime.free.fr/index.php/bellepoule). 

Ce projet permet d'afficher en temps réel sur des écrans TV connectés :
- Les résultats des poules
- Les tableaux éliminatoires
- Les classements
- Les listes de présence

Parfait pour les salles de compétition, les clubs d'escrime et les événements sportifs.

## ✨ Fonctionnalités

### 🖥️ Affichage Multi-Pages

- **Page de sélection** : Interface intuitive pour choisir la compétition à afficher
- **Poules** : Affichage des résultats de poules en temps réel
- **Tableaux éliminatoires** : Visualisation des brackets avec code couleur
- **Classements** : Classements intermédiaires et finaux
- **Liste de présence** : Vérification des participants

### 🎨 Interface Moderne

- **Design responsive** : S'adapte à toutes les tailles d'écran (TV, tablette)
- **Thème sombre** : Optimisé pour la lisibilité sur grand écran
- **Code couleur ** : 6 couleurs distinctes pour identifier les matchs

### 🔄 Défilement Automatique

- **Scroll fluide** : Interpolation temporelle pour un défilement ultra-fluide (60 FPS)
- **Vitesse ajustable** : 12 niveaux de vitesse (0.2x à 2.4x)
- **Pause intelligente** : Détection automatique de l'interaction utilisateur
- **Sauvegarde des préférences** : Cookie + URL pour mémoriser la vitesse
- **Boucle infinie** : Retour automatique en haut de page

### 📊 Affichage des Données

- **Noms complets** : Affichage des noms entiers sans troncation
- **Clubs** : Affichage des clubs (limité à 10 caractères)
- **Drapeaux** : Icônes de nationalité
- **Statuts** : Qualifié/Éliminé/Abandon/Expulsion en français
- **Scores en temps réel** : Mise à jour automatique

### ⚙️ Configuration Avancée

- **Sélecteur de taille de tableau** : Filtrage T512 à T2
- **Zoom dynamique** : Ajustement de la taille d'affichage
- **Rafraîchissement auto** : Mise à jour périodique des données
- **Multi-compétitions** : Support de plusieurs compétitions simultanées
- **Suites de tableaux** : Navigation entre tableau principal et repêchages

### 🎯 Optimisations Techniques

- **Performance** : Utilisation de `requestAnimationFrame` pour le scroll
- **Touch optimisé** : Support natif des écrans tactiles
- **Compatibilité** : Fonctionne sur tous les navigateurs modernes

## 📸 Captures d'écran

### Page de sélection
Interface de choix de compétition avec liste des événements disponibles.

### Tableaux éliminatoires
Affichage des brackets avec code couleur et lignes de connexion claires.

### Poules
Résultats de poules avec scores et classements en temps réel.

## 🔧 Prérequis

- **Serveur web** : Apache 2.4+ ou Nginx
- **PHP** : Version 7.4 ou supérieure
- **BellePoule** : Logiciel installé et configuré
- **Navigateur moderne** : Chrome, Firefox, Edge, Safari

## 📦 Installation

### 1. Cloner le dépôt

### 2. Configuration du serveur web

#### Apache

Placez le projet dans votre dossier `htdocs` ou `www` :

```bash
# Windows (XAMPP)
C:\xampp\htdocs\bellepoule-tv-display\

# Linux
/var/www/html/bellepoule-tv-display/

# macOS (MAMP)
/Applications/MAMP/htdocs/bellepoule-tv-display/
```

#### Nginx

Configurez un virtual host pointant vers le dossier du projet.

### 3. Configuration PHP

Assurez-vous que les extensions suivantes sont activées dans `php.ini` :

```ini
extension=xml
extension=simplexml
extension=dom
```



## ⚙️ Configuration

### Fichier `config.php`

Créez ou modifiez le fichier `config.php` à la racine :

```php
<?php
// Configuration de base
define('BELLEPOULE_PATH', '/chemin/vers/bellepoule/data/');
define('AUTO_REFRESH_INTERVAL', 30000); // 30 secondes
define('DEFAULT_ZOOM', 1.0);
define('DEFAULT_SCROLL_SPEED', 1.0);

// Compétitions disponibles
$competitions = [
    'competition1' => [
        'name' => 'Championnat Régional',
        'date' => '2024-01-15',
        'file' => 'championnat_regional.xml'
    ],
    // Ajoutez vos compétitions ici
];
?>
```

### Variables d'environnement

Vous pouvez également utiliser des variables d'environnement :

```bash
export BELLEPOULE_PATH="/chemin/vers/bellepoule/data/"
export AUTO_REFRESH=30000
```



## 🏗️ Structure du projet

```
bellepoule-tv-display/
├── index.php              # Point d'entrée principal
├── config.php             # Configuration
├── my6.php                # Logique d'affichage des tableaux
├── functions.php          # Fonctions utilitaires
├── tools.php              # Outils de traitement XML
├── css/
│   ├── const.css          # Styles principaux
│   └── style.css          # Styles spécifiques
├── js/
│   ├── scroll-refresh.js  # Système de scroll automatique
│   ├── functions.js       # Fonctions JavaScript
│   └── bracket-lines.js   # Lignes de connexion des tableaux
├── images/
│   └── flags/             # Drapeaux des pays
└── README.md              # Ce fichier
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Voici comment participer :

## 🐛 Signaler un bug

Ouvrez une [issue](https://github.com/votre-username/bellepoule-tv-display/issues) avec :
- Description détaillée du problème
- Étapes pour reproduire
- Captures d'écran si possible
- Version du navigateur et système d'exploitation

## 📝 Changelog

### Version 2.0.0 (2024-01-XX)

#### ✨ Nouvelles fonctionnalités
- Scroll fluide avec interpolation temporelle (60 FPS)
- Système de vitesse de scroll avec 12 niveaux
- Sauvegarde des préférences dans cookie + URL
- Rectangles de couleur pour les matchs 
- En-têtes sticky pour les phases de tableau
- Affichage des noms complets sans troncation
- Lignes de connexion légères et claires

#### 🔧 Améliorations
- Suppression de tous les `!important` des CSS
- Résolution des conflits entre fichiers CSS
- Optimisation des performances de scroll
- Support touch amélioré pour mobile/tablette

#### 🐛 Corrections
- Correction de l'affichage des noms tronqués
- Correction des conflits CSS entre const.css et style.css
- Amélioration de la stabilité du scroll automatique

### Version 1.0.0 (2023-XX-XX)
- Version initiale

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🙏 Remerciements

- **BellePoule** : Logiciel open source de gestion de compétitions d'escrime
- **Communauté FFE** : Pour les retours et suggestions


## 🔗 Liens utiles

- [BellePoule](http://betton.escrime.free.fr/index.php/bellepoule) - Logiciel de gestion de compétitions
- [Documentation BellePoule](http://betton.escrime.free.fr/index.php/documentation)
- [Fédération Française d'Escrime](https://www.escrime-ffe.fr/)

---

⭐ Si ce projet vous est utile, n'hésitez pas à lui donner une étoile sur GitHub !

Fait avec ❤️ pour la communauté de l'escrime

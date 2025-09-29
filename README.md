# 🎯 WP Business Model Canvas v2.0

> Plugin WordPress professionnel pour construire, suivre et enrichir un Business Model Canvas avec gestion centralisée par les administrateurs

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/your-repo/wp-business-model-canvas)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🚀 Nouveautés v2.0 - Architecture Centralisée

### ✨ Changements majeurs
- **Projets créés par les admins** : Les administrateurs créent et gèrent tous les projets
- **Utilisateurs avec ID personnalisé** : Chaque utilisateur a un identifiant unique assigné par l'admin
- **Association projet-utilisateur** : Un projet peut être assigné à plusieurs utilisateurs
- **Gestion centralisée** : Interface d'administration complète pour la gestion des projets et utilisateurs
- **Migration automatique** : Script de migration pour les données existantes

## 🚀 Nouveautés v2.0

### ✨ Structure complètement réorganisée
- **Architecture MVC** : Séparation claire des responsabilités
- **Autoloader intégré** : Chargement automatique des classes
- **Code modulaire** : Facile à maintenir et étendre
- **Standards WordPress** : Respect des meilleures pratiques

### 🎨 Interfaces séparées
- **Admin** : Interface d'administration complète
- **Public** : Interface utilisateur intuitive
- **Responsive** : Design adaptatif mobile/desktop

### 🔧 Fonctionnalités avancées
- **Sauvegarde automatique** : Aucune perte de données
- **Export PDF** : Génération de documents professionnels
- **Gestion des fichiers** : Upload et organisation des documents
- **Authentification sécurisée** : Système d'accès robuste

## 📁 Structure du projet

```
wp-business-model-canvas/
├── 📁 src/                           # 🎯 Code source principal
│   ├── 📁 Admin/                     # 🛡️ Interface d'administration
│   │   ├── Controllers/               # Contrôleurs admin
│   │   │   ├── admin-dashboard.php   # Dashboard principal
│   │   │   └── admin-page.php         # Page admin générale
│   │   ├── Views/                    # Vues admin (templates)
│   │   ├── Assets/                   # Ressources admin
│   │   │   ├── css/                  # Styles admin
│   │   │   └── js/                   # Scripts admin
│   │   │       ├── admin.js          # JavaScript général
│   │   │       ├── admin-dashboard.js # JavaScript dashboard
│   │   │       └── admin-users.js    # JavaScript gestion utilisateurs
│   │   └── Services/                 # Services admin spécialisés
│   ├── 📁 Public/                    # 👥 Interface utilisateur
│   │   ├── Controllers/              # Contrôleurs public
│   │   ├── Views/                    # Vues public (templates)
│   │   ├── Assets/                   # Ressources public
│   │   │   ├── css/                  # Styles public
│   │   │   │   └── users.css         # Styles gestion utilisateurs
│   │   │   │   ├── public.css        # Styles généraux
│   │   │   │   └── admin.css         # Styles admin pour public
│   │   │   └── js/                   # Scripts public
│   │   │       ├── public.js         # JavaScript général
│   │   │       ├── auth.js           # Authentification
│   │   │       └── dashboard.js      # Dashboard utilisateur
│   │   └── Services/                 # Services public spécialisés
│   ├── 📁 Core/                      # 🔧 Fonctionnalités centrales
│   │   ├── Database/                 # Gestion base de données
│   │   │   └── class-wp-bmc-database.php
│   │   ├── Auth/                     # Authentification
│   │   │   └── class-wp-bmc-auth.php
│   │   ├── Ajax/                     # Requêtes AJAX
│   │   │   └── class-wp-bmc-ajax.php
│   │   ├── Shortcodes/               # Shortcodes WordPress
│   │   │   └── class-wp-bmc-shortcodes.php
│   │   ├── class-wp-bmc-loader.php   # Chargeur principal
│   │   └── class-wp-bmc-template-loader.php # Chargeur templates
│   └── 📁 Shared/                    # 🔄 Ressources partagées
│       ├── Models/                   # Modèles de données
│       ├── Utils/                    # Utilitaires communs
│       └── Templates/                # Templates réutilisables
│           ├── admin/                # Templates admin
│           │   ├── canvas.php        # Canvas admin
│           │   ├── dashboard.php     # Dashboard admin
│           │   └── edit-section.php  # Édition section
│           └── public/               # Templates public
│               ├── canvas.php        # Canvas public
│               ├── dashboard.php     # Dashboard public
│               ├── edit-section.php  # Édition section
│               ├── login-form.php    # Formulaire connexion
│               └── register-form.php # Formulaire inscription
├── 📁 assets/                        # 📦 Assets statiques compilés
│   ├── css/                          # Styles compilés
│   ├── js/                           # Scripts compilés
│   └── images/                       # Images optimisées
├── 📁 languages/                     # 🌍 Fichiers de traduction
├── README.md                         # Documentation principale
└── wp-business-model-canvas.php      # 🚀 Point d'entrée du plugin
```

## 🛠️ Installation

### Prérequis
- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- MySQL 5.6 ou supérieur

### Installation manuelle
1. Téléchargez le plugin
2. Décompressez dans `/wp-content/plugins/`
3. Activez le plugin depuis l'admin WordPress
4. Configurez les pages nécessaires

### Installation via Composer
```bash
composer require your-vendor/wp-business-model-canvas
```

## 🎯 Utilisation v2.0

### Pour les administrateurs
1. Connectez-vous à l'admin WordPress
2. Accédez au menu "BMC v2.0" dans la barre latérale
3. **Gestion des utilisateurs** : Créez des utilisateurs avec des ID personnalisés
4. **Gestion des projets** : Créez des projets et assignez-les aux utilisateurs
5. **Migration** : Utilisez l'outil de migration pour les données existantes

### Pour les utilisateurs
1. Connectez-vous avec vos identifiants (créés par l'admin)
2. Accédez à votre tableau de bord
3. Éditez les projets qui vous ont été assignés
4. **Note** : Les utilisateurs ne peuvent plus créer de projets directement

## 🔧 Configuration

### Pages automatiques
Le plugin crée automatiquement les pages suivantes :
- `/business-model-canvas/` - Canvas principal
- `/dashboard/` - Tableau de bord utilisateur
- `/login/` - Connexion
- `/register/` - Inscription

### Shortcodes disponibles
- `[wp_bmc_canvas]` - Affiche le canvas
- `[wp_bmc_dashboard]` - Affiche le dashboard
- `[wp_bmc_login]` - Formulaire de connexion
- `[wp_bmc_register]` - Formulaire d'inscription

## 🎨 Personnalisation

### Styles CSS
Les styles sont organisés par contexte :
- `src/Public/Assets/css/admin.css` - Styles admin
- `src/Public/Assets/css/` - Styles public
- `assets/css/` - Styles compilés

### Templates
Les templates sont dans `src/Shared/Templates/` :
- `admin/` - Templates admin
- `public/` - Templates public

### Hooks et filtres
```php
// Personnaliser le canvas
add_filter('wp_bmc_canvas_sections', 'custom_canvas_sections');

// Ajouter des champs personnalisés
add_action('wp_bmc_user_profile_fields', 'add_custom_fields');
```

## 🧪 Tests

### Tests unitaires
```bash
composer test
```

### Tests d'intégration
```bash
composer test:integration
```

## 📚 Documentation

- [Guide de développement](docs/DEVELOPMENT.md)
- [Structure v2.0](docs/STRUCTURE-V2.md)
- [API Reference](docs/API.md)
- [Migration](docs/MIGRATION.md)

## 🤝 Contribution

### Développement
1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

### Standards de code
- Respecter les [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Utiliser PHP 7.4+ features
- Documenter le code avec PHPDoc
- Tests unitaires pour les nouvelles fonctionnalités

## 📋 Changelog

### v2.0.0 (2024-01-XX) - Architecture Centralisée
- 🏗️ **Architecture centralisée** : Projets créés et gérés par les administrateurs
- 👥 **Gestion des utilisateurs** : Création d'utilisateurs avec ID personnalisé
- 🔗 **Association projet-utilisateur** : Un projet peut être assigné à plusieurs utilisateurs
- 🛠️ **Interface d'administration** : Pages dédiées pour la gestion des projets et utilisateurs
- 🔄 **Migration automatique** : Script de migration pour les données existantes
- 🚫 **Création de projets désactivée** : Les utilisateurs ne peuvent plus créer de projets directement
- 🔐 **Contrôle d'accès** : Vérification des permissions pour l'accès aux projets

### v1.0.0 (2023-XX-XX)
- 🎉 Version initiale
- 📊 Business Model Canvas de base
- 👥 Système d'authentification
- 💾 Sauvegarde automatique

## 🐛 Signaler un bug

Si vous rencontrez un problème :
1. Vérifiez les [issues existantes](https://github.com/your-repo/wp-business-model-canvas/issues)
2. Créez une nouvelle issue avec :
   - Description détaillée
   - Étapes pour reproduire
   - Version WordPress/PHP
   - Logs d'erreur

## 📄 Licence

Ce projet est sous licence GPL v2 ou ultérieure. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Équipe

- **Développeur principal** : [Votre Nom](https://github.com/your-username)
- **Contributeurs** : Voir [CONTRIBUTORS.md](CONTRIBUTORS.md)

## 🙏 Remerciements

- [WordPress](https://wordpress.org/) - Plateforme CMS
- [Business Model Canvas](https://www.strategyzer.com/canvas/business-model-canvas) - Méthodologie
- [Font Awesome](https://fontawesome.com/) - Icônes
- Tous les contributeurs et testeurs

---

**Développé avec ❤️ pour la communauté WordPress**
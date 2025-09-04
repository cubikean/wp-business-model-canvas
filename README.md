# WP Business Model Canvas

Un plugin WordPress complet pour créer et gérer des Business Model Canvas avec un système d'authentification, d'édition collaborative et de notation par les administrateurs.

## 🎯 Fonctionnalités

### 👥 Système d'authentification
- Inscription et connexion utilisateurs
- Gestion des profils utilisateurs
- Système de déconnexion sécurisé

### 📊 Business Model Canvas
- **9 sections standard** du Business Model Canvas :
  - Partenaires clés
  - Activités clés
  - Ressources clés
  - Proposition de valeur
  - Relations clients
  - Canaux
  - Segments clients
  - Structure des coûts
  - Sources de revenus

### 🎨 Interface utilisateur
- **Vue synthétique** : Affichage des 3 sections principales
- **Vue globale** : Affichage complet des 9 sections
- Interface responsive et moderne
- Édition en temps réel avec sauvegarde automatique

### 🔧 Fonctionnalités avancées
- **Éditeur WYSIWYG** pour chaque section
- **Gestion des fichiers** : Upload d'images, vidéos, PDF
- **Documents de référence** : Bibliothèque de documents gérée par les admins
- **Système de notation** : Les admins peuvent noter chaque section (0-10) avec commentaires
- **Export PDF** des canvas

### 👨‍💼 Interface administrateur
- Gestion des utilisateurs
- Gestion des documents de référence
- Vue d'édition des canvas utilisateurs
- Système de notation et commentaires

## 🚀 Installation

1. Téléchargez le plugin
2. Uploadez le dossier dans `/wp-content/plugins/`
3. Activez le plugin dans l'administration WordPress
4. Les tables de base de données seront créées automatiquement

## 📋 Prérequis

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

## 🛠️ Structure du plugin

```
wp-business-model-canvas/
├── admin/                 # Interface d'administration
├── includes/              # Classes PHP principales
├── public/               # Assets publics (CSS, JS)
├── templates/            # Templates d'affichage
│   ├── admin/           # Templates admin
│   └── public/          # Templates utilisateur
└── wp-business-model-canvas.php  # Fichier principal
```

## 🗄️ Base de données

Le plugin crée automatiquement les tables suivantes :
- `bmc_users` : Utilisateurs du plugin
- `bmc_projects` : Projets canvas
- `bmc_canvas_data` : Données des sections canvas
- `bmc_files` : Fichiers attachés aux sections
- `bmc_documents` : Documents de référence
- `bmc_ratings` : Notes et commentaires des admins

## 🎨 Shortcodes disponibles

- `[wp_bmc_login]` : Formulaire de connexion
- `[wp_bmc_register]` : Formulaire d'inscription
- `[wp_bmc_dashboard]` : Tableau de bord utilisateur
- `[wp_bmc_canvas]` : Affichage du canvas

## 🔧 Configuration

### Pages requises
Créez les pages suivantes dans WordPress :
- `/login/` : Page de connexion
- `/register/` : Page d'inscription
- `/dashboard/` : Tableau de bord utilisateur
- `/business-model-canvas/` : Page d'affichage canvas

### Permissions
- Les utilisateurs normaux peuvent créer et éditer leur propre canvas
- Les administrateurs peuvent voir et éditer tous les canvas
- Seuls les administrateurs peuvent ajouter des documents de référence

## 🎯 Utilisation

### Pour les utilisateurs
1. Créez un compte via la page d'inscription
2. Accédez à votre tableau de bord
3. Créez votre premier Business Model Canvas
4. Éditez chaque section avec l'éditeur WYSIWYG
5. Ajoutez des fichiers et consultez les documents de référence
6. Consultez les notes et commentaires de votre administrateur

### Pour les administrateurs
1. Accédez à l'interface d'administration du plugin
2. Gérez les documents de référence
3. Consultez les canvas des utilisateurs
4. Notez et commentez chaque section
5. Suivez l'évolution des projets

## 🔒 Sécurité

- Authentification sécurisée avec nonces WordPress
- Validation et échappement des données
- Protection CSRF
- Gestion des permissions utilisateur
- Upload de fichiers sécurisé

## 🎨 Personnalisation

Le plugin utilise des variables CSS pour la personnalisation :
```css
:root {
    --primary-color: #0073aa;
    --secondary-color: #005177;
    --success-color: #28a745;
    --error-color: #dc3545;
    --warning-color: #ffc107;
}
```

## 📝 Changelog

### Version 1.0.0
- Système d'authentification complet
- Business Model Canvas avec 9 sections
- Éditeur WYSIWYG intégré
- Gestion des fichiers et documents
- Système de notation administrateur
- Interface responsive
- Export PDF

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer des améliorations
- Soumettre des pull requests

## 📄 Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## 👨‍💻 Auteur

Développé avec ❤️ pour la communauté WordPress.

---

**Note** : Ce plugin est conçu pour les consultants, coachs et formateurs qui souhaitent offrir un outil de Business Model Canvas à leurs clients ou étudiants.

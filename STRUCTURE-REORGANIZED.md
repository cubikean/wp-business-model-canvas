# Structure réorganisée - WP Business Model Canvas

## Vue d'ensemble

Le plugin a été réorganisé pour bien séparer les fonctionnalités administrateur et utilisateur, avec une arborescence claire et logique.

## 📁 Nouvelle structure des dossiers

```
wp-business-model-canvas/
├── admin/                          # 🛡️ Interface d'administration WordPress
│   ├── admin-dashboard.php         # Dashboard admin principal
│   ├── admin-page.php              # Fonctions utilitaires admin
│   ├── css/
│   │   ├── admin.css               # Styles généraux admin
│   │   ├── admin-dashboard.css     # Styles du dashboard admin
│   │   └── admin-users.css         # Styles de gestion des utilisateurs
│   └── js/
│       ├── admin.js                # JavaScript général admin
│       ├── admin-dashboard.js       # JavaScript du dashboard admin
│       └── admin-users.js           # JavaScript de gestion des utilisateurs
├── public/                         # 👥 Interface utilisateur (front-end)
│   ├── css/
│   │   └── public.css              # Styles pour les utilisateurs
│   └── js/
│       ├── auth.js                  # Authentification utilisateur
│       ├── dashboard.js             # Dashboard utilisateur
│       ├── public.js                # JavaScript général public
│       └── canvas-admin.js          # Canvas avec fonctionnalités admin
├── templates/                      # 📄 Templates PHP
│   ├── admin/                      # Templates pour l'admin
│   │   ├── dashboard.php            # Template dashboard admin (legacy)
│   │   └── edit-section.php         # Template d'édition réutilisable
│   └── public/                     # Templates pour les utilisateurs
│       ├── canvas.php               # Template du canvas
│       ├── dashboard.php            # Template dashboard utilisateur
│       ├── edit-section.php         # Template d'édition réutilisable
│       ├── login-form.php           # Formulaire de connexion
│       └── register-form.php        # Formulaire d'inscription
├── includes/                       # 🔧 Classes PHP principales
│   ├── class-wp-bmc-auth.php       # Authentification
│   ├── class-wp-bmc-database.php   # Base de données
│   ├── class-wp-bmc-loader.php      # Chargeur principal
│   └── ...
└── wp-business-model-canvas.php    # Fichier principal du plugin
```

## 🎯 Séparation des responsabilités

### **Interface Admin (`/admin/`)**
- **Accès** : Administrateurs WordPress uniquement
- **URL** : `/wp-admin/admin.php?page=wp-business-model-canvas`
- **Fonctionnalités** :
  - Gestion complète des utilisateurs
  - Statistiques du système
  - Export des données
  - Configuration du plugin
  - Vue des projets utilisateurs

### **Interface Utilisateur (`/public/`)**
- **Accès** : Utilisateurs BMC inscrits
- **URL** : `/dashboard/`
- **Fonctionnalités** :
  - Création et édition du Business Model Canvas
  - Gestion des fichiers attachés
  - Sauvegarde automatique
  - Export PDF du canvas

## 🔄 Flux d'authentification

### **Pour les administrateurs WordPress**
1. Connexion avec identifiants WordPress
2. Redirection automatique vers `/wp-admin/admin.php?page=wp-business-model-canvas`
3. Accès au dashboard admin complet

### **Pour les utilisateurs BMC**
1. Inscription/connexion via le système BMC
2. Redirection vers `/dashboard/`
3. Accès au canvas personnel

## 📋 Fonctionnalités par interface

### **Dashboard Admin**
- ✅ **Statistiques** : Utilisateurs, projets, données
- ✅ **Gestion utilisateurs** : Liste complète avec recherche, tri, filtrage
- ✅ **Actions** : Voir canvas, éditer, supprimer utilisateurs
- ✅ **Export** : CSV utilisateurs, JSON données complètes
- ✅ **Projets récents** : Liste des derniers projets créés
- ✅ **Informations système** : Versions, configuration

### **Dashboard Utilisateur**
- ✅ **Canvas personnel** : Création et édition du BMC
- ✅ **Vues** : Synthétique (3 sections) ou globale (9 sections)
- ✅ **Édition** : Interface d'édition avec WYSIWYG
- ✅ **Fichiers** : Upload et gestion des fichiers attachés
- ✅ **Documents** : Consultation des documents de référence
- ✅ **Sauvegarde** : Automatique et manuelle
- ✅ **Export** : PDF du canvas

## 🔧 Modifications techniques

### **Authentification**
- Détection automatique des administrateurs WordPress
- Redirection appropriée selon le type d'utilisateur
- Utilisateur virtuel pour les admins WordPress

### **Templates**
- Séparation claire admin/user
- Templates réutilisables (`edit-section.php`)
- Fonction utilitaire `wp_bmc_include_edit_section()`

### **Assets**
- CSS et JS séparés par contexte
- Chargement conditionnel des scripts
- Localisation AJAX appropriée

## 🚀 Avantages de cette structure

1. **Séparation claire** : Admin et user complètement séparés
2. **Maintenabilité** : Code organisé et modulaire
3. **Sécurité** : Permissions appropriées par interface
4. **Performance** : Chargement optimisé des assets
5. **Évolutivité** : Facile d'ajouter de nouvelles fonctionnalités
6. **UX** : Interface adaptée au type d'utilisateur

## 📝 Notes importantes

- Les administrateurs WordPress ne peuvent plus accéder au dashboard public `/dashboard/`
- Ils sont automatiquement redirigés vers l'interface admin
- Les utilisateurs BMC normaux n'ont pas accès à l'interface admin
- La gestion des utilisateurs se fait uniquement depuis l'admin WordPress
- Les templates sont réutilisables entre admin et public quand approprié

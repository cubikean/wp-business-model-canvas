# 🎯 WP Business Model Canvas v2.0

> Plugin WordPress professionnel pour construire, suivre et enrichir un Business Model Canvas avec gestion centralisée par les administrateurs

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/your-repo/wp-business-model-canvas)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 📋 Table des matières

- [Vue d'ensemble](#-vue-densemble)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Structure du projet](#-structure-du-projet)
- [Systèmes principaux](#-systèmes-principaux)
- [Conventions de code](#-conventions-de-code)
- [Guide de développement](#-guide-de-développement)
- [Fonctionnalités](#-fonctionnalités)
- [Base de données](#-base-de-données)
- [Débogage](#-débogage)
- [Points d'attention](#-points-dattention-pour-les-futurs-développeurs)

---

## 🎯 Vue d'ensemble

**WP Business Model Canvas** est un plugin WordPress complet permettant aux utilisateurs de créer, éditer et suivre leur Business Model Canvas directement depuis le front-end. Le plugin offre une interface intuitive pour les utilisateurs et une interface d'administration complète pour la gestion centralisée des projets et utilisateurs.

### Caractéristiques principales

- ✅ **Architecture MVC** : Code organisé et maintenable
- ✅ **Gestion centralisée** : Les administrateurs créent et gèrent tous les projets
- ✅ **Système de versions** : Optimistic locking pour éviter les conflits
- ✅ **Sauvegarde automatique** : Aucune perte de données
- ✅ **Export PDF** : Génération de documents professionnels
- ✅ **Gestion des fichiers** : Upload et organisation des documents
- ✅ **Système de todos** : Plan d'action intégré
- ✅ **Notation et révisions** : Suivi de l'évolution par les admins
- ✅ **Authentification sécurisée** : Système d'accès robuste

---

## 🏗️ Architecture

### Architecture MVC

Le plugin suit une architecture MVC stricte avec séparation claire des responsabilités :

```
src/
├── Core/          # Classes centrales (Database, Auth, Ajax, Shortcodes)
├── Admin/         # Interface d'administration (Controllers, Assets)
├── Public/        # Interface utilisateur (Controllers, Assets)
└── Shared/        # Ressources partagées (Templates, Functions, Utils)
```

### Points d'entrée principaux

1. **Plugin principal** : `wp-business-model-canvas.php`
   - Définit les constantes
   - Charge l'autoloader
   - Initialise le plugin

2. **Autoloader** : `src/Core/autoloader.php`
   - Charge automatiquement les classes selon les conventions

3. **Chargeur principal** : `src/Core/class-wp-bmc-loader.php`
   - Enregistre les hooks WordPress
   - Charge les scripts et styles
   - Initialise les shortcodes

4. **Configuration** : `src/Core/class-wp-bmc-config.php`
   - Centralise toutes les configurations

---

## 🛠️ Installation

### Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- MySQL 5.6 ou supérieur
- Extensions PHP : `mysqli`, `json`, `mbstring`

### Installation manuelle

1. Téléchargez ou clonez le plugin
2. Décompressez dans `/wp-content/plugins/wp-business-model-canvas/`
3. Activez le plugin depuis l'admin WordPress (`Plugins > Plugins installés`)
4. Le plugin créera automatiquement les tables de base de données
5. Les pages nécessaires seront créées automatiquement

### Pages créées automatiquement

- `/business-model-canvas/` - Canvas principal
- `/dashboard/` - Tableau de bord utilisateur
- `/login/` - Connexion
- `/register/` - Inscription

### Configuration initiale

1. Accédez au menu **"BMC"** dans la barre latérale WordPress
2. Créez vos premiers utilisateurs avec des ID personnalisés
3. Créez des projets et assignez-les aux utilisateurs
4. Les utilisateurs peuvent ensuite se connecter et éditer leurs projets

---

## 📁 Structure du projet

```
wp-business-model-canvas/
├── 📁 src/                           # Code source principal
│   ├── 📁 Admin/                     # Interface d'administration
│   │   ├── Controllers/              # Contrôleurs admin
│   │   │   ├── admin-projects.php    # Gestion des projets
│   │   │   ├── admin-users.php       # Gestion des utilisateurs
│   │   │   └── admin-canvas-config.php # Configuration du canvas
│   │   └── Assets/                  # Ressources admin
│   │       ├── css/                  # Styles admin
│   │       └── js/                   # Scripts admin
│   │           ├── admin-projects.js # JavaScript projets
│   │           ├── admin-users.js    # JavaScript utilisateurs
│   │           ├── admin-dashboard.js # JavaScript dashboard admin
│   │           └── admin-canvas-config.js # JavaScript config
│   │
│   ├── 📁 Public/                    # Interface utilisateur
│   │   └── Assets/                   # Ressources public
│   │       ├── css/                  # Styles public
│   │       │   ├── public.css        # Styles généraux
│   │       │   ├── admin.css         # Styles admin pour public
│   │       │   └── users.css         # Styles gestion utilisateurs
│   │       └── js/                   # Scripts public
│   │           ├── core/             # Modules JavaScript core
│   │           │   ├── canvas-utils.js      # Utilitaires
│   │           │   ├── canvas-versions.js   # Gestion des versions
│   │           │   ├── canvas-draft.js      # Gestion des brouillons
│   │           │   ├── canvas-core.js       # Fonctions core
│   │           │   └── canvas-conflicts.js  # Résolution de conflits
│   │           ├── public.js         # JavaScript général
│   │           ├── dashboard.js      # Dashboard utilisateur
│   │           └── auth.js           # Authentification
│   │
│   ├── 📁 Core/                      # Fonctionnalités centrales
│   │   ├── Database/                 # Gestion base de données
│   │   │   └── class-wp-bmc-database.php
│   │   ├── Auth/                     # Authentification
│   │   │   └── class-wp-bmc-auth.php
│   │   ├── Ajax/                     # Requêtes AJAX
│   │   │   └── class-wp-bmc-ajax.php
│   │   ├── Shortcodes/               # Shortcodes WordPress
│   │   │   └── class-wp-bmc-shortcodes.php
│   │   ├── autoloader.php            # Autoloader personnalisé
│   │   ├── class-wp-bmc-loader.php    # Chargeur principal
│   │   ├── class-wp-bmc-config.php   # Configuration
│   │   └── class-wp-bmc-template-loader.php # Chargeur templates
│   │
│   └── 📁 Shared/                     # Ressources partagées
│       ├── Assets/                   # Assets partagés
│       │   ├── css/                  # Styles partagés
│       │   │   ├── wp-bmc-toast.css  # Système de notifications
│       │   │   └── wp-bmc-tippy.css  # Tooltips
│       │   └── js/                   # Scripts partagés
│       │       ├── wp-bmc-toast.js   # Système de notifications
│       │       └── wp-bmc-tippy.js    # Tooltips
│       ├── Config/                   # Configurations
│       │   ├── canvas-sections.php   # Configuration des sections
│       │   └── class-wp-bmc-canvas-config.php
│       ├── Functions/                # Fonctions utilitaires
│       │   └── canvas-functions.php
│       ├── Templates/                # Templates réutilisables
│       │   ├── admin/                # Templates admin
│       │   ├── public/               # Templates public
│       │   └── emails/               # Templates emails
│       └── Utils/                    # Utilitaires
│           └── email-templates.php
│
├── README.md                         # Documentation principale
└── wp-business-model-canvas.php      # Point d'entrée du plugin
```

---

## 🔧 Systèmes principaux

### 1. Base de données (`WP_BMC_Database`)

**Fichier** : `src/Core/Database/class-wp-bmc-database.php`

**Tables principales** :
- `bmc_users` - Utilisateurs BMC
- `bmc_projects` - Projets
- `bmc_canvas_data` - Données du canvas
- `bmc_todos` - Todos/Plan d'action
- `bmc_ratings` - Notations par section
- `bmc_section_revisions` - Révisions des sections
- `bmc_project_users` - Association projet-utilisateur
- `bmc_project_admins` - Association projet-superviseur
- `bmc_files` - Fichiers uploadés
- `bmc_documents` - Documents de référence

**Conventions importantes** :
- ✅ Toujours utiliser `$wpdb->prepare()` pour les requêtes
- ✅ Utiliser `format_date_for_display()` pour toutes les dates affichées
- ✅ Toutes les fonctions sont statiques
- ✅ Retourner `false` en cas d'erreur, les données en cas de succès

**Exemple** :
```php
$project = WP_BMC_Database::get_project($project_id);
$formatted_date = WP_BMC_Database::format_date_for_display($project->created_at);
```

### 2. Authentification (`WP_BMC_Auth`)

**Fichier** : `src/Core/Auth/class-wp-bmc-auth.php`

**Fonctionnalités** :
- Inscription/connexion custom
- Intégration WordPress (`wp_set_auth_cookie`)
- Support admin WordPress
- Gestion des sessions

**Utilisation** :
```php
$user = WP_BMC_Auth::get_current_user();
if (WP_BMC_Auth::is_logged_in()) {
    // Utilisateur connecté
}
```

### 3. AJAX (`class-wp-bmc-ajax.php`)

**Fichier** : `src/Core/Ajax/class-wp-bmc-ajax.php`

**Conventions** :
- ✅ Toujours vérifier le nonce : `check_ajax_referer('wp_bmc_nonce', 'nonce')`
- ✅ Vérifier les permissions : `WP_BMC_Auth::is_logged_in()` ou `current_user_can()`
- ✅ Retours standardisés : `wp_send_json_success()` / `wp_send_json_error()`
- ✅ Sanitization obligatoire : `sanitize_text_field()`, `sanitize_textarea_field()`

**Exemple** :
```php
function wp_bmc_action_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Connexion requise');
    }
    
    $data = sanitize_text_field($_POST['data']);
    $result = WP_BMC_Database::method($data);
    
    if ($result) {
        wp_send_json_success(['message' => 'Succès !']);
    } else {
        wp_send_json_error('Erreur lors de l\'opération');
    }
}
```

### 4. Système de notifications (`WP_BMC_Toast`)

**Fichier** : `src/Shared/Assets/js/wp-bmc-toast.js`

**Utilisation** :
```javascript
WP_BMC_Toast.success('Message de succès');
WP_BMC_Toast.error('Message d\'erreur');
WP_BMC_Toast.warning('Avertissement');
WP_BMC_Toast.info('Information');
```

**⚠️ Important** : Ne jamais utiliser `alert()` ou des messages inline. Toujours utiliser le système de toast.

### 5. Gestion des versions (Optimistic Locking)

**Fichier** : `src/Public/Assets/js/core/canvas-versions.js`

**Fonctionnement** :
- Chaque section du canvas a une version (timestamp `updated_at`)
- Lors de la sauvegarde, on envoie les versions attendues
- Le serveur vérifie si les versions correspondent
- Si conflit détecté, retourne les conflits avec popups individuelles

**Résolution de conflits** :
- Popup par section en conflit
- Choix entre version serveur ou version locale
- Réessai automatique après résolution

### 6. Système de brouillons locaux

**Fichier** : `src/Public/Assets/js/core/canvas-draft.js`

**Fonctionnalités** :
- Sauvegarde automatique dans `localStorage`
- Détection au chargement de la page
- Comparaison avec version serveur
- Application sélective

---

## 📝 Conventions de code

### PHP

#### Nomenclature

**Classes** :
- Préfixe : `WP_BMC_`
- Format : `class WP_BMC_Database`
- Fichiers : `class-wp-bmc-[nom].php`

**Fonctions** :
- Préfixe : `wp_bmc_`
- Format : `wp_bmc_function_name()`
- Hooks : `wp_bmc_action_handler()`

**Exemple** :
```php
class WP_BMC_Database {
    public static function get_project($project_id) {
        // ...
    }
}

function wp_bmc_save_canvas() {
    // ...
}
```

#### Gestion des erreurs

```php
// ✅ Retours AJAX standardisés
wp_send_json_error('Message d\'erreur');
wp_send_json_success(['message' => 'Succès', 'data' => $data]);

// ✅ Logs pour débogage
error_log('wp_bmc_function - info: ' . $info);
```

#### Sécurité

```php
// ✅ Toujours vérifier le nonce
check_ajax_referer('wp_bmc_nonce', 'nonce');

// ✅ Vérifier les permissions
if (!WP_BMC_Auth::is_logged_in()) {
    wp_send_json_error('Connexion requise');
}

// ✅ Sanitisation obligatoire
$section = sanitize_text_field($_POST['section']);
$content = sanitize_textarea_field($_POST['content']);

// ✅ Validation
if (empty($section) || !$project_id) {
    wp_send_json_error('Paramètres invalides');
}
```

### JavaScript

#### Nomenclature

**Variables** :
- camelCase : `currentSectionTodos`, `pendingOperations`
- Constantes : `wp_bmc_ajax`, `wp_bmc_admin_ajax`

**Modules globaux** :
- `window.WP_BMC_Utils` - Utilitaires
- `window.WP_BMC_CanvasVersions` - Gestion des versions
- `window.WP_BMC_CanvasDraft` - Gestion des brouillons
- `window.WP_BMC_CanvasCore` - Fonctions core
- `window.WP_BMC_CanvasConflicts` - Résolution de conflits
- `window.WP_BMC_Toast` - Notifications

#### Gestion des erreurs

```javascript
// ✅ Toujours utiliser les toasts
WP_BMC_Toast.error('Message d\'erreur');

// ❌ Jamais d'alert ou de messages inline
alert('erreur'); // INTERDIT
$('#message').html('erreur'); // INTERDIT
```

#### Performance

```javascript
// ✅ Cache pour éviter les recharges
var todoCache = {};

// ✅ Batch operations
var pendingOperations = [];

// ✅ Sauvegarde différée avec triggers multiples
window.addEventListener('beforeunload', savePendingOperations);
document.addEventListener('visibilitychange', savePendingOperations);
```

---

## 🚀 Guide de développement

### Ajouter une nouvelle fonctionnalité

1. **Définir la structure** :
   - Si admin : `src/Admin/Controllers/` + `src/Admin/Assets/js/`
   - Si public : `src/Public/Assets/js/`
   - Si partagé : `src/Shared/`

2. **Créer le handler AJAX** :
   - Ajouter dans `src/Core/Ajax/class-wp-bmc-ajax.php`
   - Vérifier nonce et permissions
   - Utiliser `WP_BMC_Database` pour les opérations

3. **Créer l'interface JavaScript** :
   - Utiliser les modules core existants
   - Utiliser `WP_BMC_Toast` pour les notifications
   - Gérer les erreurs proprement

4. **Tester** :
   - Vérifier les permissions
   - Tester les cas d'erreur
   - Vérifier la sécurité (nonce, sanitization)

### Ajouter une nouvelle table

1. **Modifier `create_tables()`** dans `WP_BMC_Database`
2. **Ajouter les fonctions CRUD** :
   - `get_[table]()`
   - `create_[table]()`
   - `update_[table]()`
   - `delete_[table]()`
3. **Ajouter les index nécessaires**
4. **Tester la migration**

### Modifier le canvas

Les sections du canvas sont définies dans :
- `src/Shared/Config/canvas-sections.php`
- `src/Shared/Config/class-wp-bmc-canvas-config.php`

Pour ajouter/modifier une section :
1. Modifier la configuration
2. Mettre à jour les templates si nécessaire
3. Vérifier les styles CSS

---

## ✨ Fonctionnalités

### Canvas Business Model

**Vues disponibles** :
- **Global** : Grille complète 3x3 (vue d'ensemble)
- **Synthetic** : Vue résumée avec plan d'action
- **Edit** : Vue d'édition individuelle avec WYSIWYG

**Sections** (9 sections standard) :
1. Partenaires clés
2. Activités clés
3. Ressources clés
4. Proposition de valeur
5. Relations clients
6. Canaux
7. Segments clients
8. Structure des coûts
9. Flux de revenus

**Couleurs par groupe** :
- Rouge : Partenaires, Activités, Ressources
- Vert : Proposition de valeur
- Orange : Relations, Canaux, Segments
- Bleu : Coûts, Revenus

### Système de todos

**Fichiers impliqués** :
- Base de données : `WP_BMC_Database::*_todo()`
- Interface : `src/Public/Assets/js/dashboard.js`
- Templates : `src/Shared/Templates/public/edit-section.php`

**Fonctionnalités** :
- Cache client avec `todoCache`
- Opérations batch avec `pendingOperations`
- UI immédiate, synchronisation différée
- Sauvegarde automatique (beforeunload, visibilitychange, interval)

**⚠️ Important** : Utiliser `parseInt(todo.is_completed) === 1` pour vérifier si un todo est terminé (éviter la conversion JavaScript `"0"` → `true`).

### Système de notation admin

**Auto-ouverture popup** :
- URL : `?auto_grade_section=section_name`
- Détection : `checkAutoGradeSection()` dans `admin-dashboard.js`
- Nettoyage URL automatique après ouverture

**Révisions** :
- Créées uniquement lors de notation admin
- Incluent rating, comment, admin_id
- Affichage avec dates formatées WordPress

### Gestion des fichiers

**Upload** :
- Dossier : `/wp-content/uploads/wp-bmc-files/`
- Types autorisés : Images, PDF, DOC, XLS
- Taille max : 10MB
- Organisation par projet/section

### Export PDF

**Fonctionnalité** :
- Génération de PDF professionnel
- Inclut toutes les sections
- Mise en page optimisée

---

## 🗄️ Base de données

### Tables principales

#### `bmc_users`
Utilisateurs BMC avec intégration WordPress.

**Champs clés** :
- `id` - ID BMC
- `user_id` - ID WordPress (peut être NULL)
- `custom_id` - ID personnalisé (unique)
- `email` - Email (unique)
- `first_name`, `last_name` - Nom complet
- `status` - Statut (pending, active, inactive)
- `is_active` - Actif/inactif
- `created_by_admin` - Admin créateur

#### `bmc_projects`
Projets créés par les administrateurs.

**Champs clés** :
- `id` - ID projet
- `title` - Titre
- `description` - Description
- `status` - Statut (draft, active, archived)
- `pepitizy_id` - ID Pepitizy (optionnel)
- `created_by_admin` - Admin créateur

#### `bmc_canvas_data`
Données du canvas par section.

**Champs clés** :
- `project_id` - ID projet
- `section` - Nom de la section
- `content` - Contenu HTML
- `updated_at` - Timestamp (utilisé pour optimistic locking)

#### `bmc_todos`
Todos/Plan d'action par section.

**Champs clés** :
- `project_id` - ID projet
- `section` - Section concernée
- `content` - Contenu du todo
- `is_completed` - Terminé (0/1)
- `user_id` - Utilisateur créateur

#### `bmc_ratings`
Notations par section (admin uniquement).

**Champs clés** :
- `project_id` - ID projet
- `section` - Section notée
- `rating` - Note (1-4)
- `comment` - Commentaire
- `admin_id` - Admin notateur

#### `bmc_section_revisions`
Révisions des sections (créées lors de notation).

**Champs clés** :
- `project_id` - ID projet
- `section` - Section
- `content` - Contenu au moment de la notation
- `rating` - Note associée
- `comment` - Commentaire
- `admin_id` - Admin créateur

### Relations

- `bmc_project_users` : Association projet ↔ utilisateur (many-to-many)
- `bmc_project_admins` : Association projet ↔ superviseur (many-to-many)

### Index

Toutes les tables ont des index sur :
- `project_id`
- `section`
- `user_id` (si applicable)
- Clés uniques sur les champs appropriés

---

## 🐛 Débogage

### Problèmes fréquents

#### Todos marqués comme "finished" immédiatement

**Cause** : Conversion JavaScript `"0"` → `true`

**Solution** :
```javascript
// ❌ Incorrect
if (todo.is_completed) { ... }

// ✅ Correct
if (parseInt(todo.is_completed) === 1) { ... }
```

#### Erreur "Aucun projet trouvé" pour admin

**Cause** : `wp_bmc_get_current_project_id()` non fiable pour admins

**Solution** : Récupérer `project_id` depuis la todo elle-même en BDD

#### Dates mal formatées

**Cause** : Formatage côté client sans fuseau horaire

**Solution** : Utiliser `WP_BMC_Database::format_date_for_display()` côté serveur

#### "saveBrickContent callback - saveSuccess: false"

**Cause** : `closeEditView()` reset `currentEditingSection`

**Solution** : Éviter `closeEditView()` automatique, utiliser callback

### Outils de débogage

#### Console logs JavaScript

```javascript
// Dashboard
console.log('loadSectionTodos - project_id:', project_id);
console.log('saveBrickContent - response:', response);

// Admin
console.log('openGradingModal - projectId:', projectId);
```

#### PHP error_log

```php
error_log('wp_bmc_toggle_todo_handler - todo found: ' . ($todo ? 'yes' : 'no'));
error_log('save_canvas_data - result: ' . ($result ? 'success' : 'failed'));
```

#### Base de données

```sql
-- Vérifier structure todos
DESCRIBE wp_bmc_todos;

-- Vérifier données
SELECT * FROM wp_bmc_todos WHERE project_id = X;

-- Vérifier versions canvas
SELECT section, updated_at FROM wp_bmc_canvas_data WHERE project_id = X;
```

### Optimisations performances

#### Todos
- Cache client : `todoCache[section]`
- Batch operations : `pendingOperations`
- Triggers de sauvegarde : beforeunload, visibilitychange, interval

#### AJAX
- Éviter les appels multiples avec cache
- Utiliser batch handlers quand possible
- Nonces appropriés (`wp_bmc_nonce` vs `wp_bmc_admin_nonce`)

---

## ⚠️ Points d'attention pour les futurs développeurs

### 1. Architecture modulaire JavaScript

Le code JavaScript a été refactorisé en modules core réutilisables :

- **`canvas-utils.js`** : Utilitaires (escapeHtml, extractErrorMessage, etc.)
- **`canvas-versions.js`** : Gestion des versions (optimistic locking)
- **`canvas-draft.js`** : Gestion des brouillons locaux
- **`canvas-core.js`** : Fonctions core (auto-resize, auto-save, etc.)
- **`canvas-conflicts.js`** : Résolution de conflits avec popups individuelles

**⚠️ Important** : Ne pas dupliquer ces fonctions. Toujours utiliser les modules core.

### 2. Système de versions (Optimistic Locking)

Le système de versions est crucial pour éviter les conflits :

- Chaque section a une version (`updated_at`)
- Les versions sont envoyées avec chaque sauvegarde
- En cas de conflit, des popups individuelles permettent de choisir
- Ne jamais contourner ce système

### 3. Gestion des dates

**Toujours utiliser** :
```php
WP_BMC_Database::format_date_for_display($date_string);
```

Cette fonction respecte :
- Le fuseau horaire WordPress
- Le format de date WordPress
- Les paramètres utilisateur

### 4. Nonces et sécurité

**Deux types de nonces** :
- `wp_bmc_nonce` : Pour les utilisateurs
- `wp_bmc_admin_nonce` : Pour les administrateurs

**Toujours vérifier** :
```php
check_ajax_referer('wp_bmc_nonce', 'nonce'); // ou 'wp_bmc_admin_nonce'
```

### 5. Permissions

**Vérifier les permissions** :
- Utilisateurs : `WP_BMC_Auth::is_logged_in()`
- Admins : `current_user_can('manage_options')`
- Accès projet : `WP_BMC_Database::user_has_project_access()`

### 6. Notifications

**Toujours utiliser** `WP_BMC_Toast` :
```javascript
WP_BMC_Toast.success('Succès');
WP_BMC_Toast.error('Erreur');
```

**Jamais** :
- `alert()`
- Messages inline dans le DOM
- `console.log()` pour l'utilisateur

### 7. Sauvegarde automatique

Le système de sauvegarde automatique utilise plusieurs triggers :
- `beforeunload` : Avant fermeture
- `visibilitychange` : Changement d'onglet
- Interval : Toutes les 30 secondes
- Input events : Après 2 secondes d'inactivité

Ne pas désactiver ces mécanismes sans raison valable.

### 8. Structure des fichiers

**Respecter la structure MVC** :
- Admin → `src/Admin/`
- Public → `src/Public/`
- Core → `src/Core/`
- Shared → `src/Shared/`

Ne pas mélanger les responsabilités.

### 9. Base de données

**Toujours** :
- Utiliser `$wpdb->prepare()` pour les requêtes
- Utiliser les fonctions statiques de `WP_BMC_Database`
- Vérifier les retours (`false` = erreur)

**Jamais** :
- Requêtes SQL directes sans `prepare()`
- Modifications directes des tables
- Bypass des fonctions de la classe Database

### 10. Tests avant déploiement

**Vérifier** :
- ✅ Permissions (utilisateur, admin, projet)
- ✅ Nonces (tous les handlers AJAX)
- ✅ Sanitization (toutes les entrées)
- ✅ Gestion d'erreurs (tous les cas)
- ✅ Conflits de version (test avec 2 utilisateurs)
- ✅ Sauvegarde automatique (fermeture, changement d'onglet)
- ✅ Responsive (mobile, tablette, desktop)

---

## 📚 Ressources supplémentaires

### Fichiers de documentation

- `.cursor/rules/wp-bmc-structure.mdc` - Structure du plugin
- `.cursor/rules/wp-bmc-features.mdc` - Fonctionnalités
- `.cursor/rules/wp-bmc-debugging.mdc` - Guide de débogage
- `.cursor/rules/wp-bmc-php.mdc` - Conventions PHP

### Shortcodes disponibles

- `[wp_bmc_canvas]` - Affiche le canvas
- `[wp_bmc_dashboard]` - Affiche le dashboard
- `[wp_bmc_login]` - Formulaire de connexion
- `[wp_bmc_register]` - Formulaire d'inscription

### Hooks et filtres

```php
// Personnaliser le canvas
add_filter('wp_bmc_canvas_sections', 'custom_canvas_sections');

// Ajouter des champs personnalisés
add_action('wp_bmc_user_profile_fields', 'add_custom_fields');
```

---

## 📋 Changelog

### v2.0.0 (2024) - Architecture Centralisée

- 🏗️ **Architecture centralisée** : Projets créés et gérés par les administrateurs
- 👥 **Gestion des utilisateurs** : Création d'utilisateurs avec ID personnalisé
- 🔗 **Association projet-utilisateur** : Un projet peut être assigné à plusieurs utilisateurs
- 🛠️ **Interface d'administration** : Pages dédiées pour la gestion des projets et utilisateurs
- 🔄 **Migration automatique** : Script de migration pour les données existantes
- 🚫 **Création de projets désactivée** : Les utilisateurs ne peuvent plus créer de projets directement
- 🔐 **Contrôle d'accès** : Vérification des permissions pour l'accès aux projets
- 🎨 **Refactoring JavaScript** : Modules core réutilisables
- 🔄 **Système de conflits** : Popups individuelles pour résolution de conflits
- 📝 **ID Pepitizy** : Support pour l'intégration avec Pepitizy

---

## 📄 Licence

Ce projet est sous licence GPL v2 ou ultérieure. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👥 Équipe

- **Développeur principal** : [Votre Nom](https://github.com/your-username)
- **Contributeurs** : Voir [CONTRIBUTORS.md](CONTRIBUTORS.md)

---

## 🙏 Remerciements

- [WordPress](https://wordpress.org/) - Plateforme CMS
- [Business Model Canvas](https://www.strategyzer.com/canvas/business-model-canvas) - Méthodologie
- [Font Awesome](https://fontawesome.com/) - Icônes
- Tous les contributeurs et testeurs

---

**Développé avec ❤️ pour la communauté WordPress**

---

## 📞 Support

Pour toute question ou problème :
1. Vérifiez cette documentation
2. Consultez les [issues existantes](https://github.com/your-repo/wp-business-model-canvas/issues)
3. Créez une nouvelle issue avec :
   - Description détaillée
   - Étapes pour reproduire
   - Version WordPress/PHP
   - Logs d'erreur

---

*Dernière mise à jour : 2024*

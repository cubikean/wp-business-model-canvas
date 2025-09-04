# Structure des Templates - WP Business Model Canvas

## Vue d'ensemble

Le plugin utilise maintenant un système de templates modulaire pour organiser les vues de manière claire et maintenable.

## Structure des dossiers

```
templates/
├── admin/
│   └── dashboard.php          # Page d'administration principale
└── public/
    ├── login-form.php         # Formulaire de connexion
    ├── register-form.php      # Formulaire d'inscription
    ├── dashboard.php          # Tableau de bord utilisateur
    └── canvas.php             # Interface Business Model Canvas
```

## Classe Template Loader

La classe `WP_BMC_Template_Loader` fournit deux méthodes principales :

### `load_template($template_name, $args = array())`
Charge et affiche un template avec des variables passées en paramètre.

**Exemple :**
```php
WP_BMC_Template_Loader::load_template('admin/dashboard', array(
    'message' => 'Opération réussie'
));
```

### `get_template_content($template_name, $args = array())`
Retourne le contenu d'un template sous forme de chaîne de caractères.

**Exemple :**
```php
$content = WP_BMC_Template_Loader::get_template_content('public/login-form');
```

## Templates disponibles

### Admin Templates

#### `admin/dashboard.php`
- **Description :** Page d'administration principale
- **Variables disponibles :**
  - `$message` : Message de succès/erreur
  - `$users_count` : Nombre d'utilisateurs
  - `$projects_count` : Nombre de projets
  - `$canvas_data_count` : Nombre de sections de données
  - `$recent_users` : Liste des derniers utilisateurs
  - `$recent_projects` : Liste des derniers projets

### Public Templates

#### `public/login-form.php`
- **Description :** Formulaire de connexion
- **Fonctionnalités :**
  - Validation côté client
  - Messages d'erreur/succès
  - Lien vers l'inscription

#### `public/register-form.php`
- **Description :** Formulaire d'inscription
- **Fonctionnalités :**
  - Validation des champs requis
  - Confirmation de mot de passe
  - Lien vers la connexion

#### `public/dashboard.php`
- **Description :** Tableau de bord utilisateur
- **Variables disponibles :**
  - `$current_user` : Utilisateur connecté
  - `$user_projects` : Projets de l'utilisateur
- **Fonctionnalités :**
  - Liste des projets
  - Création de nouveaux projets
  - Actions sur les projets (éditer/supprimer)

#### `public/canvas.php`
- **Description :** Interface Business Model Canvas
- **Variables disponibles :**
  - `$project` : Projet en cours
  - `$canvas_data` : Données du canvas
- **Fonctionnalités :**
  - 9 sections du BMC
  - Sauvegarde automatique
  - Export PDF

## Utilisation dans le code

### Dans les shortcodes
```php
public function login_form() {
    if (WP_BMC_Auth::is_logged_in()) {
        return '<div class="message">Déjà connecté</div>';
    }
    
    return WP_BMC_Template_Loader::get_template_content('public/login-form');
}
```

### Dans les pages d'administration
```php
WP_BMC_Template_Loader::load_template('admin/dashboard', array(
    'message' => isset($message) ? $message : null
));
```

## Avantages du système de templates

1. **Séparation des préoccupations :** Logique métier séparée de la présentation
2. **Réutilisabilité :** Templates réutilisables dans différents contextes
3. **Maintenabilité :** Code plus facile à maintenir et modifier
4. **Lisibilité :** Structure claire et organisée
5. **Sécurité :** Variables passées de manière contrôlée

## Bonnes pratiques

1. **Toujours vérifier ABSPATH :** Chaque template doit commencer par cette vérification
2. **Échapper les données :** Utiliser `esc_html()`, `esc_attr()`, etc.
3. **Passer les variables :** Utiliser le paramètre `$args` pour passer des données
4. **Documenter les variables :** Commenter les variables disponibles dans chaque template
5. **Gérer les erreurs :** Vérifier l'existence des fichiers avant de les charger

## Personnalisation

Les templates peuvent être facilement personnalisés en modifiant les fichiers dans le dossier `templates/`. Les modifications seront préservées lors des mises à jour du plugin.

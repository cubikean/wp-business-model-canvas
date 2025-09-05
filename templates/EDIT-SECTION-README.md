# Template d'édition réutilisable - WP Business Model Canvas

## Vue d'ensemble

Le template d'édition des briques a été extrait dans des fichiers séparés pour permettre une réutilisation facile dans différentes parties du plugin (public et admin).

## Structure des fichiers

```
templates/
├── public/
│   └── edit-section.php      # Template d'édition pour le front-end
└── admin/
    └── edit-section.php      # Template d'édition pour l'admin
```

## Fonction utilitaire

Une fonction utilitaire `wp_bmc_include_edit_section()` a été créée pour faciliter l'inclusion du bon template selon le contexte :

```php
// Pour le front-end (public)
wp_bmc_include_edit_section('public');

// Pour l'admin
wp_bmc_include_edit_section('admin');

// Par défaut (utilise 'public')
wp_bmc_include_edit_section();
```

## Fonctionnalités du template

Le template d'édition inclut :

### 1. **Vue d'édition principale**
- Header avec bouton de retour et titre
- Éditeur WYSIWYG pour le contenu des briques
- Section de gestion des fichiers attachés
- Section des documents de référence
- Boutons d'action (Annuler/Sauvegarder)

### 2. **Popup des documents de référence**
- Affichage des documents disponibles
- Interface de consultation
- Boutons de fermeture

### 3. **Éléments interactifs**
- Bouton de retour avec flèche (`#back-to-dashboard`)
- Bouton d'annulation (`#edit-cancel`)
- Bouton de sauvegarde (`#edit-save`)
- Bouton d'ajout de fichiers (`#add-file-btn`)
- Bouton de consultation des documents (`#view-documents-btn`)

## Utilisation dans le JavaScript

Le JavaScript existant (`dashboard.js`) fonctionne avec les nouveaux templates car les IDs et classes CSS restent identiques :

- `#wp-bmc-edit-view` : Conteneur principal de la vue d'édition
- `#edit-section-title` : Titre de la section en cours d'édition
- `#wysiwyg-editor` : Zone de l'éditeur WYSIWYG
- `#files-list` : Liste des fichiers attachés
- `#documents-list` : Liste des documents de référence

## Avantages de cette approche

1. **Réutilisabilité** : Le même template peut être utilisé dans différents contextes
2. **Maintenabilité** : Les modifications se font en un seul endroit
3. **Flexibilité** : Possibilité d'avoir des versions spécifiques (public/admin)
4. **Cohérence** : Interface uniforme dans toute l'application
5. **Évolutivité** : Facile d'ajouter de nouveaux contextes (mobile, API, etc.)

## Exemple d'utilisation complète

```php
// Dans un template de page
<div class="my-page">
    <h1>Ma page</h1>
    <div class="content">
        <!-- Contenu de la page -->
    </div>
    
    <!-- Inclure le template d'édition -->
    <?php wp_bmc_include_edit_section('public'); ?>
</div>
```

Le template sera automatiquement inclus et prêt à être utilisé avec le JavaScript existant.

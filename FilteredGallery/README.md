# FilteredGallery - Plugin WordPress

Un plugin WordPress pour créer des galeries d'images avec filtres par catégories. Permet d'afficher des images organisées par catégories avec des boutons de filtrage interactifs.

## 🚀 Fonctionnalités

- **Galeries d'images filtrées** : Organisez vos images par catégories
- **Filtrage AJAX** : Filtrage dynamique sans rechargement de page
- **Lightbox intégré** : Affichage en plein écran des images
- **Design responsive** : S'adapte à tous les écrans
- **Navigation clavier** : Utilisez les flèches et Escape pour naviguer
- **Chargement différé** : Optimisation des performances
- **Shortcode simple** : Intégration facile dans vos pages
- **Widget disponible** : Ajoutez des galeries dans vos sidebars
- **Interface d'administration** : Gestion facile des images et catégories

## 📋 Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- jQuery (inclus avec WordPress)

## 🛠️ Installation

1. Téléchargez le plugin dans le dossier `/wp-content/plugins/`
2. Activez le plugin via le menu 'Extensions' dans WordPress
3. Accédez à 'Filtered Gallery' dans le menu d'administration

## 📖 Utilisation

### Shortcode

Utilisez le shortcode `[filtered_gallery]` dans vos pages ou articles :

```php
// Galerie simple
[filtered_gallery]

// Galerie avec options
[filtered_gallery category="nature" columns="4" limit="12"]
```

#### Paramètres du shortcode

- `category` : Afficher seulement une catégorie spécifique
- `columns` : Nombre de colonnes (1-6)
- `limit` : Nombre maximum d'images à afficher
- `orderby` : Critère de tri (date, title, sort_order)
- `order` : Ordre de tri (ASC, DESC)

### Widget

1. Allez dans Apparence > Widgets
2. Ajoutez le widget "Filtered Gallery" à votre sidebar
3. Configurez les options du widget

### PHP

Vous pouvez également utiliser le plugin programmatiquement :

```php
// Afficher une galerie
echo do_shortcode('[filtered_gallery columns="3"]');

// Récupérer les images
$gallery = new FilteredGallery();
$images = $gallery->get_gallery_images(['category' => 'nature']);
```

## 🎨 Personnalisation

### CSS

Le plugin utilise des variables CSS pour faciliter la personnalisation :

```css
:root {
    --fg-primary-color: #0073aa;
    --fg-secondary-color: #005a87;
    --fg-accent-color: #ff6b35;
    --fg-text-color: #333333;
    --fg-light-gray: #f5f5f5;
    --fg-border-color: #e0e0e0;
    --fg-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    --fg-border-radius: 4px;
    --fg-transition: all 0.3s ease;
    --fg-spacing: 20px;
}
```

### JavaScript

Le plugin expose une API JavaScript :

```javascript
// Créer une galerie programmatiquement
window.createFilteredGallery('#container', {
    columns: 4,
    spacing: 30,
    enableLightbox: true,
    enableLazyLoading: true
});
```

## 🔧 Configuration

### Options par défaut

Le plugin définit les options suivantes :

- `gallery_columns` : 3 (nombre de colonnes par défaut)
- `gallery_spacing` : 20 (espacement entre les images)
- `enable_lightbox` : true (activer le lightbox)
- `enable_lazy_loading` : true (activer le chargement différé)
- `thumbnail_size` : 'medium' (taille des miniatures)
- `image_size` : 'large' (taille des images en lightbox)

### Modifier les options

```php
// Dans functions.php de votre thème
add_filter('filtered_gallery_options', function($options) {
    $options['gallery_columns'] = 4;
    $options['enable_lightbox'] = false;
    return $options;
});
```

## 📁 Structure des fichiers

```
FilteredGallery/
├── filtered-gallery.php          # Fichier principal
├── README.md                     # Documentation
├── assets/
│   ├── css/
│   │   ├── filtered-gallery.css  # Styles frontend
│   │   └── admin.css             # Styles admin
│   └── js/
│       ├── filtered-gallery.js   # JavaScript frontend
│       └── admin.js              # JavaScript admin
├── includes/
│   ├── class-filtered-gallery-admin.php
│   ├── class-filtered-gallery-widget.php
│   └── class-filtered-gallery-shortcode.php
├── templates/
│   ├── gallery-template.php
│   ├── gallery-items.php
│   └── admin/
│       ├── main-page.php
│       ├── add-image.php
│       └── categories.php
└── languages/                    # Fichiers de traduction
```

## 🗄️ Base de données

Le plugin crée deux tables :

### `wp_filtered_gallery_categories`
- `id` : Identifiant unique
- `name` : Nom de la catégorie
- `slug` : Slug unique
- `description` : Description de la catégorie
- `created_at` : Date de création

### `wp_filtered_gallery_images`
- `id` : Identifiant unique
- `title` : Titre de l'image
- `description` : Description de l'image
- `image_url` : URL de l'image
- `thumbnail_url` : URL de la miniature
- `category_id` : ID de la catégorie
- `sort_order` : Ordre de tri
- `created_at` : Date de création

## 🔌 Hooks et filtres

### Actions

```php
// Avant l'affichage de la galerie
do_action('filtered_gallery_before_display', $gallery_id, $options);

// Après l'affichage de la galerie
do_action('filtered_gallery_after_display', $gallery_id, $options);

// Avant le filtrage AJAX
do_action('filtered_gallery_before_filter', $category, $gallery_id);

// Après le filtrage AJAX
do_action('filtered_gallery_after_filter', $category, $gallery_id, $images);
```

### Filtres

```php
// Modifier les options de la galerie
apply_filters('filtered_gallery_options', $options);

// Modifier les arguments de requête
apply_filters('filtered_gallery_query_args', $args, $gallery_id);

// Modifier le HTML de la galerie
apply_filters('filtered_gallery_html', $html, $images, $options);

// Modifier les classes CSS
apply_filters('filtered_gallery_item_classes', $classes, $image, $options);
```

## 🐛 Dépannage

### Problèmes courants

1. **Les images ne s'affichent pas**
   - Vérifiez que les URLs des images sont correctes
   - Assurez-vous que les permissions de fichiers sont correctes

2. **Le filtrage AJAX ne fonctionne pas**
   - Vérifiez que jQuery est chargé
   - Contrôlez la console JavaScript pour les erreurs

3. **Le lightbox ne s'ouvre pas**
   - Vérifiez que les images ont des URLs valides
   - Assurez-vous qu'il n'y a pas de conflit avec d'autres plugins

### Mode debug

Activez le mode debug WordPress pour voir les erreurs :

```php
// Dans wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

## 📄 Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## 🆘 Support

Pour obtenir de l'aide :

- Consultez la documentation
- Vérifiez les problèmes connus
- Ouvrez une issue sur GitHub

## 📝 Changelog

### Version 1.0.0
- Version initiale
- Galeries d'images avec filtres
- Lightbox intégré
- Interface d'administration
- Widget et shortcode
- Support responsive
- Navigation clavier
- Chargement différé

---

**Développé avec ❤️ pour la communauté WordPress** 
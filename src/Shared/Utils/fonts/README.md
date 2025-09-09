# Système de Polices WP Business Model Canvas

## 📁 Structure des fichiers

```
src/Shared/utils/fonts/
├── urbanist.css                    # Définitions CSS des polices
├── class-wp-bmc-fonts.php         # Classe de gestion des polices
└── Urbanist-var.woff2             # Police variable Urbanist (tous les poids)
```

## 🎨 Polices disponibles

- **Urbanist Regular** (400) - Police normale
- **Urbanist Medium** (500) - Police medium
- **Urbanist SemiBold** (600) - Police semi-grasse
- **Urbanist Bold** (700) - Police grasse
- **Urbanist ExtraBold** (800) - Police extra-grasse

## 🔧 Utilisation

### Variables CSS disponibles

```css
:root {
    --font-primary: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    --font-weight-extrabold: 800;
}
```

### Classes utilitaires

```css
.font-normal     /* font-weight: 400 */
.font-medium     /* font-weight: 500 */
.font-semibold   /* font-weight: 600 */
.font-bold       /* font-weight: 700 */
.font-extrabold  /* font-weight: 800 */
```

### Application dans le CSS

```css
body {
    font-family: var(--font-primary);
}

h1 {
    font-family: var(--font-primary);
    font-weight: var(--font-weight-bold);
}
```

## 📥 Remplacement des polices

Pour utiliser la police variable Urbanist :

1. Téléchargez la police variable Urbanist depuis [Google Fonts](https://fonts.google.com/specimen/Urbanist)
2. Sélectionnez "Download family" et choisissez "Variable" dans les options
3. Remplacez le fichier `Urbanist-var.woff2` dans le dossier `fonts/`
4. La police variable supporte tous les poids de 100 à 900 dans un seul fichier

## 🚀 Chargement automatique

Les polices sont automatiquement chargées :
- Dans l'admin WordPress via `wp_bmc_admin_scripts()`
- Dans les pages publiques via `WP_BMC_Loader::enqueue_public_scripts()`
- Avec dépendance correcte pour éviter les problèmes de chargement

## 🔍 Vérification

Pour vérifier que les polices sont chargées :

```php
// Vérifier les fichiers manquants
$missing = WP_BMC_Fonts::check_font_files();

// Obtenir les polices disponibles
$fonts = WP_BMC_Fonts::get_available_fonts();

// Obtenir la configuration
$config = WP_BMC_Fonts::get_font_config();
```

## 📝 Notes importantes

- Utilise une police variable qui supporte tous les poids dans un seul fichier
- Plus efficace qu'avoir plusieurs fichiers de police séparés
- Le système utilise `font-display: swap` pour de meilleures performances
- Les polices sont chargées avec les bonnes dépendances CSS
- La police variable supporte les poids de 100 à 900

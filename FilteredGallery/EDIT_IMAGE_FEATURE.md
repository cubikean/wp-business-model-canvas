# Fonctionnalité d'édition d'image - FilteredGallery

## Description

Cette fonctionnalité permet d'éditer les propriétés d'une image dans la galerie directement depuis l'interface d'administration WordPress.

## Fonctionnalités ajoutées

### 1. Bouton "Éditer" dans la liste des images
- Ajout d'un bouton "Éditer" à côté du bouton "Retirer" dans le tableau des images
- Le bouton contient les données de l'image (titre, description, catégorie, ordre de tri)

### 2. Modal d'édition
- Interface modale pour éditer les propriétés de l'image
- Formulaire avec les champs suivants :
  - Titre (obligatoire)
  - Description (optionnel)
  - Catégorie (optionnel)
  - Ordre de tri (numérique)

### 3. Fonctionnalités AJAX
- `ajax_get_image` : Récupère les données d'une image pour l'édition
- `ajax_update_image` : Met à jour les données d'une image

### 4. Styles CSS
- Styles pour le formulaire d'édition
- Styles pour les boutons d'action dans le tableau
- Responsive design

## Utilisation

1. Aller dans l'administration WordPress > Filtered Gallery
2. Dans la liste des images, cliquer sur le bouton "Éditer"
3. Modifier les propriétés souhaitées dans le formulaire
4. Cliquer sur "Enregistrer" pour sauvegarder les modifications

## Fichiers modifiés

### Templates
- `templates/admin/main-page.php` : Ajout du bouton d'édition et du modal

### Classes PHP
- `includes/class-filtered-gallery-admin.php` : Ajout des méthodes AJAX

### Assets
- `assets/js/admin.js` : Gestion des événements d'édition
- `assets/css/admin.css` : Styles pour l'interface d'édition

### Base de données
- Ajout du champ `updated_at` dans la table `filtered_gallery_images`

## Sécurité

- Vérification des nonces pour toutes les requêtes AJAX
- Vérification des permissions utilisateur
- Sanitisation des données d'entrée
- Échappement des données de sortie

## Compatibilité

- Compatible avec WordPress 5.0+
- Compatible avec PHP 7.4+
- Utilise les standards WordPress pour l'interface d'administration 
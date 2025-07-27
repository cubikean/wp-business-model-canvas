# Fonctionnalité de modification en lot - FilteredGallery

## Description

Cette fonctionnalité permet de modifier les catégories de plusieurs images à la fois, facilitant ainsi l'organisation de la galerie.

## Fonctionnalités

### 1. Sélection multiple d'images
- Cases à cocher pour chaque image
- Case "Sélectionner tout" avec état indéterminé
- Mise en surbrillance des lignes sélectionnées

### 2. Actions en lot
- Sélecteur d'action : "Changer la catégorie"
- Interface pour choisir la nouvelle catégorie
- Bouton d'application avec confirmation

### 3. Interface utilisateur
- Zone d'actions en lot en haut du tableau
- Sélecteur de catégorie avec toutes les catégories disponibles
- Bouton d'application avec feedback visuel

## Utilisation

### Étape 1 : Sélectionner les images
1. Utiliser la case "Sélectionner tout" pour sélectionner toutes les images
2. Ou cocher individuellement les images souhaitées
3. Les lignes sélectionnées sont mises en surbrillance

### Étape 2 : Choisir l'action
1. Dans le sélecteur "Actions en lot", choisir "Changer la catégorie"
2. L'interface de sélection de catégorie apparaît

### Étape 3 : Appliquer les modifications
1. Choisir la nouvelle catégorie dans le menu déroulant
2. Cliquer sur "Appliquer aux images sélectionnées"
3. Confirmer l'action dans la boîte de dialogue
4. Les modifications sont appliquées et la page se recharge

## Fonctionnalités techniques

### Sécurité
- Vérification des nonces pour toutes les requêtes AJAX
- Validation des permissions utilisateur
- Sanitisation des données d'entrée

### Performance
- Mise à jour en lot optimisée
- Gestion des erreurs individuelles
- Feedback détaillé sur les opérations

### Interface
- Design responsive
- États visuels clairs (sélection, désactivation)
- Messages de confirmation et d'erreur

## Fichiers modifiés

### Templates
- `templates/admin/main-page.php` : Ajout de l'interface de sélection et d'actions en lot

### Classes PHP
- `includes/class-filtered-gallery-admin.php` : Ajout de la méthode `ajax_bulk_update_category`

### Assets
- `assets/js/admin.js` : Gestion des événements de sélection et d'actions en lot
- `assets/css/admin.css` : Styles pour l'interface de sélection et d'actions

### Traductions
- Ajout des nouvelles chaînes de traduction dans le fichier principal

## Messages d'interface

### Confirmation
- "Êtes-vous sûr de vouloir changer la catégorie de X image(s) vers [Catégorie] ?"

### Succès
- "X image(s) mise(s) à jour avec succès."

### Erreurs
- "Veuillez sélectionner au moins une image."
- "Aucune image n'a pu être mise à jour."
- "Erreur lors de la mise à jour en lot."

## Compatibilité

- Compatible avec WordPress 5.0+
- Compatible avec PHP 7.4+
- Interface responsive
- Accessibilité améliorée avec les cases à cocher

## Extensibilité

La structure permet d'ajouter facilement d'autres actions en lot :
- Suppression en lot
- Modification du titre en lot
- Changement d'ordre de tri en lot
- Export en lot 
# Guide de dépannage - FilteredGallery

## Problème : Erreur "undefined" lors de l'édition d'image

### Étapes de résolution

#### 1. Vérifier la structure de la base de données

1. Aller dans WordPress Admin > Filtered Gallery
2. Dans la section "Outils", cliquer sur "Tester la base de données"
3. Vérifier que :
   - La table existe
   - Le champ `updated_at` est présent
   - Toutes les colonnes nécessaires sont présentes

#### 2. Mettre à jour la structure si nécessaire

Si le champ `updated_at` n'existe pas :
1. Cliquer sur "Mettre à jour les tables" dans la section Outils
2. Attendre la confirmation
3. Recharger la page

#### 3. Vérifier les logs d'erreur

1. Ouvrir la console du navigateur (F12)
2. Aller dans l'onglet "Console"
3. Essayer d'éditer une image
4. Vérifier les messages d'erreur dans la console

#### 4. Vérifier les permissions

Assurez-vous que :
- Vous êtes connecté en tant qu'administrateur
- Vous avez les permissions `manage_options`

#### 5. Vérifier les données envoyées

Dans la console du navigateur, vous devriez voir :
```
Données envoyées pour mise à jour: {action: "filtered_gallery_update_image", image_id: "1", title: "...", ...}
```

#### 6. Vérifier la réponse AJAX

Dans la console, vous devriez voir :
```
Réponse AJAX: {success: true, data: "Image mise à jour avec succès."}
```

### Messages d'erreur courants

#### "Erreur de sécurité"
- Le nonce a expiré
- Recharger la page et réessayer

#### "Vous n'avez pas les permissions nécessaires"
- Vérifier que vous êtes administrateur
- Vérifier les permissions utilisateur

#### "Image non trouvée"
- L'ID de l'image est invalide
- L'image a été supprimée

#### "Erreur lors de la mise à jour: [message SQL]"
- Problème de structure de base de données
- Utiliser le bouton "Mettre à jour les tables"

### Structure de base de données attendue

La table `wp_filtered_gallery_images` doit contenir :
- `id` (mediumint, auto-increment)
- `attachment_id` (bigint)
- `title` (varchar)
- `description` (text)
- `category_id` (mediumint, nullable)
- `sort_order` (int)
- `created_at` (datetime)
- `updated_at` (datetime)

### Test manuel

Pour tester manuellement la fonctionnalité :

1. Ajouter une image à la galerie
2. Cliquer sur "Éditer"
3. Modifier le titre
4. Changer la catégorie
5. Cliquer sur "Enregistrer"
6. Vérifier que les modifications sont sauvegardées

### Support

Si le problème persiste :
1. Vérifier les logs WordPress (`wp-content/debug.log`)
2. Vérifier les logs du serveur web
3. Tester avec un thème par défaut
4. Désactiver les autres plugins temporairement 
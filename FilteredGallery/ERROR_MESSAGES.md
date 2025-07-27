# Messages d'erreur et de succès - FilteredGallery

## Messages lors de l'ajout d'images

### Cas de succès partiel
- **Message** : "2 image(s) ajoutée(s) avec succès. 1 image(s) ignorée(s) (déjà présentes dans la galerie)."
- **Signification** : Certaines images ont été ajoutées, d'autres étaient déjà présentes

### Cas où toutes les images existent déjà
- **Message** : "Toutes les images sélectionnées sont déjà dans la galerie."
- **Signification** : Aucune nouvelle image n'a été ajoutée car elles existaient toutes déjà

### Cas d'erreur complète
- **Message** : "Aucune image n'a pu être ajoutée."
- **Signification** : Erreur technique lors de l'ajout des images

## Messages lors de la suppression en lot

### Confirmation de suppression
- **Message** : "Êtes-vous sûr de vouloir supprimer définitivement 5 image(s) ?"
- **Action** : Demande de confirmation avant suppression

### Succès de suppression
- **Message** : "5 image(s) supprimée(s) avec succès."
- **Signification** : Les images ont été supprimées de la galerie

## Messages lors de la modification en lot

### Confirmation de changement de catégorie
- **Message** : "Êtes-vous sûr de vouloir changer la catégorie de 3 image(s) vers 'Nature' ?"
- **Action** : Demande de confirmation avant modification

### Succès de modification
- **Message** : "3 image(s) mise(s) à jour avec succès."
- **Signification** : Les catégories ont été modifiées

## Messages d'erreur système

### Erreur de sécurité
- **Message** : "Erreur de sécurité."
- **Cause** : Nonce expiré ou invalide
- **Solution** : Recharger la page

### Erreur de permissions
- **Message** : "Vous n'avez pas les permissions nécessaires."
- **Cause** : Utilisateur non administrateur
- **Solution** : Se connecter en tant qu'administrateur

### Erreur de base de données
- **Message** : "Erreur lors de la mise à jour: [détails SQL]"
- **Cause** : Problème de structure de base de données
- **Solution** : Utiliser le bouton "Mettre à jour les tables"

## Améliorations apportées

### Avant
- Message générique "Aucune image n'a pu être ajoutée" même quand les images existaient déjà
- Pas de distinction entre erreur technique et images déjà présentes

### Après
- Messages détaillés indiquant le nombre d'images ajoutées vs ignorées
- Distinction claire entre images déjà présentes et erreurs techniques
- Feedback précis pour l'utilisateur sur ce qui s'est passé

## Utilisation

Ces messages permettent à l'utilisateur de :
1. Comprendre exactement ce qui s'est passé
2. Savoir combien d'images ont été traitées
3. Identifier les problèmes spécifiques
4. Prendre les actions appropriées 
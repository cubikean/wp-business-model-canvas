# Guide d'Import CSV - WP Business Model Canvas

## Vue d'ensemble

Le système d'import CSV permet de créer en masse :
- ✅ Utilisateurs (étudiants)
- ✅ Superviseurs (administrateurs)
- ✅ Projets avec assignations automatiques

**💡 Avantage majeur** : Vous pouvez utiliser **UN SEUL FICHIER CSV** pour tout créer en 3 étapes !

## Format du fichier CSV

### Colonnes requises

Votre fichier CSV doit contenir les colonnes suivantes :

| Colonne | Description | Obligatoire | Utilisé pour |
|---------|-------------|-------------|--------------|
| **Prénom** | Prénom de l'utilisateur | ✅ Oui | Utilisateurs |
| **Nom** | Nom de l'utilisateur | ✅ Oui | Utilisateurs |
| **E-mail** | Email de l'utilisateur | ✅ Oui | Utilisateurs + Projets |
| **Candidature** | ID personnalisé unique | ✅ Oui | Utilisateurs |
| **Tuteur** | Nom complet du superviseur | ✅ Oui | Superviseurs |
| **Coordonnées du tuteur** | Email du superviseur | ✅ Oui | Superviseurs + Projets |
| **Nom du projet** | Titre du projet | ✅ Oui | Projets |
| **Résumé du projet** | Description du projet | ⚠️ Optionnel | Projets |

### Exemple de fichier CSV

```csv
Prénom,Nom,E-mail,Candidature,Tuteur,Coordonnées du tuteur,Nom du projet,Résumé du projet
Jean,Dupont,jean.dupont@example.com,CAND001,Marie Martin,marie.martin@example.com,Startup Tech Innovation,Application mobile pour la gestion de projets
Sophie,Bernard,sophie.bernard@example.com,CAND002,Marie Martin,marie.martin@example.com,E-commerce Bio,Plateforme de vente en ligne de produits biologiques
Pierre,Dubois,pierre.dubois@example.com,CAND003,Jean Lefebvre,jean.lefebvre@example.com,Service Consulting,Cabinet de conseil en transformation digitale
```

## Processus d'import en 3 étapes

### Étape 1 : Importer les utilisateurs

📍 **BMC > Utilisateurs > Import CSV**

1. Uploadez votre fichier CSV complet
2. Le système va créer les utilisateurs en utilisant les colonnes :
   - `Prénom` → Prénom de l'utilisateur
   - `Nom` → Nom de l'utilisateur
   - `E-mail` → Email (doit être unique)
   - `Candidature` → ID personnalisé (doit être unique)
   - **Mot de passe généré** : `Candidature + Prénom`

**Exemple** : `CAND001` + `Jean` = `CAND001Jean`

3. Chaque utilisateur reçoit un email avec ses identifiants

### Étape 2 : Importer les superviseurs

📍 **BMC > Utilisateurs > Import CSV Superviseurs**

1. Uploadez le **même fichier CSV**
2. Le système va créer les superviseurs en utilisant :
   - `Tuteur` → Nom complet (séparé en prénom/nom)
   - `Coordonnées du tuteur` → Email (doit être unique)
   - **Mot de passe généré** : `Prénom + 6 caractères aléatoires`

**Exemple** : `Marie` → `MarieA7x2Kb`

3. Chaque superviseur reçoit un email avec ses identifiants et privilèges

### Étape 3 : Importer les projets

📍 **BMC > Projets > Import CSV**

1. Uploadez le **même fichier CSV**
2. Le système va :
   - Créer les projets avec `Nom du projet` et `Résumé du projet`
   - **Assigner automatiquement l'utilisateur** (via `E-mail`)
   - **Assigner automatiquement le superviseur** (via `Coordonnées du tuteur`)

**Note** : Les utilisateurs et superviseurs doivent déjà exister (étapes 1 et 2)

## Génération des mots de passe

### Utilisateurs
```
Formule : Candidature + Prénom
Exemple : CAND001Jean
Caractéristiques : Prévisible, basé sur les données
```

### Superviseurs
```
Formule : Prénom + 6 caractères aléatoires (lettres + chiffres)
Exemple : MarieA7x2Kb
Caractéristiques : Sécurisé, aléatoire
```

## Gestion des doublons

Le système détecte automatiquement :
- ❌ Emails déjà existants (ignorés)
- ❌ IDs personnalisés déjà utilisés (ignorés)
- ❌ Usernames déjà pris (génère un nouveau : `jean.dupont1`)

## Résultats de l'import

Après chaque import, vous verrez :

```
┌─────────────────────────────────────┐
│ ✓ 25 créés │ ⚠ 3 ignorés │ ✗ 2 erreurs │
└─────────────────────────────────────┘

✓ Entités créées avec succès
  • Détails de chaque création
  
✗ Erreurs rencontrées
  • Ligne 5 : Message d'erreur
  • Ligne 8 : Message d'erreur
```

## Workflow complet recommandé

### Option 1 : Import complet (recommandé)

1. **Préparez votre fichier CSV** avec toutes les colonnes
2. **Importez les utilisateurs** (étape 1)
3. **Importez les superviseurs** (étape 2)
4. **Importez les projets** (étape 3)
5. ✅ Tout est prêt ! Les assignations sont faites automatiquement

### Option 2 : Import progressif

Vous pouvez aussi importer en plusieurs fois :
- Créer d'abord les utilisateurs
- Créer les superviseurs plus tard
- Créer les projets quand vous voulez
- Assigner manuellement si nécessaire

## Gestion des erreurs

### Erreurs courantes et solutions

| Erreur | Cause | Solution |
|--------|-------|----------|
| "Colonnes manquantes" | En-têtes incorrects | Vérifiez l'orthographe exacte des colonnes |
| "Email existe déjà" | Doublon dans la base | Supprimez l'utilisateur existant ou changez l'email |
| "ID personnalisé existe déjà" | Doublon Candidature | Utilisez un ID unique |
| "Utilisateur non trouvé" | Import projets avant users | Importez d'abord les utilisateurs |
| "Superviseur non trouvé" | Import projets avant supervisors | Importez d'abord les superviseurs |

### Logs de débogage

Activez les logs dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Consultez ensuite `wp-content/debug.log` pour les détails.

## Emails automatiques

### Utilisateurs
```
Objet : Bienvenue sur WP Business Model Canvas
Contenu :
  - Prénom et nom
  - Email de connexion
  - Mot de passe (Candidature + Prénom)
  - ID personnalisé
```

### Superviseurs
```
Objet : Bienvenue - Accès Superviseur
Contenu :
  - Badge "ACCÈS SUPERVISEUR"
  - Nom complet
  - Email et username
  - Mot de passe (Prénom + aléatoire)
  - Liste des privilèges
```

## Exemple pratique complet

### Fichier CSV : `inscriptions-2024.csv`

```csv
Prénom,Nom,E-mail,Candidature,Tuteur,Coordonnées du tuteur,Nom du projet,Résumé du projet
Jean,Dupont,jean.dupont@example.com,CAND001,Marie Martin,marie.martin@example.com,Startup Tech,Application mobile innovante
Sophie,Bernard,sophie.bernard@example.com,CAND002,Marie Martin,marie.martin@example.com,E-commerce Bio,Vente en ligne de produits bio
Pierre,Dubois,pierre.dubois@example.com,CAND003,Jean Lefebvre,jean.lefebvre@example.com,Consulting,Cabinet de conseil digital
```

### Résultat après les 3 imports

**Utilisateurs créés :**
- Jean Dupont (jean.dupont@example.com) - Mot de passe : `CAND001Jean`
- Sophie Bernard (sophie.bernard@example.com) - Mot de passe : `CAND002Sophie`
- Pierre Dubois (pierre.dubois@example.com) - Mot de passe : `CAND003Pierre`

**Superviseurs créés :**
- Marie Martin (marie.martin@example.com) - Mot de passe : `MarieK9p2Lm`
- Jean Lefebvre (jean.lefebvre@example.com) - Mot de passe : `JeanT4z8Qn`

**Projets créés et assignés :**
- **Startup Tech**
  - Utilisateur : Jean Dupont ✅
  - Superviseur : Marie Martin ✅
  
- **E-commerce Bio**
  - Utilisateur : Sophie Bernard ✅
  - Superviseur : Marie Martin ✅
  
- **Consulting**
  - Utilisateur : Pierre Dubois ✅
  - Superviseur : Jean Lefebvre ✅

## Limitations

- ⚠️ Taille maximale du fichier : Limite PHP (généralement 2-8 MB)
- ⚠️ Nombre de lignes : Pas de limite technique, mais préférez < 1000 lignes par import
- ⚠️ Format CSV : Séparateur virgule (`,`), encodage UTF-8
- ⚠️ Les projets nécessitent que les users et superviseurs existent déjà

## Conseils d'utilisation

### Préparer votre CSV

1. **Utilisez un tableur** (Excel, Google Sheets, LibreOffice)
2. **Nommez exactement les colonnes** comme indiqué
3. **Exportez en CSV** (UTF-8 recommandé)
4. **Vérifiez les emails** (format valide)
5. **Assurez l'unicité** des emails et IDs

### Ordre d'import optimal

```
1. Import Utilisateurs
   ↓
2. Import Superviseurs
   ↓
3. Import Projets (avec assignations auto)
```

### Tester avec un petit échantillon

Avant d'importer 100 lignes, testez avec 2-3 lignes pour valider le format.

## Support

En cas de problème :
1. Consultez les logs (`wp-content/debug.log`)
2. Vérifiez le format exact des colonnes
3. Testez avec un fichier de 2 lignes
4. Contactez le support avec les logs

## Sécurité

- ✅ Tous les imports nécessitent les droits administrateur
- ✅ Vérification nonce pour chaque opération
- ✅ Sanitisation de toutes les données
- ✅ Validation des emails et IDs
- ✅ Emails envoyés de manière sécurisée
- ✅ Mots de passe hashés dans la base de données


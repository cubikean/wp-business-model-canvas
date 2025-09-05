# Gestion des utilisateurs - Dashboard Admin WP Business Model Canvas

## Vue d'ensemble

Le dashboard admin a été enrichi avec une section complète de gestion des utilisateurs, permettant aux administrateurs de visualiser, rechercher, trier et gérer tous les utilisateurs du système.

## Fonctionnalités principales

### 1. **Liste complète des utilisateurs**
- Affichage de tous les utilisateurs dans un tableau structuré
- Informations détaillées : nom, email, entreprise, nombre de projets, dates
- Compteur de projets par utilisateur
- Date de dernière activité (dernier projet créé)

### 2. **Recherche et filtrage**
- **Recherche en temps réel** : Tapez dans le champ de recherche pour filtrer par nom, email ou entreprise
- **Filtrage par statut** : Afficher les utilisateurs actifs (avec projets) ou inactifs (sans projets)
- **Tri des colonnes** : Cliquez sur les en-têtes pour trier par nom, email, entreprise, nombre de projets, etc.

### 3. **Actions sur les utilisateurs**
Pour chaque utilisateur, plusieurs actions sont disponibles :

#### Boutons d'action
- **👁️ Voir le profil** : Affiche les détails complets de l'utilisateur
- **✏️ Éditer** : Permet de modifier les informations de l'utilisateur
- **📊 Voir le canvas** : Accès direct au Business Model Canvas de l'utilisateur (si existant)
- **🗑️ Supprimer** : Suppression de l'utilisateur et de tous ses projets (avec confirmation)

#### Liens contextuels
- **Email cliquable** : Ouvre le client email par défaut
- **"Voir les projets"** : Affiche la liste des projets de l'utilisateur

### 4. **Export des données**
- **Export CSV** : Bouton pour exporter la liste complète des utilisateurs
- **Export des données** : Export général des données du système

## Structure du tableau

| Colonne | Description | Triable |
|---------|-------------|---------|
| **Nom** | Prénom et nom de l'utilisateur | ✅ |
| **Email** | Adresse email (cliquable) | ✅ |
| **Entreprise** | Nom de l'entreprise | ✅ |
| **Projets** | Nombre de projets + lien "Voir les projets" | ✅ |
| **Inscription** | Date d'inscription | ✅ |
| **Dernier projet** | Date du dernier projet créé | ✅ |
| **Actions** | Boutons d'action | ❌ |

## Fonctionnalités interactives

### Recherche
- Tapez dans le champ "Rechercher un utilisateur..."
- La recherche se fait en temps réel sur le nom, email et entreprise
- Le compteur d'utilisateurs se met à jour automatiquement

### Tri
- Cliquez sur les en-têtes de colonnes pour trier
- Indicateurs visuels (▲▼) montrent l'ordre de tri
- Tri ascendant/descendant en alternance

### Filtrage
- Menu déroulant pour filtrer par statut :
  - **Tous les statuts** : Affiche tous les utilisateurs
  - **Actifs** : Utilisateurs ayant au moins un projet
  - **Inactifs** : Utilisateurs sans projet

## Raccourcis clavier

- **Ctrl + F** : Focuser sur le champ de recherche
- **Échap** : Fermer les popups ouvertes

## Responsive Design

Le tableau s'adapte aux écrans mobiles :
- Colonnes réorganisées pour les petits écrans
- Boutons d'action empilés verticalement
- Texte réduit pour une meilleure lisibilité

## Fichiers ajoutés

### JavaScript
- `admin/js/admin-users.js` : Gestion des interactions et AJAX

### CSS
- `admin/css/admin-users.css` : Styles pour le tableau et les popups

### Templates
- `templates/admin/dashboard.php` : Template principal mis à jour

## Intégration

Le système s'intègre parfaitement avec :
- Le système d'authentification existant
- La base de données des utilisateurs et projets
- Les actions d'administration WordPress
- Le système de nonces pour la sécurité

## Sécurité

- Toutes les actions utilisent les nonces WordPress
- Confirmation avant suppression d'utilisateurs
- Échappement des données affichées
- Validation côté serveur pour les actions AJAX

## Évolutions futures

Le système est conçu pour être facilement extensible :
- Ajout de nouvelles colonnes de tri
- Filtres supplémentaires (par date, entreprise, etc.)
- Actions en lot (sélection multiple)
- Intégration avec des systèmes de notification
- Historique des actions administrateur

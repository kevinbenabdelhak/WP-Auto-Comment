# WP Auto Comment
Contributors: kevinbenabdelhak   
Tags: commentaires, automatisation, OpenAI, génération, API   
Requires at least: 5.0   
Tested up to: 6.6.2   
Requires PHP: 7.0   
Stable tag: 1.6   
License: GPLv2 or later   
License URI: https://www.gnu.org/licenses/gpl-2.0.html   

Automatisez la génération de commentaires sur vos articles en utilisant l'API OpenAI pour enrichir l'interaction avec vos lecteurs.

## Description

### WP Auto Comment - Automatisez la génération de commentaires sur vos articles

WP Auto Comment est un plugin WordPress qui permet de générer automatiquement des commentaires sur vos articles en utilisant l'API OpenAI. Ce plugin enrichit vos publications en fournissant des commentaires pertinents et engageants sans nécessiter d'intervention manuelle.

[![Voir le tutoriel](https://img.youtube.com/vi/Pj8Df5n7HRg/hqdefault.jpg)](https://www.youtube.com/watch?v=Pj8Df5n7HRg)


#### Fonctionnalités principales :

1. **Génération automatique de commentaires** : Créez des commentaires sur vos articles basés sur le contenu et le style spécifiés.
2. **Paramètres configurables** : Gérez facilement les paramètres du plugin via l'interface de configuration, y compris la clé API OpenAI et le style d'écriture.
3. **Contrôle individuel** : Activez ou désactivez la génération de commentaires automatiques pour chaque article directement depuis l'interface WordPress.
4. **Planification via Cron** : Planifiez la génération de commentaires à des intervalles spécifiques pour un apport constant de contenu.
5. **Interface utilisateur intuitive** : Configuration simple et interface claire pour une utilisation facile par tous les utilisateurs.

## Installation

1. **Téléchargez le fichier ZIP du plugin :**

   Téléchargez le fichier ZIP du plugin depuis cette URL : [Télécharger WP Auto Comment](https://kevin-benabdelhak.fr/plugins/wp-auto-comment/)

2. **Uploader le fichier ZIP du plugin :**

   - Allez dans le panneau d'administration de WordPress et cliquez sur "Extensions" > "Ajouter".
   - Cliquez sur "Téléverser une extension".
   - Choisissez le fichier ZIP que vous avez téléchargé et cliquez sur "Installer maintenant".

3. **Activer le plugin :**

   Une fois le plugin installé, cliquez sur "Activer".

4. **Configurer votre compte OpenAI :**

   - Allez dans "Réglages" > "WP Auto Comment".
   - Entrez vos paramètres d'API OpenAI pour activer la génération de commentaires.

## MAJ

### 1.7 
* Compatibilité des commentaires automatique sur tout les types de contenus personnalisés (et pages)


### 1.6 
* Ajout d'une option pour générer des commentaires en fonction des visites (par IP)
* Ajout de gpt-4.1 et gpt-4.1-mini


### 1.5
* Nombre de commentaires par boucle (en aléatoire)
* Nombre maximum de commentaires sur les articles (en aléatoire)
* Activer les coms autos sur les nouvelles publications ( cases à cocher cochées par défaut )


### 1.4
* Générateur de modèles de commentaire ( génère automatiquement un brief : Nom,prénom,profession,style d'écriture)
* Possibilité d'indiquer le nombre de modèle à créer
* Modèle créé avec gpt-4o-mini


### 1.3
* Patch sur l'enregistrement des cases à cocher dans la page listing des articles

### 1.2
* Possibilité de créer des templates de commentaire (idéal pour briser les redondances de l'IA)

### 1.1
*  Ajout d'une case à cocher dans les options pour s'adresser directement à l'auteur (exemple : Bonjour Kevin, merci pour cet article)

### 1.0
*  Ajoutez des commentaires avec les actions groupées
*  Sélectionnez le nombre de commentaires par article
*  Générez avec gpt-4o-mini, gpt-4o ou gpt-3.5-turbo
*  Personnalisez les commentaires avec un prompt
*  Automatisez le nom et prenom de l'auteur
*  Choisissez une tranche pour le nombre de mot (min/max)
*  Activez la génération automatique
*  Filtrer les pages concernés dans le tableau des articles
*  Modifier l'intervalle entre les publications de commentaires

ToDoList

Description

ToDoList est une application web permettant aux utilisateurs de gérer leurs tâches de manière efficace. Ce projet a été recréé en utilisant une version plus récente de Symfony tout en conservant les fonctionnalités de l'ancienne version.

Fonctionnalités

Création, modification et suppression de tâches

Attribution automatique des tâches à l'utilisateur authentifié

Gestion des rôles utilisateur (ROLE_USER, ROLE_ADMIN)

Un utilisateur peut uniquement supprimer ses propres tâches

Seuls les administrateurs peuvent gérer les utilisateurs et supprimer les tâches anonymes

Prérequis

Avant d'installer le projet, assurez-vous d'avoir les éléments suivants :

PHP 8.1+

Composer

Symfony CLI

MySQL

Node.js et Yarn (pour la gestion des assets)

Installation

Cloner le dépôt :

git clone https://github.com/Judes77850/todolist.git
cd todolist

Installer les dépendances PHP :

composer install

Configurer la base de données :

Modifier le fichier .env pour indiquer les bonnes informations de connexion MySQL

Créer la base de données et exécuter les migrations :

symfony console doctrine:database:create
symfony console doctrine:migrations:migrate

Installer les dépendances front-end :

yarn install
yarn encore dev

Lancer le serveur Symfony :

symfony server:start

Tests

Le projet inclut des tests PHPUnit pour assurer la stabilité du code.

Pour exécuter les tests :

php bin/phpunit

Contributions

Les contributions sont les bienvenues ! Merci de suivre les bonnes pratiques de développement et d'effectuer des pull requests bien documentées.

Auteur

Julien Desaindes

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/31c6fec5b54246088701aabf5ccda369)](https://app.codacy.com/gh/Judes77850/todolist/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

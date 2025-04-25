Guide de Contribution
Processus de Contribution
Forker le dépôt : Créez un fork du projet sur votre compte GitHub.
Créer une branche : Nommez votre branche de manière descriptive, par exemple :
git checkout -b feature/ajout-authentification
Développer votre fonctionnalité : Assurez-vous de suivre les normes de code et d'écrire des tests pertinents.
Soumettre une Pull Request (PR) : Décrivez clairement les modifications apportées, les raisons et tout autre détail pertinent.
Normes de Code
Le projet suit les standards PSR-1, PSR-2, PSR-4 et PSR-12, ainsi que les conventions spécifiques à Symfony.
Indentation : 4 espaces, pas de tabulations.
Nommage :
Classes : CamelCase
Méthodes : camelCase
Variables : camelCase
Constantes : UPPER_CASE
Espaces : Un espace après chaque virgule et autour des opérateurs.
Accolades : Sur la même ligne que la déclaration.
Pour automatiser la vérification du style de code, vous pouvez utiliser PHP CS Fixer :
composer require --dev friendsofphp/php-cs-fixer
vendor/bin/php-cs-fixer fix
Tests
Le projet utilise PHPUnit pour les tests unitaires et fonctionnels.
Configuration
Le fichier phpunit.xml.dist est déjà configuré. Pour exécuter les tests :
php bin/phpunit
Bonnes Pratiques
Utilisation de fixtures : Pour les tests fonctionnels, utilisez des fixtures pour peupler la base de données avec des données de test.
Nomenclature : Les méthodes de test doivent commencer par test et être descriptives, par exemple testAjoutTacheValide.
Outils Complémentaires
PHPStan : Pour l'analyse statique du code.

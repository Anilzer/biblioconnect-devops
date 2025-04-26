
Bienvenue sur BiblioConnect, un projet Symfony pour la gestion d'une bibliothèque en ligne.
Installation

    Cloner le projet

	git clone https://github.com/ton-utilisateur/biblioconnect.git
	cd biblioconnect


    Configurer l'environnement

	Copie le fichier .env :

	cp .env .env.local

Et configure la connexion à ta base de données (DATABASE_URL), par exemple :

	DATABASE_URL="mysql://root:password@127.0.0.1:3306/biblioconnect"

    Créer la base de données

	php bin/console doctrine:database:create

    Créer les tables (migrations)

	php bin/console doctrine:migrations:migrate

    Charger des données de test (fixtures)

	php bin/console doctrine:fixtures:load

    Cela créera automatiquement :

    	Un Admin : admin@example.com / password
	
    	Un Bibliothécaire : bibliothecaire@example.com / password

    	Un Utilisateur : user@example.com / password

   Lancer le serveur

	symfony server:start


   Et c'est parti !!!
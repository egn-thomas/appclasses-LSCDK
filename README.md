# appclasses-LSCDK

Une application de gestion de classes scolaires pour l'ensemble scolaire LaSalle Coudekerque.

## Architecture

- `api/` : API Node.js avec Express et MongoDB.
- `php/` : frontend PHP servi par Apache.
- `mongo` : base de données MongoDB.

## Prérequis

- Docker
- Docker Compose

## Démarrage

1. Copier `.env.example` en `.env`
2. Modifier les valeurs si nécessaire
3. Lancer :

```bash
docker compose up --build
```

Si un MongoDB local est déjà actif sur `27017`, le service du conteneur est exposé sur `27018` pour éviter le conflit.

## Points d'entrée

- Frontend PHP : `http://localhost:8080`
- API Node.js : `http://localhost:3000`
- MongoDB (hôte) : `mongodb://localhost:27018`

## Structure du projet

- `api/Dockerfile` : construction du service API.
- `php/Dockerfile` : construction du frontend PHP.
- `docker-compose.yml` : orchestre les services `api`, `php` et `mongo`.

## Développement

- API : modifier les fichiers dans `api/src`
- Frontend : modifier `php/index.php`

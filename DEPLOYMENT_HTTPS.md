# Fix pour le Mixed Content Error en Production HTTPS

## Problème

Lors du déploiement en HTTPS (https://appclasses.lscdk.fr), les appels API retournaient une erreur "Mixed Content" :

```
Mixed Content: The page at 'https://appclasses.lscdk.fr/?page=students' was loaded over HTTPS,
but requested an insecure resource 'http://api:3000/api/upload/upload'
```

Le navigateur bloque les appels HTTP depuis une page HTTPS pour des raisons de sécurité.

## Solution Implémentée

### 1. Détection Automatique en JavaScript

Le fichier `php/src/pages/students.php` a été modifié pour détecter le contexte d'exécution :

```javascript
// En développement local: utilise http://localhost:3000
// En production: utilise /api (proxié par le reverse proxy)
const isLocalhost =
  window.location.hostname === "localhost" ||
  window.location.hostname === "127.0.0.1";
const API_URL = isLocalhost ? "http://localhost:3000" : "/api";
```

**Comportement :**

- **Développement local** (`http://localhost:8080`) → API_URL = `http://localhost:3000`
- **Production** (`https://appclasses.lscdk.fr`) → API_URL = `/api`

### 2. Configuration Reverse Proxy

Pour que ça fonctionne en production, le reverse proxy DOIT proxier les requêtes `/api/*` vers le backend API.

Un fichier `nginx.conf` d'exemple est fourni à la racine du projet. Il configure :

- Redirection HTTP → HTTPS
- Proxying `/api/*` vers le container API (port 3000)
- Proxying tout le reste vers le container PHP

## Mise en Œuvre en Production

### Étape 1 : Installer nginx (si pas déjà présent)

```bash
sudo apt-get install nginx
```

### Étape 2 : Configurer le reverse proxy

Plusieurs options selon votre setup :

#### Option A : Utiliser la config fournie

```bash
sudo cp nginx.conf /etc/nginx/sites-available/appclasses
sudo ln -s /etc/nginx/sites-available/appclasses /etc/nginx/sites-enabled/appclasses
sudo systemctl reload nginx
```

#### Option B : Adapter une config existante

Si vous avez déjà une config nginx, ajoutez ces blocs :

```nginx
upstream php_backend {
    server php:80;
}

upstream api_backend {
    server api:3000;
}

server {
    listen 443 ssl http2;
    server_name appclasses.lscdk.fr;

    # ... SSL config ...

    # Proxier /api/* vers l'API
    location /api/ {
        proxy_pass http://api_backend/;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;
    }

    # Proxier le reste vers PHP
    location / {
        proxy_pass http://php_backend;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;
    }
}
```

#### Option C : Utiliser un reverse proxy Docker (traefik)

Si vous utilisez traefik en production, vous n'avez rien à faire - traefik proxiera automatiquement.

### Étape 3 : Tester

Après déploiement :

1. Accédez à https://appclasses.lscdk.fr/?page=students
2. Ouvrez la console du navigateur (F12)
3. Testez l'import :
   - Pas d'erreurs "Mixed Content"
   - Les appels API doivent réussir
   - Vous devez voir les requêtes vers `/api/...`

## Fichiers Modifiés

- `php/src/config.php` : Nettoyage de la logique de configuration
- `php/src/pages/students.php` : Ajout de la détection localhost/production
- `nginx.conf` : Nouveau fichier de configuration d'exemple

## Notes Importantes

1. **API_URL_FRONTEND en PHP** : Créée pour la clarté, mais n'est plus utilisée après les optimisations.
2. **Appels côté serveur PHP** : Continuent d'utiliser l'URL interne `http://api:3000` (via getenv('API_URL')) - aucun changement.
3. **Protocoles HTTPS** : Le reverse proxy ajoute automatiquement les headers `X-Forwarded-Proto` pour que le backend sache qu'il reçoit HTTPS.

## Dépannage

Si vous voyez toujours des erreurs Mixed Content :

1. Vérifiez que nginx est bien configuré pour proxier `/api/*`
2. Dans la console du navigateur (F12), vérifiez que les URLs des appels API commencent par `/api/`
3. Vérifiez que le certificat SSL est valide

## Support

Pour plus d'informations sur nginx et les reverse proxies :

- [nginx Documentation](https://nginx.org/en/docs/)
- [Traefik Documentation](https://doc.traefik.io/)

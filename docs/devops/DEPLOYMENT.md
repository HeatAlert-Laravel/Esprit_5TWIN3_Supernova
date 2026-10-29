# Déploiement HeatAlert sur une VM Ubuntu

## Préparé dans le dépôt

`docker/Dockerfile` construit une cible CI avec Pest/SQLite et deux images de production PHP-FPM et Nginx. `deploy/compose.prod.yml` garde MySQL 8.4 sur un réseau Docker privé, avec volumes nommés persistants. Les images sont taguées par SHA Git complet. `docker-compose.yml` à la racine reste réservé à Sail. La cible Node 22 exécute `npm ci` et `npm run build`; PHP 8.3 installe `mbstring`, XML, ZIP, PDO MySQL et PDO SQLite. Composer utilise `composer.lock` sans dépendances de développement dans l'image finale.

Les avatars (`storage/app/public`) sont montés dans PHP et sous `/storage` dans Nginx. L'audio privé (`storage/app/private`) n'est monté que dans PHP et reste servi par le contrôleur Laravel. Les logs, le cache fichier et les vues compilées ont leurs volumes. Les sessions sont en base. Ni `.env` ni credentials ne sont copiés dans les images.

À chaque déploiement, avant de lancer la nouvelle application, `deploy.sh` exécute un conteneur temporaire `app` comme root pour attribuer à UID/GID 33 (`www-data`) les cinq volumes Laravel (`storage/app/public`, `storage/app/private`, `storage/logs`, `storage/framework/cache`, `storage/framework/views`). Il vérifie ensuite, depuis un autre conteneur temporaire exécuté comme UID/GID 33, que ces dossiers sont inscriptibles et qu'un fichier temporaire peut être créé dans le stockage public. Cette opération conserve les avatars, les audios et les autres fichiers existants; elle ne touche pas au volume MySQL. Le processus PHP-FPM permanent reste non privilégié.

## À exécuter une fois sur la VM

Vérifier les outils et les ressources :

```bash
git --version
docker version
docker compose version
java -version
df -h /var/lib/docker
free -h
sudo -u jenkins docker info
sudo -u jenkins docker compose version
```

Si `jenkins` n'accède pas à Docker, autoriser le groupe Docker puis redémarrer Jenkins :

```bash
sudo usermod -aG docker jenkins
sudo systemctl restart jenkins
sudo -u jenkins docker info
```

L'accès au socket Docker équivaut à des privilèges administrateur sur la VM : réserver l'administration Jenkins aux personnes de confiance. Prévoir au moins 2 Gio libres avant chaque déploiement. Ne pas supprimer les volumes Docker. Contrôler périodiquement les images anciennes et les sauvegardes pour rester sous 50 Go.

Créer les dépôts Docker Hub `<namespace>/heatalert-app` et `<namespace>/heatalert-nginx`. Le namespace est le paramètre Jenkins `DOCKERHUB_NAMESPACE`; il peut être un compte ou une organisation. Configurer dans Jenkins le credential `dockerhub-creds` (Username with password / access token). Aucun credential GitHub n'est requis pour ce dépôt public.

Créer la configuration privée **hors du dépôt** :

```bash
sudo install -d -m 700 -o jenkins -g jenkins /opt/heatalert/config /opt/heatalert/backups
sudo install -m 600 -o jenkins -g jenkins deploy/production.env.example /opt/heatalert/config/production.env
sudo -u jenkins nano /opt/heatalert/config/production.env
```

Dans ce fichier, définir une seule fois `APP_KEY` avec la sortie de `php artisan key:generate --show` dans un conteneur de test ou localement, puis conserver cette valeur. Ne jamais la régénérer lors d'un déploiement. Renseigner `APP_URL=http://IP_DE_LA_VM:8088`, des mots de passe forts et différents pour `DB_PASSWORD` et `MYSQL_ROOT_PASSWORD`, et éventuellement les credentials SMTP, Gemini et Groq. `DB_HOST=mysql`, `DB_DATABASE=heatalert`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`. Le premier essai peut garder `MAIL_MAILER=log`; les emails ne seront alors pas envoyés. Vérifier que tous les placeholders sont remplacés. Le port HTTP est 8088 par défaut (`HTTP_PORT` peut être défini dans le fichier privé); ouvrir ce port uniquement sur le réseau privé VMware.

Le fichier privé est à la fois le `--env-file` de Compose et le `env_file` des conteneurs. Ses permissions doivent rester 600 et il doit appartenir à `jenkins`. Ne jamais le copier dans le workspace Jenkins ni l'ajouter à Git. Les variables `DOCKERHUB_NAMESPACE` et `IMAGE_TAG` sont fournies par le job Jenkins, jamais par ce fichier. Une commande manuelle de Compose doit les exporter elle-même.

## Dans l'interface Jenkins

Suivre [JENKINS-UI.md](JENKINS-UI.md) pour créer le job Pipeline avec son script Groovy, le namespace et Poll SCM. Le premier **Build Now** teste, construit, pousse et déploie. Les builds suivants sont déclenchés par interrogation de `main` toutes les cinq minutes environ. Chaque build vérifie que HEAD est exactement `origin/main`. Le job ne déploie jamais une autre branche.

## Vérification et exploitation

Accéder à `http://IP_DE_LA_VM:8088` et `http://IP_DE_LA_VM:8088/up`. Le health check de déploiement attend HTTP 200 sur `/up` et sur l'accueil, repère un lien CSS Vite avec son hash courant, puis exige HTTP 200 et `Content-Type: text/css` pour ce fichier. Il échoue explicitement si une page HTTP locale pointe vers un CSS HTTPS. Depuis la VM :

```bash
curl -f http://127.0.0.1:8088/up
css_url="$(curl -fsS http://127.0.0.1:8088/ | grep -Eo 'https?://[^" ]+/build/assets/[^" ]+\.css' | head -n 1)"
printf '%s\n' "$css_url"
curl -I "$css_url"
```

Pour inspecter Compose manuellement, depuis une copie du dépôt sur la VM, exporter le namespace et le SHA déployé, puis exécuter :

```bash
export DOCKERHUB_NAMESPACE=<namespace>
export IMAGE_TAG=<SHA_GIT_40_CARACTÈRES>
export PRODUCTION_ENV_FILE=/opt/heatalert/config/production.env
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml ps
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml logs --tail=100 app nginx mysql
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml exec -T app php artisan migrate:status
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml exec -T app sh -c 'id; probe="$(mktemp storage/app/public/.write-check.XXXXXX)" && rm -- "$probe"'
```

Éviter `docker compose config` sans `--quiet` : la sortie peut contenir les mots de passe. Les logs Laravel persistent dans le volume `app-logs`; lire via `docker compose exec app tail -n 100 storage/logs/laravel.log` si le canal `single` est configuré. Pour sauvegarder hors cycle Jenkins, exporter les variables ci-dessus puis lancer `deploy/scripts/backup-db.sh`. La sauvegarde est automatique avant chaque migration; un échec arrête le déploiement. Garder les sauvegardes sous `/opt/heatalert/backups` avec mode 600. Surveiller la place libre; archiver hors VM puis supprimer manuellement les anciennes sauvegardes après vérification.

## Incidents fréquents

- `permission denied` sur Docker : vérifier le groupe `docker`, redémarrer Jenkins et vérifier avec `sudo -u jenkins docker info`.
- `unauthorized` lors du push : vérifier `dockerhub-creds`, le namespace et les deux dépôts Docker Hub.
- MySQL reste `unhealthy` : consulter ses logs, vérifier l'espace disque et les mots de passe du fichier privé. Si le volume a déjà été initialisé avec un autre mot de passe, modifier l'utilisateur dans MySQL; changer seulement `DB_PASSWORD` ne modifie pas un volume existant.
- `/up` échoue : vérifier `docker compose ps`, logs PHP/Nginx, `APP_KEY`, migrations et droits des volumes.
- Les avatars ne chargent pas : vérifier le volume `public-files` et l'URL `APP_URL`.
- La page est sans styles : vérifier que `APP_URL` dans le fichier privé est `http://192.168.124.133:8088` dans le laboratoire, que les liens `/build/assets/*.css` rendus utilisent HTTP et que leur réponse est `200` avec `Content-Type: text/css`. L'ancienne version du provider imposait HTTPS dès que `APP_ENV=production`, ce qui produisait `SSL: WRONG_VERSION_NUMBER` sur le port HTTP 8088; la correction suit désormais le schéma de `APP_URL`.
- Les emails ne partent pas avec `MAIL_MAILER=log` : configurer SMTP et tester l'envoi après le premier déploiement.
- Les fonctions IA sans clés Gemini/Groq ne sont pas disponibles; elles restent facultatives pour `/up`.

L'accès HTTP avec `APP_ENV=production` est réservé au réseau VMware privé : la logique Laravel, les sessions et la protection CSRF restent actives. Avant toute exposition publique, installer TLS via un proxy HTTPS, définir `APP_URL=https://...`, activer les cookies sécurisés et limiter l'accès Jenkins. Le provider forcera alors HTTPS pour les URLs générées. Définir également `HEALTHCHECK_URL` (et, si nécessaire, `HEALTHCHECK_HOME_URL`) vers le point d'accès HTTPS dans l'environnement du job Jenkins afin que le contrôle des assets teste le chemin public réel. Prévoir la sauvegarde hors VM avant de considérer cette instance comme durable.

## État de validation

Voir le rapport de livraison de la tâche pour les commandes réellement exécutées dans l'environnement de préparation. La publication Docker Hub, la connexion au Jenkins de la VM et le déploiement réel nécessitent la configuration privée ci-dessus et restent à vérifier sur cette VM.

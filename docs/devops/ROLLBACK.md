# Sauvegarde, restauration et retour applicatif

Avant toute migration, `deploy.sh` lance `backup-db.sh` et arrête le déploiement si la sauvegarde échoue. Les dumps horodatés sont compressés dans `/opt/heatalert/backups` hors du dépôt. Les conserver sur un autre support avant une migration à risque. Ne jamais utiliser `migrate:fresh`, `migrate:reset`, `db:wipe`, `docker compose down -v` ou `docker volume prune`.

## Retour à une image précédente

Sur la VM, dans une copie du dépôt contenant les scripts de déploiement, choisir un SHA publié antérieurement. Exporter `DOCKERHUB_NAMESPACE`, `PRODUCTION_ENV_FILE=/opt/heatalert/config/production.env` et `IMAGE_TAG=<ancien SHA complet>`. Vérifier d'abord que les deux images existent dans Docker Hub. Puis :

```bash
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml pull app nginx
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml up -d --no-deps app nginx
deploy/scripts/healthcheck.sh
```

Ce retour change le code et les assets seulement. Il **ne restaure pas la base**. Une migration déjà appliquée peut rendre l'ancienne image incompatible; inspecter le schéma et préparer une restauration séparée si nécessaire. Ne jamais exécuter automatiquement les anciennes migrations en sens inverse.

## Restauration manuelle de la base

Planifier une interruption de service et conserver une seconde copie du dump actuel. Vérifier l'archive (`gzip -t`). Avec les variables Compose exportées comme ci-dessus, examiner la cible et les comptes concernés. La commande ci-dessous **remplace les données de la base `heatalert`** : ne l'exécuter qu'après validation explicite de l'archive et de la fenêtre d'interruption.

```bash
gzip -dc /opt/heatalert/backups/heatalert-AAAAmmjjTHHMMSSZ.sql.gz | \
  docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml exec -T mysql \
    sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u "$MYSQL_USER" "$MYSQL_DATABASE"'
```

L'import d'un dump sur une base non vide peut échouer ou mélanger des données. Pour une restauration complète, préparer une base isolée et tester le dump avant de décider du remplacement de production. Après restauration, redémarrer l'application, vérifier `/up`, l'authentification, les avatars et l'audio privé. Les fichiers des volumes `public-files` et `private-files` ne figurent pas dans le dump MySQL : les sauvegarder séparément et restaurer l'ensemble au même point dans le temps.

# Pipeline Jenkins dans l'interface

Créer **New Item → Pipeline → Configure → Pipeline → Definition: Pipeline script**. Coller exactement le script ci-dessous. Aucun Jenkinsfile n'est nécessaire. Le seul plugin supplémentaire aux plugins standard Pipeline/Git/Credentials est **Credentials Binding** pour `withCredentials`. La VM Jenkins doit exécuter `docker info` et `docker compose version` sous l'utilisateur `jenkins` (voir [DEPLOYMENT.md](DEPLOYMENT.md)).

Créer deux dépôts Docker Hub, `heatalert-app` et `heatalert-nginx`, dans l'espace de noms indiqué par le paramètre `DOCKERHUB_NAMESPACE`. L'identifiant Jenkins existant `dockerhub-creds` doit être de type Username with password (mot de passe ou access token Docker Hub). Le clone GitHub public n'utilise aucun credential.

Le déclencheur `pollSCM` interroge GitHub environ toutes les cinq minutes. Un premier **Build Now** peut être nécessaire pour enregistrer le déclencheur et le paramètre. Le lancement manuel reste possible. La VM NAT n'a pas besoin d'un webhook entrant.

```groovy
pipeline {
    agent any
    options {
        skipDefaultCheckout(true)
        disableConcurrentBuilds()
        timeout(time: 60, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '15', daysToKeepStr: '30'))
        timestamps()
    }
    parameters {
        string(name: 'DOCKERHUB_NAMESPACE', defaultValue: '', description: 'Nom du compte ou organisation Docker Hub contenant heatalert-app et heatalert-nginx')
    }
    triggers { pollSCM('H/5 * * * *') }
    environment {
        PRODUCTION_ENV_FILE = '/opt/heatalert/config/production.env'
    }
    stages {
        stage('Checkout') {
            steps {
                deleteDir()
                checkout([$class: 'GitSCM', branches: [[name: '*/main']],
                    userRemoteConfigs: [[url: 'https://github.com/HeatAlert-Laravel/Esprit_5TWIN3_Supernova.git']],
                    extensions: [[$class: 'PruneStaleBranch']]])
                script {
                    env.IMAGE_TAG = sh(script: 'git rev-parse HEAD', returnStdout: true).trim()
                }
                sh '''#!/bin/bash
set -euo pipefail
test "$(git rev-parse HEAD)" = "$(git rev-parse refs/remotes/origin/main)"
test "$(git branch -r --contains HEAD | grep -c 'origin/main')" -ge 1
echo "Checked out main commit ${IMAGE_TAG}"
'''
            }
        }
        stage('Validate') {
            steps {
                sh '''#!/bin/bash
set -euo pipefail
[[ "$DOCKERHUB_NAMESPACE" =~ ^[a-z0-9][a-z0-9_-]*$ ]]
test -f "$PRODUCTION_ENV_FILE"
test "$(stat -c %a "$PRODUCTION_ENV_FILE")" = 600
docker info >/dev/null
docker compose version
bash -n deploy/scripts/*.sh
export IMAGE_TAG DOCKERHUB_NAMESPACE PRODUCTION_ENV_FILE
docker compose --env-file "$PRODUCTION_ENV_FILE" -f deploy/compose.prod.yml config --quiet
echo 'Validation passed'
'''
            }
        }
        stage('Tests') {
            steps {
                sh '''#!/bin/bash
set -euo pipefail
docker build -f docker/Dockerfile --target ci -t "heatalert-ci:${IMAGE_TAG}" .
docker run --rm --network none -e APP_ENV=testing -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  "heatalert-ci:${IMAGE_TAG}" sh -c ': > .env && php artisan test'
docker run --rm --network none -v "$PWD:/work:ro" -w /work node:22-bookworm-slim \
  node --test tests/js/*.test.mjs
'''
            }
        }
        stage('Build Images') {
            steps {
                sh '''#!/bin/bash
set -euo pipefail
docker build -f docker/Dockerfile --target app -t "${DOCKERHUB_NAMESPACE}/heatalert-app:${IMAGE_TAG}" .
docker build -f docker/Dockerfile --target nginx -t "${DOCKERHUB_NAMESPACE}/heatalert-nginx:${IMAGE_TAG}" .
'''
            }
        }
        stage('Docker Hub Login and Push') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'dockerhub-creds', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_TOKEN')]) {
                    sh '''#!/bin/bash
set -euo pipefail
umask 077
export DOCKER_CONFIG="$(mktemp -d)"
trap 'docker logout >/dev/null 2>&1 || true; rm -rf -- "$DOCKER_CONFIG"' EXIT
printf '%s' "$DOCKER_TOKEN" | docker login --username "$DOCKER_USER" --password-stdin >/dev/null
docker push "${DOCKERHUB_NAMESPACE}/heatalert-app:${IMAGE_TAG}"
docker push "${DOCKERHUB_NAMESPACE}/heatalert-nginx:${IMAGE_TAG}"
'''
                }
            }
        }
        stage('Deploy') {
            steps {
                sh '''#!/bin/bash
set -euo pipefail
chmod +x deploy/scripts/*.sh
deploy/scripts/deploy.sh
'''
            }
        }
        stage('Health Check') {
            steps { sh 'deploy/scripts/healthcheck.sh' }
        }
    }
    post {
        success { echo "SUCCESS: ${env.IMAGE_TAG}" }
        failure { echo "FAILURE: inspect the failing stage and preserve the existing database volumes" }
        always { sh 'docker image rm "heatalert-ci:${IMAGE_TAG}" >/dev/null 2>&1 || true' }
    }
}
```

Le `APP_KEY` de la commande de test est une valeur factice propre au conteneur CI. Un `.env` vide est créé uniquement au démarrage du conteneur éphémère; il n'est pas dans l'image et laisse `phpunit.xml` définir SQLite, l'email simulé et les autres paramètres de test. Les tests JavaScript sont exécutés sous Node 22. `npm ci` et `npm run build` sont exécutés dans la cible Docker `assets`, utilisée par `ci`, `app` et `nginx`.

Avant **Build Now**, préparer le fichier privé et les droits Docker selon [DEPLOYMENT.md](DEPLOYMENT.md). Vérifier le namespace Docker Hub et les dépôts. Le déploiement prend exactement le SHA testé. Un échec de test, de publication, de sauvegarde, de migration ou de santé donne `FAILURE`; ne pas relancer aveuglément une migration échouée sans analyser la base.

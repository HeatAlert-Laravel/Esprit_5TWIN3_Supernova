# Livraison — Coupures & Signalements

Branche : `feature/module2-coupure-Signalement`.
La branche distante a été récupérée ; elle était déjà à jour.
Aucun commit ni push effectué.

## Fonctionnalités

- Modèles partagés Coupure et Signalement, constantes de valeurs autorisées et relations Eloquent.
- Dates de coupures converties en datetime ; fin estimée et description facultatives.
- Deux migrations additives dans shared/database/migrations, après les migrations existantes.
- Quartier conservé par clé étrangère restrictive : supprimer ses coupures avant de supprimer le quartier.
- Suppression d’une coupure : signalements conservés, coupure_id devient null.
- Suppression d’un utilisateur : suppression de ses signalements.
- Deux CRUD complets sous /admin/coupures et /admin/signalements, protégés par auth + admin.
- Statut rapide d’un signalement via PATCH /admin/signalements/{signalement}/statut ; seul le statut est modifié.
- Composants et layouts HeatAlert existants, messages flash et erreurs de validation.
- Page publique /outages : coupures actives, filtre quartier, pagination, dates et nombre total de signalements associés.
- Page /outages/report : formulaire et enregistrement réservés aux utilisateurs connectés.
- Le formulaire habitant ne propose ni user_id ni statut ; ces valeurs sont fixées côté serveur.
- Form Requests : clés étrangères, valeurs autorisées, longueurs et cohérence chronologique.
- Factories avec signalements associés ou sans coupure ; seeders idempotents utilisant les quartiers et habitants existants.
- L’interface conserve l’anglais du projet, les valeurs métier demandées restent en français.
- DatabaseSeeder global, modèles Quartier et User, migrations existantes et layouts conservés.
- Adaptation nécessaire du contrôleur Quartier : message lisible plutôt qu’erreur SQL si des coupures empêchent sa suppression.

## Commandes et validation

PHP du PATH : 8.2.12. Utiliser l’exécutable installé C:\php83\php.exe (8.3.35).

Depuis backoffice, les commandes suivantes ont été exécutées avec succès :

```powershell
& C:\php83\php.exe artisan migrate:status
& C:\php83\php.exe artisan migrate
& C:\php83\php.exe artisan migrate:status
& C:\php83\php.exe artisan route:list --path=admin/coupures
& C:\php83\php.exe artisan route:list --path=admin/signalements
& C:\php83\php.exe artisan view:cache
npm run build
```

Seules les deux nouvelles migrations étaient en attente ; elles sont maintenant appliquées, batch 2.
Aucune commande de réinitialisation de base exécutée. Aucun seeder lancé sur la base réelle.
La compilation produit un avertissement de taille sur un bundle existant, sans échec.

Depuis frontoffice :

```powershell
& C:\php83\php.exe artisan migrate:status
& C:\php83\php.exe artisan route:list --path=outages
& C:\php83\php.exe artisan view:cache
```

Les deux applications reconnaissent les migrations appliquées.
Les vues sont compilées et leur rendu HTTP est couvert par les tests ; aucune revue visuelle dans un navigateur réalisée.

Tests isolés SQLite en mémoire, avec extensions chargées uniquement pour la commande :

```powershell
# Depuis backoffice
& C:\php83\php.exe -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/CoupureSignalementTest.php tests/Feature/QuartierFoundationTest.php tests/Feature/AlerteMeteoTest.php tests/Feature/ProfileCrudTest.php

# Depuis frontoffice
& C:\php83\php.exe -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/OutageTest.php tests/Feature/WeatherAlertTest.php tests/Feature/AuthenticationFlowTest.php
```

Les tests du module couvrent les CRUD, relations, suppressions, statuts, clés étrangères invalides, dates incohérentes,
droits admin, visiteurs non connectés, usurpation d’identité, champs sensibles vides,
filtre public et répétition des seeders. Les tests ne touchent pas la base heatalert.
Les nouveaux fichiers PHP ont été formatés avec Laravel Pint.

Résultat final : 20 tests Back Office réussis (193 assertions) et 11 tests Front Office réussis
(110 assertions), soit 31 tests et 303 assertions. Parmi eux, 9 tests concernent le nouveau module.

## Données de démonstration facultatives

Depuis backoffice, si des quartiers et des habitants existent :

```powershell
& C:\php83\php.exe artisan db:seed --class=CoupureSeeder
& C:\php83\php.exe artisan db:seed --class=SignalementSeeder
```

Pour le responsable du DatabaseSeeder, ajouter après les seeders Quartier et User :

```php
$this->call([
    CoupureSeeder::class,
    SignalementSeeder::class,
]);
```

## Fichiers

Créés :

- shared/app/Models/Coupure.php
- shared/app/Models/Signalement.php
- shared/database/migrations/2026_10_06_000001_create_coupures_table.php
- shared/database/migrations/2026_10_06_000002_create_signalements_table.php
- shared/database/factories/CoupureFactory.php
- shared/database/factories/SignalementFactory.php
- shared/database/seeders/CoupureSeeder.php
- shared/database/seeders/SignalementSeeder.php
- backoffice/app/Http/Controllers/Admin/CoupureController.php
- backoffice/app/Http/Controllers/Admin/SignalementController.php
- backoffice/app/Http/Requests/CoupureRequest.php
- backoffice/app/Http/Requests/SignalementRequest.php
- backoffice/app/Http/Requests/SignalementStatutRequest.php
- backoffice/lang/en.json
- backoffice/resources/views/pages/admin/coupures/_form.blade.php
- backoffice/resources/views/pages/admin/coupures/create.blade.php
- backoffice/resources/views/pages/admin/coupures/edit.blade.php
- backoffice/resources/views/pages/admin/coupures/index.blade.php
- backoffice/resources/views/pages/admin/coupures/show.blade.php
- backoffice/resources/views/pages/admin/signalements/_form.blade.php
- backoffice/resources/views/pages/admin/signalements/create.blade.php
- backoffice/resources/views/pages/admin/signalements/edit.blade.php
- backoffice/resources/views/pages/admin/signalements/index.blade.php
- backoffice/resources/views/pages/admin/signalements/show.blade.php
- backoffice/tests/Feature/CoupureSignalementTest.php
- frontoffice/app/Http/Controllers/OutageController.php
- frontoffice/app/Http/Requests/SignalementRequest.php
- frontoffice/lang/en.json
- frontoffice/resources/views/pages/front/outages.blade.php
- frontoffice/resources/views/pages/front/report-outage.blade.php
- frontoffice/tests/Feature/OutageTest.php
- docs/module2-coupures-signalements.md

Modifiés :

- backoffice/app/Http/Controllers/Admin/QuartierController.php
- backoffice/resources/views/partials/admin-sidebar.blade.php
- backoffice/routes/web.php
- frontoffice/routes/web.php

Modification préexistante conservée : backoffice/package-lock.json (à exclure des commits du module).
Les assets compilés et caches sont ignorés par Git.

## Commits proposés, à effectuer seulement après accord

1. Ajout modèles et migrations Coupure Signalement
   Inclure shared/app/Models, les deux migrations, les deux factories et seeders.
2. Ajout CRUD backoffice Coupures Signalements
   Inclure les nouveaux contrôleurs, requests, vues, traductions, routes, navigation,
   protection de suppression Quartier et tests Back Office.
3. Ajout pages frontoffice coupures et signalements
   Inclure contrôleur, request, vues, traductions, routes, tests Front Office et ce document.

Ne pas ajouter backoffice/package-lock.json aux commits du module sans revue de sa modification préexistante.

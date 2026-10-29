# Module M2 — Recherche et filtres simplifiés

## Pages

- ADMIN : `/admin/coupures` et `/admin/signalements`.
- USER / public : `/outages`, avec les cartes existantes et uniquement les coupures actives, conformément à la visibilité précédente.
- Aucune page de consultation des signalements personnels n'existe dans le projet ; aucune nouvelle page n'a été créée. Le formulaire de signalement existant reste disponible aux utilisateurs authentifiés et vérifiés.

## Fonctionnalités conservées

Une barre de recherche, un statut, un tri récent/ancien, un bouton Appliquer et un bouton Réinitialiser. Les contrôles utilisent le composant responsive `ha-filter` et le debounce existant. Les requêtes sont exécutées côté serveur.

Coupures : recherche dans la description et le nom/ville du quartier. Signalements : recherche dans la description et l'adresse, sans recherche sur les identités des résidents.

Pagination fixe de 10 résultats, avec total, Précédent/Suivant et conservation des paramètres. Les formulaires repartent de la première page. Une page hors limites affiche la dernière page disponible.

Le tri administrateur utilise `created_at`, le tri public utilise `date_debut` comme auparavant. Les deux utilisent `id` en second critère. Seules les valeurs `recent` et `oldest` sont acceptées ; aucune colonne ne provient de la requête.

Les statuts réels restent `active`, `résolue` pour les coupures et `en attente`, `validé`, `rejeté` pour les signalements. Le filtre public ne peut jamais rendre une coupure résolue visible : sa sélection donne une liste vide, et l'interface rappelle que seules les coupures actives sont visibles.

## Suppressions

Le dashboard M2, sa route, ses liens, ses KPI, ses graphiques, ses filtres de période et ses scripts ont été retirés. Le dashboard principal et ApexCharts, utilisé ailleurs, sont conservés.

Fichiers supprimés de l'intervention précédente :

- `app/Http/Controllers/M2DashboardController.php`
- `app/Http/Requests/M2DashboardRequest.php`
- `resources/js/m2.js`
- `resources/views/components/m2-sort.blade.php`
- `resources/views/coupures/dashboard.blade.php`
- `tests/Feature/M2AdvancedTest.php` (seul fichier de tests précédemment ajouté).

Les filtres de date, quartier, coupure associée, choix de taille de page et tri par colonne ont été retirés.

## Fichiers modifiés

- `app/Http/Controllers/CoupureController.php`
- `app/Http/Controllers/SignalementController.php`
- `app/Http/Controllers/OutageController.php`
- `app/Http/Requests/M2IndexRequest.php`
- `app/Support/M2Listing.php`
- `resources/views/coupures/_filters.blade.php`
- `resources/views/coupures/index.blade.php`
- `resources/views/coupures/resident-index.blade.php`
- `resources/views/signalements/index.blade.php`
- `resources/js/admin.js` (suppression du chargement M2).
- `resources/views/partials/admin-sidebar.blade.php` (suppression du lien M2).
- `routes/web.php` (suppression de la route M2).
- `docs/M2-advanced.md`.

Aucune migration, nouvelle dépendance ou modification de données MySQL. Aucun nouveau fichier de tests.

## Lancer

```powershell
npm run build
php artisan serve
```

Pour le développement des assets : `npm run dev` dans un second terminal.

## Compatibilité des anciens tests

Le test préexistant `public outages show only active records with neighborhood filtering and report counts` attend encore un filtre `quartier_id`. Ce filtre a été retiré à la demande de simplification ; ce scénario n'est donc plus compatible avec l'interface demandée. Le fichier de tests préexistant est conservé sans modification.

## Vérifications exécutées après simplification

- Contrôles directs des routes sur MySQL, en lecture seule : recherches combinées aux statuts côté ADMIN et public/USER, ordres récent et ancien, taille fixe de 10, conservation des paramètres de pagination, recherche vide, tri invalide refusé et accès ADMIN refusé au USER.
- Le filtre public « résolue » renvoie zéro résultat ; aucune coupure résolue n'est divulguée.
- `/admin/m2/dashboard` renvoie 404 ; `/admin` répond toujours 200.
- Le seul nouveau test M2 a été supprimé. Aucun test préexistant n'a été modifié.
- Les six tests préexistants `CoupureSignalementTest.php` passent : 69 assertions, base SQLite isolée, aucun changement MySQL.
- Build Vite réussi. Le contrôle visuel interactif dans un navigateur n'a pas été effectué.

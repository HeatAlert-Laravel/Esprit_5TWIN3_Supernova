# Résumé intelligent des signalements

## Utilisation

1. Se connecter avec un compte ADMIN vérifié.
2. Ouvrir **Outages → View** sur une coupure contenant des signalements.
3. Cliquer sur **Résumer les signalements avec l'IA**.
4. La synthèse, les problèmes et les points d'attention s'affichent sans recharger la page. **Régénérer le résumé** relance une génération.

Sans signalement, la fiche affiche un message et ne propose pas d'appel IA. Aucun appel n'est effectué à l'ouverture de la page. Les résumés ne sont pas enregistrés en base et aucun statut n'est modifié.

## Configuration

Le service réutilise exclusivement les variables existantes :

- Gemini : `GOOGLE_API_KEY`, `GOOGLE_EXPLAIN_MODEL`.
- OpenRouter : `OPENROUTER_API_KEY`, `OPENROUTER_MODEL`.

Aucune nouvelle variable, valeur de clé, dépendance ou migration. Les configurations et services Gemini/Groq des autres fonctionnalités restent inchangés. Seule une entrée `services.openrouter` est ajoutée pour lire les variables existantes.

## Fonctionnement

Route POST `admin/coupures/{coupure}/summary`, nom `admin.coupures.summary`. Elle reste dans le groupe `auth`, `admin`, `verified`, conserve la protection CSRF et limite les demandes à six par minute. Un verrou par coupure empêche les générations simultanées.

Gemini est appelé en premier. Une erreur HTTP, notamment 429/500/502/503/504, une déconnexion, un timeout ou un contenu inexploitable déclenche OpenRouter. Une tentative Gemini (12 secondes maximum), puis au maximum deux tentatives OpenRouter (15 secondes chacune) en cas d'échec temporaire ou de réponse invalide. Les erreurs permanentes, notamment une authentification refusée, ne sont pas réessayées. La connexion est limitée à 4 secondes. Si aucun résultat valide n'est disponible, l'utilisateur reçoit un message d'indisponibilité sans détail technique ni clé.

OpenRouter reçoit un schéma JSON strict et une demande de désactivation du raisonnement optionnel. Les réponses vides, incomplètes ou non JSON sont diagnostiquées dans le journal sans contenu utilisateur ni clé. Vérification de la correction : une erreur Gemini 503 simulée a déclenché OpenRouter réel, qui a produit un résumé valide sur un exemple fictif. La seconde tentative après échec temporaire est également vérifiée avec des réponses simulées.

Laravel calcule le total exact. L'IA renvoie des textes et des références anonymes aux descriptions ; le serveur vérifie ces références et calcule le nombre de signalements cités pour chaque point. Ce dernier est un compte des références retenues par l'IA, pas un recensement exhaustif des mentions. Les nombres sont exclus des textes générés et ajoutés par Laravel. Les résultats restent des déclarations à vérifier par l'administrateur.

Le quartier affiché vient de la relation Eloquent, pas d'une localisation inventée par l'IA. Les descriptions sont traitées comme des données, jamais comme des instructions. Les réponses sont affichées avec `textContent`, sans insertion de HTML généré.

## Données et limites

Le service récupère uniquement les signalements de la coupure sélectionnée. Il retire les noms connus (et leurs composants), les emails, les adresses exactes enregistrées, les contacts téléphoniques et les URL détectables. Seules les descriptions nettoyées et des références temporaires sont envoyées. Les identifiants de base, noms, champs d'adresse, emails et informations utilisateur ne sont pas ajoutés au payload.

Le nettoyage automatique ne garantit pas la détection de toute donnée personnelle saisie librement. Les limites de génération sont les 100 signalements les plus récents et 1 500 caractères nettoyés par description. Si une partie des signalements est exclue, l'interface affiche le périmètre analysé avec le total complet. Le fournisseur gratuit OpenRouter peut être indisponible ou produire une réponse refusée par la validation ; aucun faux succès n'est alors affiché.

## Fichiers

Créés :

- `app/Services/OutageReportSummary.php`
- `app/Http/Controllers/OutageReportSummaryController.php`
- `resources/js/outage-report-summary.js`
- `docs/M2-report-summary.md`

Modifiés :

- `config/services.php`
- `routes/web.php`
- `resources/js/admin.js`
- `resources/views/coupures/show.blade.php`

## Vérifications effectuées

Sans création de fichier de tests : exécution ponctuelle de vérifications avec SQLite en mémoire et des réponses HTTP simulées. Vérifiés : Gemini principal, fallback pour les cinq codes d'erreur demandés et timeout, contenu incorrect, échec des deux fournisseurs, absence de signalements sans appel, nettoyage des données personnelles connues, total exact, refus des nombres inventés et références étrangères, accès ADMIN autorisé et USER/visiteur refusé, fiche rendue sans génération automatique.

Appels réels aux deux fournisseurs avec uniquement des descriptions fictives sans donnée personnelle : réponse structurée valide obtenue de Gemini et d'OpenRouter. Aucune description réelle envoyée pendant ces vérifications.

Les six tests CRUD préexistants passent (69 assertions, SQLite isolée). Build Vite réussi. Aucun fichier de tests ni donnée MySQL modifié. Le rendu interactif n'a pas été vérifié visuellement dans un navigateur connecté.

Pour recompiler après modification du JavaScript : `npm run build`. Pour lancer Laravel : `php artisan serve`.

Références API consultées : [sorties structurées Gemini](https://ai.google.dev/gemini-api/docs/structured-output) et [API OpenRouter](https://openrouter.ai/docs/api_reference/overview).

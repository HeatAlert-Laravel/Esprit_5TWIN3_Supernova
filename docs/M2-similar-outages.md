# Coupures similaires — espace USER

## Accès et utilisation

1. Se connecter avec un compte `USER` dont l'email est vérifié.
2. Ouvrir `/outages`, puis **Report an outage**, ou directement `/outages/report`.
3. Remplir les champs d'adresse et de description existants.
4. Dans **Une coupure existe peut-être déjà**, choisir le quartier de recherche puis cliquer sur **Rechercher avec l'IA une coupure similaire**.
5. Consulter les cartes et leurs justifications. **Sélectionner cette coupure** renseigne uniquement le champ existant **Associated outage**.
6. Vérifier le formulaire, puis cliquer sur son bouton habituel d'envoi. La recherche et la sélection ne créent aucun signalement.

Le quartier de recherche est facultatif pour l'envoi classique et n'est pas enregistré en base. Sans JavaScript, le formulaire et son champ de coupure existants restent utilisables.

La nouvelle aide est absente pour les ADMIN. La route d'analyse refuse également les ADMIN, visiteurs et comptes non vérifiés. Aucun accès n'est ajouté aux signalements privés des autres résidents.

## Recherche et IA

Laravel sélectionne au maximum les 12 coupures actives les plus récentes du quartier choisi dont la date de début est passée. Les coupures résolues, supprimées, futures ou d'autres quartiers sont exclues des candidats.

Les seules données analysées sont la description nettoyée saisie, le quartier et la ville, puis les descriptions nettoyées, dates de début et identifiants des coupures candidates. Aucun autre signalement n'est interrogé. Noms et composants de noms connus, emails, téléphones, URL et adresses personnelles connues ou détectables sont retirés. Chaque description envoyée est limitée à 1 500 caractères. Les coordonnées du profil, lorsqu'elles existent, servent uniquement au nettoyage local.

Gemini est utilisé en premier avec `GOOGLE_API_KEY` et `GOOGLE_EXPLAIN_MODEL`. En cas d'erreur, notamment 429/500/502/503/504, timeout ou réponse inexploitable, OpenRouter est essayé avec `OPENROUTER_API_KEY` et `OPENROUTER_MODEL`. Aucun changement aux variables, modèles ou quotas existants. Les services IA des autres fonctionnalités, notamment le résumé ADMIN, ne sont pas modifiés.

Les fournisseurs doivent retourner un JSON `matches` contenant au maximum trois identifiants candidats et des justifications en français. Les identifiants, types, doublons, tailles et textes sont contrôlés par Laravel. Une réponse proposant un identifiant inconnu est rejetée. L'absence de correspondance renvoyée par l'IA est respectée : elle n'est pas remplacée par des suggestions locales.

Si les deux fournisseurs échouent, trois coupures actives du quartier sont proposées au maximum. L'interface indique explicitement qu'il s'agit d'une recherche locale, sans analyse IA. Le formulaire reste disponible dans tous les cas.

## Sécurité et maîtrise des appels

- Route POST `/outages/report/similar`, nom `outages.report.similar`.
- Middlewares `auth`, `verified`, protection CSRF, rôle `USER` dans la Form Request et limite de quatre demandes par minute.
- Description requise de 20 à 10 000 caractères et quartier existant obligatoire pour l'analyse uniquement.
- Un verrou par utilisateur empêche les analyses simultanées. Les réponses sont réutilisées pendant deux minutes pour une saisie et des candidats identiques ; le secours local est conservé trente secondes.
- Délai de connexion de quatre secondes, requête Gemini de douze secondes, puis OpenRouter de quinze secondes au maximum. Le navigateur interrompt l'attente après trente-cinq secondes.
- Aucun champ d'identité ou de statut fourni par le résident ne devient une donnée de création autorisée.
- La visibilité est revérifiée après l'analyse et le cache. À l'envoi, la coupure doit encore exister et être active ; une vérification transactionnelle avec verrou empêche une association périmée.
- Les textes sont affichés avec `textContent`, jamais comme du HTML généré.

Si la description, l'adresse ou le quartier change, les anciennes cartes sont masquées et une association issue des suggestions est effacée. Une sélection manuelle dans le champ existant reste sous le contrôle de l'utilisateur. Une réponse arrivée après une modification du formulaire est ignorée.

## Fichiers créés

- `app/Http/Requests/SimilarOutageRequest.php`
- `app/Http/Controllers/SimilarOutageController.php`
- `app/Services/SimilarOutages.php`
- `resources/views/signalements/_similar-outages.blade.php`
- `resources/js/similar-outages.js`
- `resources/css/similar-outages.css`
- `docs/M2-similar-outages.md`

## Fichiers modifiés

- `routes/web.php` : nouvelle route USER.
- `app/Http/Controllers/OutageController.php` : options de quartier du formulaire et vérification transactionnelle lors de l'enregistrement.
- `app/Http/Requests/ResidentSignalementRequest.php` : refus d'une coupure qui n'est plus active.
- `resources/views/signalements/resident-create.blade.php` : ajout conditionnel de l'aide USER.
- `resources/js/app.js` : chargement du script sur ce formulaire uniquement.
- `resources/css/app.css` : styles limités au composant de suggestions.

Aucune migration, nouvelle dépendance, modification de clé, création de fichier de tests ou modification du résumé ADMIN, de la recherche et de la pagination existantes.

## Vérifications effectuées

Exécutions ponctuelles en SQLite mémoire et réponses API simulées, sans fichier de tests : succès Gemini, plusieurs candidats dans le quartier, tous les statuts de fallback demandés et timeout, secours local, absence de correspondance, absence de candidats sans appel API, contrôle des identifiants inventés, nettoyage des données connues, cache, absence de création pendant la recherche, visibilité revérifiée après une résolution pendant l'analyse, accès USER uniquement, validation, enregistrement avec et sans coupure, et refus d'une association devenue résolue. Quatre demandes acceptées puis cinquième refusée avec 429.

Les huit tests préexistants ciblés passent (89 assertions) : `CoupureSignalementTest.php` et les deux scénarios de soumission et d'autorisation d'`OutageTest.php`. Build Vite, syntaxe PHP/JavaScript et `git diff --check` validés. Aucun test préexistant n'est modifié.

Un appel réel Gemini sur des données fictives a rencontré un quota dépassé (429). OpenRouter a ensuite fourni une réponse structurée valide et une correspondance sur des descriptions et une localisation entièrement fictives. La chaîne complète du service a aussi été exécutée avec Gemini 429 simulé et OpenRouter réel : une suggestion valide a été reçue en mode IA. Un appel sans réponse exploitable a correctement produit le mode local. Aucun signalement réel n'a été envoyé pendant ces vérifications.

## Scénarios à essayer dans le navigateur

- Choisir un quartier comportant une coupure active et décrire une panne compatible ; sélectionner une carte, puis vérifier le champ de coupure.
- Modifier ensuite la description ou le quartier : les suggestions et leur association deviennent caduques.
- Ignorer les cartes et envoyer normalement avec ou sans coupure.
- Sélectionner un quartier sans coupure : message d'absence de résultat.
- Entrer une description trop courte : précision demandée avant appel.
- Ouvrir le même formulaire avec un ADMIN : aucune nouvelle aide IA.

Le rendu interactif USER n'a pas été contrôlé visuellement dans une session navigateur connectée ; il reste à vérifier sur le poste. La sélection et l'invalidation côté JavaScript ont été relues et leur syntaxe vérifiée.

## Limites

La recherche est limitée aux douze coupures actives les plus récentes du quartier, déjà commencées ; elle n'analyse pas les coupures voisines ni les signalements privés. Le nettoyage automatique ne garantit pas la détection de tout détail personnel saisi en texte libre. Une suggestion reste une correspondance possible à vérifier. L'indisponibilité ou les quotas des fournisseurs peuvent déclencher le mode local.

Recompiler les assets après modification : `npm run build`. Lancer Laravel : `php artisan serve`.

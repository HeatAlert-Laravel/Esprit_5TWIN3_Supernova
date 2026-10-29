# Évaluation ciblée des dépendances — 15 octobre 2026

Cette passe conserve Laravel 12, PHP 8.3 et les contraintes des dépendances directes. Les mises à jour ont été simulées avant installation. Aucun contournement des audits, aucune exclusion d’advisory et aucun `audit fix --force` ajoutés.

## Résultats

- Composer : 48 entrées d’avis sur 14 paquets avant, un avis sur PHPUnit après. `composer audit --no-dev` : aucun avis.
- npm : 13 paquets signalés avant (3 critiques, 8 élevés, 1 modéré, 1 faible), Quill seul après (faible). Aucun avis critique/élevé restant dans les deux écosystèmes de production.
- 174 tests / 1 616 assertions réussis ; build Vite et inventaire des routes réussis.
- Base MySQL existante `heatalert`, MariaDB 10.4.32 : 19 migrations exécutées, 16 tables, 12 clés étrangères sans orphelin. GET publics et redirections testés, aucun 500 ni erreur SQL.
- Les schémas et comptages des tables métier sont identiques avant/après ; seules les sessions peuvent évoluer lors des GET normaux. Aucune migration ni écriture métier exécutée sur la base réelle.

## Classification Composer

A : runtime production directement concerné. B : dépendance indirecte. C : développement seulement. D : chemin vulnérable non utilisé dans le fonctionnement inspecté, sous les conditions précisées. E : mise à jour patch/minor compatible appliquée. F : résolution rejetée car elle impose un changement majeur transitif.

| Paquet | Avant | Après | Classe et portée |
|---|---|---|---|
| guzzlehttp/guzzle | 7.10.0 | 7.15.5 | B/E — Client HTTP indirect ; aucun appel Guzzle/Http explicite dans les contrôleurs. Correctifs conservateurs des cookies, proxys et URI. |
| guzzlehttp/psr7 | 2.8.0 | 2.13.1 | B/E — URI/messages du client HTTP indirect ; validation et sérialisation corrigées. |
| laravel/framework | v12.26.4 | v12.70.0 | A/E — Framework HTTP et règles de validation en production. Les avis debug/signed URL ont des préconditions distinctes ; tous corrigés. |
| league/commonmark | 2.7.1 | 2.10.3 | B/D/E — Dépendance du rendu Markdown Laravel. Les articles métier utilisent AdviceDocument, pas Markdown non fiable ; mise à jour quand même. |
| league/flysystem | 3.30.0 | 3.36.0 | B/E — Abstraction de stockage transitive ; normalisation des chemins corrigée. |
| phpunit/phpunit | 12.3.7 | 12.3.7 | C/D/F — Outil de tests uniquement ; scénario PHPT avec couverture absent du projet. Correctif exige tokenizer 2 : différé selon la limite de cette passe. |
| psy/psysh | v0.12.10 | v0.12.24 | B/E — Console de développement via Tinker, présente dans les dépendances installables en production ; chargement de configuration CWD corrigé. |
| symfony/http-foundation | v7.3.3 | v7.4.21 | A/E — Requêtes HTTP et PATH_INFO exposés ; correctifs appliqués, y compris helpers de réseaux privés non appelés explicitement ici. |
| symfony/mailer | v7.3.3 | v7.4.19 | B/E — Transport de courrier Laravel ; correctifs appliqués même si le mailer local est log. |
| symfony/mime | v7.3.2 | v7.4.19 | B/E — Construction des messages du framework ; validation des adresses et paramètres corrigée. |
| symfony/polyfill-intl-idn | v1.33.0 | v1.43.0 | B/E — Normalisation internationale transitive ; correction appliquée. |
| symfony/process | v7.3.3 | v7.4.21 | B/E — Gestion de sous-processus transitive ; échappement Windows corrigé. |
| symfony/routing | v7.3.2 | v7.4.21 | A/E — Génération des URL du framework ; corrections des segments et exigences appliquées. |
| symfony/yaml | v7.3.3 | v7.4.21 | C/E — Paquet require-dev transitif ; parseur corrigé sans modification de sa version majeure. |

Deux polyfills PHP et guzzlehttp/promises ont également évolué dans la même majeure, comme dépendances nécessaires des paquets ciblés. Aucun paquet Composer n’a changé de version majeure. Les 16 changements de version du lockfile sont des patchs/minors compatibles avec PHP 8.3.33 ; `composer check-platform-reqs` réussit.

## Chaque entrée d’advisory Composer

Les doublons provenant de sources distinctes sont conservés pour réconcilier exactement les 48 entrées du rapport brut. La portée détaillée figure dans la table précédente.

| Advisory | Paquet | Sévérité | Classe | Décision |
|---|---|---|---|---|
| [PKSA-gcrk-3vtt-1r14: Guzzle: Noncanonical host can bypass host-based checks](https://github.com/advisories/GHSA-v5mv-p594-2x33) | guzzlehttp/guzzle | high | B/E | Corrigé par 7.15.5 |
| [PKSA-cnw1-2ytm-cgr8: Guzzle: Noncanonical cookie domain keeps subdomain scope](https://github.com/advisories/GHSA-f7vp-7xgx-4w4r) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-fy2t-3c5f-827y: Guzzle: URI fragments disclosed in redirect Referer headers](https://github.com/advisories/GHSA-h95v-h523-3mw8) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-qxvb-2bpp-dnk6: Guzzle: Host-only cookie scope is not preserved](https://github.com/advisories/GHSA-wm3w-8rrp-j577) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-bbs6-q5q9-f3t4: Guzzle: Unbounded response cookies risk denial of service](https://github.com/advisories/GHSA-f283-ghqc-fg79) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-bcdd-5xc7-gwfb: Guzzle: Cookie Disclosure and Injection via IP-Address Domains](https://github.com/advisories/GHSA-g446-98w2-8p5w) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-pwsk-hy21-4gby: Guzzle: Proxy-Authorization headers can be sent to origin servers](https://github.com/advisories/GHSA-94pj-82f3-465w) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-93qv-9n9h-6k6p: Dot-only cookie domains match all hosts](https://github.com/guzzle/guzzle/security/advisories/GHSA-cwxw-98qj-8qjx) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-k22t-f949-t9g6: Silent HTTPS proxy downgrade to cleartext](https://github.com/guzzle/guzzle/security/advisories/GHSA-wpwq-4j6v-78m3) | guzzlehttp/guzzle | medium | B/E | Corrigé par 7.15.5 |
| [PKSA-vznr-tgp9-fd7d: guzzlehttp/psr7: Host Confusion via Weak URI Host Validation](https://github.com/advisories/GHSA-c2w2-prh8-qm98) | guzzlehttp/psr7 | medium | B/E | Corrigé par 2.13.1 |
| [PKSA-7qs6-zvnz-h66r: CRLF injection in HTTP start-line serialization](https://github.com/guzzle/psr7/security/advisories/GHSA-vm85-hxw5-5432) | guzzlehttp/psr7 | medium | B/E | Corrigé par 2.13.1 |
| [PKSA-gm5x-j3mz-71n9: CRLF injection via URI host component](https://github.com/guzzle/psr7/security/advisories/GHSA-hq7v-mx3g-29hw) | guzzlehttp/psr7 | medium | B/E | Corrigé par 2.13.1 |
| [PKSA-jj5t-2zs1-dcfm: Host confusion via authority reinterpretation](https://github.com/guzzle/psr7/security/advisories/GHSA-34xg-wgjx-8xph) | guzzlehttp/psr7 | medium | B/E | Corrigé par 2.13.1 |
| [PKSA-d5tc-s1qs-h781: Laravel: XSS in Debug Page Information](https://github.com/advisories/GHSA-jh5r-qr3c-85q8) | laravel/framework | low | A/E | Corrigé par v12.70.0 |
| [PKSA-m5cs-t1y6-qpcs: Laravel Framework: Temporary Signed URL Path Confusion](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) | laravel/framework | medium | A/E | Corrigé par v12.70.0 |
| [PKSA-3r5d-mb8f-1qw9: Laravel Framework: CRLF injection in default email rule ](https://github.com/advisories/GHSA-5vg9-5847-vvmq) | laravel/framework | high | A/E | Corrigé par v12.70.0 |
| [PKSA-mdq4-51ck-6kdq: Laravel CRLF injection in default email rule](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq) | laravel/framework | non renseignée | A/E | Corrigé par v12.70.0 |
| [PKSA-m2dq-1fhr-29b1: league/commonmark: DisallowedRawHtml bypassed when a disallowed tag name ends the raw-HTML literal](https://github.com/advisories/GHSA-97jj-33gv-5xf9) | league/commonmark | medium | B/D/E | Corrigé par 2.10.3 |
| [PKSA-m4t9-vsgq-8khn: league/commonmark: Quadratic-time denial of service in the GitHub Flavored Markdown Table extension block-start scan](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-zyf5-hrxv-hrd7: league/commonmark: Denial of service via distinctly-named attributes in the Attributes extension](https://github.com/advisories/GHSA-8rr7-cvq3-gmfh) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-nv44-1b4d-6gjg: league/commonmark: Denial of service in the SmartPunct and Attributes extensions](https://github.com/advisories/GHSA-jjv6-8j6v-6j52) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-kr3s-894t-g5w2: league/commonmark XSS: `on*` event-handler filter in `AttributesExtension` bypassed with a U+000C form feed](https://github.com/advisories/GHSA-f8fg-pg57-v4j8) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-9q1p-3s19-bp1q: league/commonmark: Denial of service via crafted code fences, reference links, and emphasis delimiters](https://github.com/advisories/GHSA-j8pm-gj4c-rq4x) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-5mzr-szzf-z6cn: league/commonmark: Denial of service via deeply nested XML output](https://github.com/advisories/GHSA-mj63-m3rc-8ppr) | league/commonmark | medium | B/D/E | Corrigé par 2.10.3 |
| [PKSA-cqd6-fg4n-nxpf: league/commonmark: Denial of service via colliding heading slugs](https://github.com/advisories/GHSA-mh25-x5hq-wrqp) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-1q6p-sqkj-8mmj: league/commonmark:  Denial of service via duplicate footnote definitions](https://github.com/advisories/GHSA-jfm3-95jq-q3rf) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-mc58-w91n-f5gv: league/commonmark: Denial of service via adjacent inline attribute blocks](https://github.com/advisories/GHSA-g2gp-3wwq-f4ph) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-t21r-vtr5-3mdz: league/commonmark: Quadratic-time denial of service when parsing crafted Markdown](https://github.com/advisories/GHSA-2q4p-g7hv-5rgv) | league/commonmark | high | B/D/E | Corrigé par 2.10.3 |
| [PKSA-scnn-p8mm-jbft: league/commonmark: AttributesExtension href/src unsafe-link filter bypass via embedded control bytes](https://github.com/advisories/GHSA-29pj-957v-52mc) | league/commonmark | medium | B/D/E | Corrigé par 2.10.3 |
| [PKSA-21fb-n1x5-5nf7: league/commonmark has an embed extension allowed_domains bypass](https://github.com/advisories/GHSA-hh8v-hgvp-g3f5) | league/commonmark | medium | B/D/E | Corrigé par 2.10.3 |
| [PKSA-2cx9-ynrq-qdk3: CommonMark has DisallowedRawHtml extension bypass via whitespace in HTML tag names](https://github.com/advisories/GHSA-4v6x-c7xx-hw9f) | league/commonmark | medium | B/D/E | Corrigé par 2.10.3 |
| [PKSA-w9tt-7782-78jx: Flysystem: WhitespacePathNormalizer's control-character (CorruptedPathDetected) check is bypassed by malformed UTF-8 in the path, affecting every adapter](https://github.com/advisories/GHSA-cxf4-7mrp-vvpr) | league/flysystem | low | B/E | Corrigé par 3.36.0 |
| [PKSA-z3gr-8qht-p93v: Unsafe Deserialization in PHPT Code Coverage Handling](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-vvj3-c3rp-c85p) | phpunit/phpunit | high | C/D/F | Différé, risque de développement évalué ci-dessous |
| [PKSA-4s4z-t146-6123: PsySH has Local Privilege Escalation via CWD .psysh.php auto-load](https://github.com/advisories/GHSA-4486-gxhx-5mg7) | psy/psysh | medium | B/E | Corrigé par v0.12.24 |
| [PKSA-xmwy-rmx5-m7h4: CVE-2026-108082: ReDoS in HeaderUtils::split() Reachable Through the Cache-Control Request Header](https://symfony.com/cve-2026-108082) | symfony/http-foundation | non renseignée | A/E | Corrigé par v7.4.21 |
| [PKSA-y6py-qpv1-h52p: CVE-2026-48736: IpUtils::PRIVATE_SUBNETS Omits IPv6 Transition Forms (6to4, NAT64, Teredo, IPv4-compatible): SSRF Bypass in NoPrivateNetworkHttpClient](https://symfony.com/cve-2026-48736) | symfony/http-foundation | medium | A/E | Corrigé par v7.4.21 |
| [PKSA-365x-2zjk-pt47: CVE-2025-64500: Incorrect parsing of PATH_INFO can lead to limited authorization bypass](https://symfony.com/blog/cve-2025-64500-incorrect-parsing-of-path-info-can-lead-to-limited-authorization-bypass) | symfony/http-foundation | high | A/E | Corrigé par v7.4.21 |
| [PKSA-28rh-rzzn-djk4: CVE-2026-45068: Argument Injection in SendmailTransport via Dash-Prefixed Recipient Address](https://symfony.com/cve-2026-45068) | symfony/mailer | medium | B/E | Corrigé par v7.4.19 |
| [PKSA-wtxr-p26d-nn42: CVE-2026-45070: Email Header Injection via Non-Token Characters in Mime Parameter Names](https://symfony.com/cve-2026-45070) | symfony/mime | medium | B/E | Corrigé par v7.4.19 |
| [PKSA-2n2k-66v2-bwg3: CVE-2026-45067: Email Header / SMTP Command Injection via CRLF in Symfony\Component\Mime\Address](https://symfony.com/cve-2026-45067) | symfony/mime | high | B/E | Corrigé par v7.4.19 |
| [PKSA-dwsq-ppd2-mb1x: CVE-2026-46644: symfony/polyfill-intl-idn accepts xn-- labels whose Punycode payload decodes to ASCII-only: insecure equivalence](https://symfony.com/cve-2026-46644) | symfony/polyfill-intl-idn | low | B/E | Corrigé par v1.43.0 |
| [PKSA-rkkf-636k-qjb3: Symfony's incorrect argument escaping under MSYS2/Git Bash can lead to destructive file operations on Windows](https://github.com/advisories/GHSA-r39x-jcww-82v6) | symfony/process | medium | B/E | Corrigé par v7.4.21 |
| [PKSA-bf7t-jnpz-492k: CVE-2026-48784: UrlGenerator Dot-Segment Encoding Skips Every Other Chained `../` or `./` → Generated URL Collapses Off-Route Under RFC 3986 Normalization](https://symfony.com/cve-2026-48784) | symfony/routing | medium | A/E | Corrigé par v7.4.21 |
| [PKSA-yc7t-91v9-99xs: CVE-2026-45065: UrlGenerator Route-Requirement Bypass via Unanchored Regex Alternation → Off-Site //host URL Injection](https://symfony.com/cve-2026-45065) | symfony/routing | medium | A/E | Corrigé par v7.4.21 |
| [PKSA-xz3r-9gp4-4m1z: CVE-2026-109413: Unbounded Recursion in the YAML Inline Lexer Lets a Single Document Crash the Process](https://symfony.com/cve-2026-109413) | symfony/yaml | non renseignée | C/E | Corrigé par v7.4.21 |
| [PKSA-v5yj-8nmz-sk2q: CVE-2026-45304: YAML Parser Exponential Memory Allocation via Recursive Collection-Alias Expansion ("Billion Laughs")](https://symfony.com/cve-2026-45304) | symfony/yaml | low | C/E | Corrigé par v7.4.21 |
| [PKSA-ft77-7h5f-p3r6: CVE-2026-45305: YAML Parser ReDoS via Catastrophic Backtracking in Parser::cleanup() Regex](https://symfony.com/cve-2026-45305) | symfony/yaml | low | C/E | Corrigé par v7.4.21 |
| [PKSA-b14r-zh1d-vdrc: CVE-2026-45133: YAML Parser Stack Exhaustion via Unbounded Recursion in Nested Blocks, Sequences, and Mappings](https://symfony.com/cve-2026-45133) | symfony/yaml | low | C/E | Corrigé par v7.4.21 |

## Classification npm

Les versions directes restent dans les contraintes du package.json inchangé. La résolution cible les paquets signalés et leurs dépendances, sans mise à jour générale.

| Paquet signalé | Portée réelle | Décision |
|---|---|---|
| axios (high) | Runtime navigateur, importé par admin.js via bootstrap.js ; adaptateurs Node non utilisés dans le navigateur. | Correctif compatible 1.11.0 → 1.20.0 |
| concurrently (critical) | Outil de développement pour composer dev ; risque shell transitif. | Correctif compatible 9.2.1 → 9.2.5 |
| follow-redirects (moderate) | Indirect : adaptateur HTTP Node d’Axios ; pas de serveur Node applicatif dans le projet. | Correctif compatible 1.15.11 → 1.16.1 |
| form-data (high) | Indirect : adaptateur HTTP Node d’Axios ; pas de flux multipart Node applicatif. | Correctif compatible 4.0.4 → 4.0.6 |
| nanoid (high) | Indirect : outil de build PostCSS. | Correctif compatible 3.3.11 → 3.3.20 |
| picomatch (high) | Indirect : résolution de fichiers dans les outils de build/dev. | Correctif compatible 4.0.3 → 4.0.7 |
| postcss (high) | Indirect : transformation CSS au build. | Correctif compatible 8.5.6 → 8.5.30 |
| quill (low) | Runtime navigateur, éditeur réservé à ADMIN. Export HTML vulnérable non utilisé. | Différé : aucun correctif publié ; analyse ci-dessous |
| rollup (high) | Indirect : bundler de build Vite. | Correctif compatible 4.49.0 → 4.64.7 |
| shell-quote (critical) | Indirect : lancement des commandes de développement. | Correctif compatible 1.8.3 → 1.12.0 |
| source-map-js (high) | Indirect : traitement des source maps du build. | Correctif compatible 1.2.1 → 1.2.2 |
| tar (critical) | Indirect : installation de Tailwind Oxide ; extraction dans la chaîne de build. | Correctif compatible 7.4.3 → 7.5.22 |
| vite (high) | Serveur de développement et build ; pas de runtime Node requis pour les assets publiés. | Correctif compatible 7.1.3 → 7.3.7 |

Axios 1.20.0 impose sa dépendance interne proxy-from-env 2.1.0, remplaçant la version 1.1.0. Cette transition transitive est prescrite par la version mineure d’Axios ; ce projet n’importe pas proxy-from-env et utilise Axios dans le navigateur, où l’adaptateur proxy Node n’est pas utilisé. Aucune dépendance directe n’a changé de majeure et aucune API applicative n’a été migrée. Les changements esbuild 0.25 → 0.28 sont ceux de Vite 7.3, outil encapsulé par Vite ; aucun appel esbuild direct dans le projet.

Les entrées optionnelles de plateformes du lockfile suivent les versions des bundlers ; elles ne constituent pas de nouvelles fonctionnalités métier. Vite conserve la compatibilité Node 22 à partir de 22.12, et un composant optionnel Linux de compression demande 22.20. Seuls Node 24.21 et 25.4 sont installés sur cette machine : validation effectuée avec Node 24.21, sans installation ni changement global de Node. Une exécution sous Node 22 récent reste une limite de validation déclarée.

## Avis restant : PHPUnit — CVE-2026-24765

[Advisory du mainteneur](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-vvj3-c3rp-c85p).

PHPUnit 12.3.7 est une dépendance de développement, absente d’une installation `composer install --no-dev`. L’avis vise la désérialisation de fichiers de couverture préexistants pendant l’exécution de tests PHPT. Aucun fichier PHPT ou `.coverage` dans la suite du projet ; la commande validée est `php artisan test`, sans instrumentation de couverture PHPT. Cela réduit la portée dans l’usage actuel sans rendre PHPUnit intrinsèquement sûr dans d’autres pipelines.

Le correctif PHPUnit 12.5.8+ impose php-code-coverage 12.5.2+, qui impose theseer/tokenizer 2.0.1. La simulation avec maintien de tokenizer 1.x ne résout pas. Une autre simulation élargissait aussi Pest/Collision et leurs outils de tests. Cette migration transitive majeure n’est pas forcée dans cette passe limitée aux mises à jour compatibles : risque résiduel explicitement retenu pour l’usage de développement inspecté, sans ignorer l’advisory.

Cette justification doit être réévaluée avant tout ajout de tests PHPT, de couverture associée, ou réutilisation d’artefacts de couverture non fiables. La configuration effective des runners externes n’a pas été certifiée par cet audit local.

## Avis restant : Quill — CVE-2025-15056

[Advisory](https://github.com/advisories/GHSA-v3m3-f69x-jf25) : version 2.0.3 affectée, aucune version corrigée annoncée ; le registre npm confirme que 2.0.3 est la dernière version.

resources/js/advice-editor.js utilise getContents/getText et setContents/setText ; aucun getSemanticHTML, export HTML ou dangerouslyPasteHTML dans le code applicatif. Les articles sont transmis comme Delta JSON, validés par AdviceDocument puis rendus avec échappement serveur. Les formats sont limités au texte, gras, italique, titres et listes, sans embeds ni URL. Les tests AdviceEditor et les tests serveur existants passent.

Le chemin d’export HTML décrit par l’advisory n’est donc pas utilisé dans le flux inspecté. Risque faible résiduel retenu pour cet usage, sans masquer npm audit. Le repli proposé par npm vers 2.0.2 est une rétrogradation, pas un correctif publié par le mainteneur ; il n’est pas appliqué automatiquement. Toute activation future d’export HTML ou d’intégration HTML non fiable impose une nouvelle analyse.

## Validation et limites

Aucun fichier PHP applicatif modifié dans cette passe : Pint ciblé sans objet, aucun reformatage historique. Les modifications portent sur composer.lock, package-lock.json et ce document. Les tests vérifient notamment les rôles USER/ADMIN, ownership, profils, équipements/types, conseils, coupures/signalements, lieux frais et alertes. Les smoke tests MySQL restent des GET invités ; les scénarios de mutation et d’authentification utilisent SQLite isolée.

Après la mise à jour Laravel, route:list expose 113 routes au total, soit les 110 routes applicatives conservées et trois routes du framework. Le nouveau PUT storage/{path} vient de Laravel, pas d’un contrôleur métier ajouté ; sa protection doit rester celle du framework (URL signée, disque local configuré). Aucun doublon de nom de route.

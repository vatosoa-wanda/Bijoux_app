# 🗺️ TODO — Suite du projet PolyBijoux (après le sprint initial de 2 jours)

Méthode de travail inchangée : un maximum de règles métier en PostgreSQL (triggers, fonctions, vues, contraintes `CHECK`), Laravel pour la validation/orchestration/API, React pour l'affichage uniquement. Chaque sprint se termine par des tests PHPUnit sur les règles critiques.

Ce document reprend les points du cahier des charges et des règles de gestion non traités dans le sprint initial (Stocks, Production, Qualité), et les organise en sprints indépendants, réalisables dans l'ordre ou en parallèle selon les priorités.

---

## 0. État des lieux (déjà fait — pour référence)

| Fonctionnalité | Statut |
|---|---|
| Stocks matières premières + mouvements + alertes | ✅ Fait |
| Fiches bijoux + nomenclature + coût de revient | ✅ Fait |
| Ordres de fabrication + clôture + consommation auto | ✅ Fait |
| Contrôle qualité + défauts + statistiques | ✅ Fait |
| Dashboard KPI | ✅ Fait |
| Tables déjà migrées mais non exploitées côté métier | `collection`, `client`, `statut_commande`, `commande`, `ligne_commande`, `fournisseur`, `fournisseur_matiere` (à créer), `produit_fini` (table existe, pas de page de gestion des ventes) |

---

## 1. Sprint 4 — Produits finis & Ventes

**Objectif** : gérer le stock de bijoux terminés et leur mise en vente, ce qui manque actuellement (les produits finis sont créés automatiquement à la clôture d'un OF mais rien ne permet de les consulter/vendre depuis l'UI).

### Base de données
- [ ] Contrainte `CHECK` sur `produit_fini.prix_vente` : doit être renseigné avant de pouvoir passer le statut à `VENDU` (à faire via trigger, pas juste une contrainte simple, car conditionnel au statut).
- [ ] Trigger `fn_verifier_vente_produit()` : empêche le passage à `statut = 'VENDU'` si `prix_vente IS NULL` ou si `quantite_stock <= 0`.
- [ ] Trigger `fn_decrementer_stock_produit_fini()` : à chaque vente (voir Sprint 5, lié aux commandes), décrémente `produit_fini.quantite_stock`.
- [ ] Fonction `fn_calculer_prix_vente_suggere(id_bijou, marge)` : applique `cout_revient * marge` (récupère `marge_defaut` dans `parametre` si non fournie) — implémente la règle de gestion n°10 (`Prix = coût matières + main d'œuvre + marge`).
- [ ] Vue `vue_produits_disponibles` : produits avec `statut = 'EN_STOCK'` et `quantite_stock > 0`, jointure bijou/collection.

### Backend
- [ ] `ProduitFiniController` (index avec filtres statut/collection, update prix de vente, update statut).
- [ ] Route `GET /api/bijoux/{id}/prix-vente-suggere`.

### Frontend
- [ ] Page *Produits finis* : tableau des bijoux en stock (référence, collection, date fabrication, coût de revient, prix de vente, statut), édition du prix de vente, badge par statut (`EN_STOCK` / `RESERVE` / `VENDU`).

### Tests
- [ ] Vente refusée si `prix_vente` non renseigné.
- [ ] `fn_calculer_prix_vente_suggere` retourne la bonne valeur selon la marge par défaut.
- [ ] La vue `vue_produits_disponibles` exclut bien les produits `VENDU` ou à quantité nulle.

---

## 2. Sprint 5 — Clients & Commandes

**Objectif** : couvrir la section 8 du cahier des charges (gestion des clients et commandes) et les règles de gestion n°1, 2, 4, 11, 12, 13, 14.

### Base de données
- [ ] Migration `type_livraison` ou colonne calculée : implémenter la règle n°12 (commandes < 15 € → retrait boutique uniquement) via une contrainte logique côté fonction plutôt qu'un champ libre.
- [ ] Fonction `fn_verifier_disponibilite_commande(id_bijou, quantite)` : réutilise/étend `fn_verifier_disponibilite` de la Fonctionnalité 2 pour vérifier le stock de **produits finis** (pas seulement matières premières) — règle de gestion n°1.
- [ ] Trigger `fn_calculer_montant_commande()` : recalcule `commande.montant_total` à chaque insertion/suppression de `ligne_commande` (somme des `quantite * prix_unitaire`).
- [ ] Trigger `fn_calculer_delai_livraison()` : applique automatiquement +3 jours à `date_livraison_prevue` si la commande contient plus de 10 pièces identiques (règle n°14), et fixe le délai standard à J+5 ouvrés sinon (règle n°13). *Note : le calcul de jours ouvrés en SQL demande une fonction dédiée (`fn_ajouter_jours_ouvres`), à écrire avec une boucle excluant samedi/dimanche.*
- [ ] Contrainte métier : une commande personnalisée (`personnalisation IS NOT NULL` sur `ligne_commande`) ne peut passer au statut `EN_FABRICATION` que si `acompte_verse >= 30% * montant_total` (règle n°11) — trigger `fn_verifier_acompte()`.
- [ ] Vue `vue_commandes_en_retard` : commandes dont `date_livraison_prevue < CURRENT_DATE` et statut ≠ livré.
- [ ] Table `statut_commande` déjà migrée : seeder à créer (`EN_ATTENTE`, `VALIDEE`, `EN_FABRICATION`, `LIVREE`, `ANNULEE`).

### Backend
- [ ] `ClientController` (CRUD simple).
- [ ] `CommandeController` : création avec lignes de commande, changement de statut (déclenche vérification acompte), lien vers OF si la commande nécessite une fabrication.
- [ ] `LigneCommandeController` (ajout/suppression de lignes).
- [ ] FormRequest avec règle : couleur/personnalisation demandée doit correspondre à un stock disponible (règle n°2) — validation croisée avec `matiere_premiere.couleur` ou `bijou`.

### Frontend
- [ ] Page *Clients* : liste + fiche client avec historique de commandes.
- [ ] Page *Commandes* : création de commande (sélection client, bijoux, quantités, personnalisation), suivi de statut, affichage de l'acompte requis et versé, alerte si commande en retard (`vue_commandes_en_retard`).

### Tests
- [ ] Le montant total se recalcule automatiquement à l'ajout d'une ligne.
- [ ] Une commande > 10 pièces identiques a bien un délai allongé de 3 jours.
- [ ] Le passage en fabrication est refusé si l'acompte de 30 % n'est pas atteint.
- [ ] Une commande de moins de 15 € ne peut pas être marquée "livrable à domicile" (règle n°12, à formaliser selon l'implémentation choisie).

---

## 3. Sprint 6 — Fournisseurs & Achats

**Objectif** : couvrir la section 6 du cahier des charges (réapprovisionnement automatique, historique d'achats, prévision des besoins).

### Base de données
- [ ] Migration table `fournisseur_matiere` (présente dans le schéma SQL initial, pas encore migrée).
- [ ] Fonction `fn_suggestion_reapprovisionnement()` : retourne, pour chaque matière sous son seuil d'alerte, le fournisseur le moins cher (`prix_achat` le plus bas dans `fournisseur_matiere`) et son délai de livraison — implémente la règle "commande automatique selon les seuils".
- [ ] Vue `vue_besoins_matieres` : à partir des OF en attente/en cours, calcule les quantités de matières nécessaires non encore consommées (prévision des besoins évoquée dans le cahier des charges).

### Backend
- [ ] `FournisseurController` (CRUD).
- [ ] `FournisseurMatiereController` (liaison prix/délai par matière).
- [ ] Route `GET /api/reapprovisionnement/suggestions` (expose `fn_suggestion_reapprovisionnement`).
- [ ] Route `GET /api/matieres/besoins-previsionnels` (expose `vue_besoins_matieres`).

### Frontend
- [ ] Page *Fournisseurs* : CRUD fournisseurs + association matières/prix/délais.
- [ ] Widget dans le Dashboard ou la page Stock : "Suggestions de réapprovisionnement" listant matière, fournisseur conseillé, quantité à commander.

### Tests
- [ ] La fonction de suggestion retourne bien le fournisseur au prix le plus bas pour une matière sous seuil.
- [ ] La vue des besoins prévisionnels agrège correctement plusieurs OF en cours sur la même matière.

---

## 4. Sprint 7 — Collections & Personnalisation

**Objectif** : couvrir la section 4 du cahier des charges et la règle de gestion n°7 et n°9 (collections, exclusivité des modèles).

### Base de données
- [ ] Contrainte métier : une collection ne peut être publiée/activée que si elle contient au moins 3 modèles de chaque type de bijou (collier, bracelet, boucle d'oreille) — fonction `fn_verifier_completude_collection(id_collection)` appelée avant de permettre `collection.actif = TRUE`.
- [ ] Trigger ou contrainte `CHECK` (via fonction, car agrégation nécessaire) limitant à 10 le nombre d'exemplaires produits d'un même modèle (`SUM(quantite_realisee)` sur tous les OF liés à un `bijou`) — règle n°9. À vérifier à la création d'un nouvel OF (réutilise/étend `fn_verifier_disponibilite`).
- [ ] Vue `vue_collections_completude` : nombre de modèles par type pour chaque collection, flag "complète"/"incomplète".

### Backend
- [ ] `CollectionController` (CRUD, avec vérification de complétude avant activation).
- [ ] Étendre `OrdreFabricationController@store` : appel à la fonction de vérification du plafond de 10 exemplaires avant création.

### Frontend
- [ ] Page *Collections* : CRUD, association des bijoux existants à une collection, indicateur visuel de complétude.
- [ ] Sur la page *Fiches bijoux* : afficher le nombre d'exemplaires déjà produits / 10 restants.

### Tests
- [ ] Une collection avec seulement 2 bracelets et aucun collier ne peut pas être activée.
- [ ] Un 11ᵉ exemplaire d'un même modèle est refusé à la création de l'OF.

---

## 5. Sprint 8 — Capacité de production (simulation)

**Objectif** : implémenter l'écran dédié du cahier des charges (page 5), distinct de la simple vérification de disponibilité déjà faite en Fonctionnalité 2 — ici il s'agit d'une **simulation multi-bijoux** avec identification du composant bloquant.

### Base de données
- [ ] Fonction `fn_capacite_max(id_bijou)` : calcule, pour un bijou donné, le nombre maximal réalisable selon le stock actuel (`MIN` sur `stock_disponible / quantite_necessaire` pour chaque matière de la nomenclature) — retourne aussi la matière bloquante.
- [ ] Fonction `fn_simulation_production(json_quantites)` : accepte un tableau `{id_bijou, quantite}` et retourne, pour chaque matière impliquée, le stock restant après simulation, avec un flag de faisabilité globale.

### Backend
- [ ] Route `GET /api/bijoux/{id}/capacite-max`.
- [ ] Route `POST /api/production/simuler` (body : liste de bijoux + quantités souhaitées) → appelle `fn_simulation_production`.

### Frontend
- [ ] Page *Capacité de production* : sélection d'un ou plusieurs bijoux avec quantités souhaitées, affichage du détail matière par matière (stock, besoin, max possible), mise en évidence du composant bloquant, résultat "réalisable / non réalisable".

### Tests
- [ ] `fn_capacite_max` identifie correctement la matière bloquante sur un jeu de données à plusieurs composants.
- [ ] La simulation multi-bijoux détecte un conflit quand deux bijoux différents consomment la même matière limitante.

---

## 6. Sprint 9 — Paramètres (interface complète)

**Objectif** : les paramètres (`taux_horaire`, `charges_indirectes_forfait`, `marge_defaut`) existent déjà en base mais ne sont modifiables que via seeder/SQL direct. Il manque une vraie interface.

### Base de données
- [ ] Rien de nouveau — la table `parametre` existe déjà avec son trigger `updated_at`.
- [ ] Ajouter des paramètres manquants identifiés dans le cahier des charges : seuils de stock par défaut, unités de mesure par défaut (optionnel selon besoin réel).

### Backend
- [ ] `ParametreController` (index, update par clé — pas de create/delete pour éviter de casser les clés attendues par les fonctions SQL).
- [ ] Validation stricte : `valeur` doit être castable en nombre pour les paramètres numériques (whitelist des clés modifiables).

### Frontend
- [ ] Page *Paramètres* : formulaire simple listant chaque paramètre avec sa description, permettant la modification de la valeur uniquement.

### Tests
- [ ] Modifier `taux_horaire` change bien le résultat de `fn_cout_revient` sur un appel suivant (test d'intégration croisée entre Sprint 9 et Fonctionnalité 2 — bon test à mentionner en entretien : "les paramètres sont bien lus dynamiquement, pas mis en cache").

---

## 7. Sprint 10 — Authentification & utilisateurs

**Objectif** : Sanctum est installé mais non exploité. Ajout d'un vrai login, nécessaire pour un portfolio crédible même sans gestion de rôles complexe (cf. consignes initiales : rester simple).

### Base de données
- [ ] Table `users` (déjà générée par défaut par Laravel) — pas de migration custom nécessaire.

### Backend
- [ ] `AuthController` (login/logout via Sanctum, `POST /api/login`, `POST /api/logout`).
- [ ] Middleware `auth:sanctum` sur toutes les routes API existantes (à faire en dernier, une fois toutes les fonctionnalités testées, pour ne pas bloquer le développement en cours de route).
- [ ] Seeder d'un utilisateur admin par défaut pour la démo.

### Frontend
- [ ] Page *Login* simple (email/mot de passe).
- [ ] Stockage du token Sanctum, ajout automatique dans l'intercepteur axios (`Authorization: Bearer ...`).
- [ ] Redirection vers `/login` si 401 reçu (gérer dans l'intercepteur déjà en place).

### Tests
- [ ] Accès refusé (401) à une route protégée sans token.
- [ ] Login réussi retourne un token valide utilisable sur une route protégée.

---

## 8. Sprint 11 — Fonctions annexes (import/export, étiquettes)

**Objectif** : section 10 du cahier des charges — fonctions utilitaires, à faire en dernier car non structurantes.

- [ ] Export CSV des matières premières et des fiches bijoux (`League\Csv` ou génération manuelle Laravel, pas de logique métier particulière ici).
- [ ] Import CSV des matières premières (avec validation ligne par ligne).
- [ ] Génération d'étiquette produit fini (référence, collection, date) en PDF simple (librairie `dompdf` ou équivalent) — pas de logique métier, uniquement de la mise en page.
- [ ] Upload de photo pour les bijoux et les défauts constatés (déjà prévu dans le schéma via `photo_url`, manque juste le endpoint d'upload de fichier + stockage `storage/app/public`).

### Tests
- [ ] L'import CSV rejette une ligne avec une catégorie/unité inexistante plutôt que de planter tout l'import.

---

## 9. Sprint 12 — Déploiement

- [ ] Backend : déploiement sur Railway ou Render (PostgreSQL managé inclus).
- [ ] Frontend : déploiement sur Vercel ou Netlify, variable d'environnement `VITE_API_URL` pointant vers le backend déployé.
- [ ] Vérifier la config CORS en production (domaines autorisés, pas de wildcard `*` avec Sanctum).
- [ ] `APP_DEBUG=false` et `APP_ENV=production` en production.
- [ ] Vérifier que les migrations avec `DB::unprepared` (triggers/fonctions/vues) s'exécutent correctement sur l'hébergeur choisi (certains PaaS restreignent les permissions PostgreSQL — à tester tôt pour éviter une mauvaise surprise en fin de projet).

---

## 10. Sprint 13 — Polish final & documentation

- [ ] Repasser sur la cohérence visuelle de toutes les nouvelles pages (mêmes conventions de couleurs/badges/espacement que les 6 pages existantes).
- [ ] Compléter le `README.md` : nouveaux endpoints, nouvelles règles de gestion implémentées, mise à jour de la section Roadmap (retirer ce qui est fait).
- [ ] Ajouter les captures d'écran manquantes.
- [ ] Vérifier la couverture de tests globale (`php artisan test`) — viser au moins 1-2 tests par nouvelle règle métier mise en base.
- [ ] Relire l'ensemble des triggers/fonctions pour vérifier la cohérence des types (`BIGINT` pour tous les identifiants, leçon apprise du sprint initial).
- [ ] Préparer un jeu de données de démonstration réaliste (seeders enrichis) pour une présentation fluide en entretien/soutenance.

---

## 📌 Rappel des pièges déjà rencontrés à ne pas reproduire

- Toujours utiliser `BIGINT` (pas `INT`) pour les paramètres de fonctions PL/pgSQL représentant un identifiant Laravel.
- Toujours `CREATE OR REPLACE FUNCTION`/`VIEW`, jamais `CREATE` seul, pour rester idempotent en développement.
- Si la signature d'une fonction change, ajouter un `DROP FUNCTION IF EXISTS nom(ancienne_signature)` avant de la recréer.
- Utiliser `migrate:fresh --seed --drop-types --drop-views` systématiquement en développement tant que des types/vues custom existent.
- Les colonnes `DECIMAL`/`NUMERIC` renvoyées par une vue via `DB::table(...)` arrivent en `string` côté PHP — caster explicitement en `float` dans le contrôleur avant de renvoyer le JSON.

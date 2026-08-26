# 🏗️ Plan de projet — App de gestion de production/vente de bijoux artisanaux
### Stack : Laravel (API) + React (SPA) + PostgreSQL — Réalisation en 2 jours pleins

---

## 0. AVANT DE COMMENCER

### 0.1 Ce qu'il faut maîtriser (ou réviser vite) avant de démarrer

| Domaine | Notions indispensables |
|---|---|
| Laravel | Migrations, Eloquent (relations `hasMany`/`belongsTo`/`belongsToMany`), API Resources, Form Requests (validation), Sanctum (auth API), `DB::transaction()`, `DB::statement()`/`DB::unprepared()` pour du SQL brut |
| PostgreSQL | Types `ENUM`, `TRIGGER` + `FUNCTION plpgsql`, `VIEW`, contraintes `CHECK`, transactions |
| React | Hooks (`useState`, `useEffect`, `useContext`), React Router, appels API (axios/fetch), un state manager léger (Context API suffit, pas besoin de Redux) |
| Outils | Git/GitHub (commits réguliers = crédibilité portfolio), Postman/Insomnia ou Bruno pour tester l'API avant de coder le front |

### 0.2 À FAIRE ✅

- **Partir du schéma SQL fourni tel quel** : il est déjà normalisé (3FN), avec les bonnes clés étrangères et ENUM. Ne le refaites pas, traduisez-le en migrations Laravel.
- **Mettre le calcul et les règles d'intégrité dans PostgreSQL** (triggers, fonctions, vues) et **l'orchestration/la validation dans Laravel**. C'est ce qui fera la différence sur un CV ("logique métier en base via fonctions PL/pgSQL", "triggers d'intégrité transactionnelle").
- **Réduire le périmètre fonctionnel** à ce qui est réalisable *proprement* en 2 jours (voir 0.4) plutôt que de faire les 12 pages à moitié.
- **Committer souvent** (au moins toutes les 1-2h) avec des messages clairs — ça sert de preuve de démarche pour un recruteur qui regarde l'historique Git.
- **Écrire un `README.md`** dès le début (stack, install, capture d'écran) — vous le complèterez à la fin.
- **Tester chaque endpoint avec Postman avant de brancher le front.**
- **Utiliser le Design System fourni** (couleurs, Poppins) dès la mise en place du layout React, pas à la fin.
- **Écrire quelques tests automatisés** (PHPUnit Feature tests) sur les règles métier critiques — 5 à 8 tests bien choisis suffisent et valorisent énormément le projet.

### 0.3 À NE PAS FAIRE ❌

- Ne pas essayer d'implémenter les 12 pages du cahier des charges à 100 % — vous n'aurez pas le temps de le faire *bien*, et "peu mais fonctionnel" vaut mieux que "beaucoup mais buggé" en entretien.
- Ne pas faire une authentification multi-rôles complexe (gestion des permissions fine) — un login simple avec Sanctum suffit.
- Ne pas mettre la logique métier (calculs, contrôles de cohérence) dans React — le front ne doit qu'afficher/saisir/appeler l'API.
- Ne pas oublier la config CORS et le `.env` (ne jamais commit le `.env`, fournir un `.env.example`).
- Ne pas négliger les migrations liées aux `ENUM` PostgreSQL — Laravel ne les gère pas nativement, il faut passer par `DB::statement()`.
- Ne pas viser le pixel-perfect sur le design — un design cohérent et propre suffit, la maquette `dashboard.html` fournie est déjà un bon guide.
- Ne pas versionner `node_modules/` ni `vendor/`.

### 0.4 Périmètre retenu pour 2 jours (MVP réaliste)

**Pages à développer (6, au lieu des 12) :**
1. Dashboard (KPI + alertes)
2. Matières premières (CRUD + stock)
3. Stock & mouvements (historique + entrée/sortie)
4. Fiches bijoux (BOM / nomenclature)
5. Production (Ordres de fabrication)
6. Contrôle qualité + Statistiques (regroupées en une page avec 2 onglets, pour gagner du temps)

**Hors périmètre pour ce sprint** (à mentionner en "roadmap" dans le README pour montrer que vous avez pensé plus loin) : Commandes clients, Fournisseurs, Capacité de production en simulation multi-bijoux, Paramètres avancés, Export Excel/CSV, impression étiquettes.

---

## 1. LES 3 FONCTIONNALITÉS PHARES POUR LE CV

L'idée : choisir 3 fonctionnalités qui **démontrent des compétences différentes et complémentaires** (intégrité des données transactionnelles, logique métier calculée, reporting/agrégation) plutôt que 3 simples CRUD.

### 🔹 Fonctionnalité 1 — Gestion des stocks avec intégrité transactionnelle (triggers)

**Pourquoi c'est un bon point CV** : montre la maîtrise des triggers PostgreSQL et de la cohérence des données en temps réel, sans dépendre du code applicatif pour la fiabilité.

**Modèle** : `matiere_premiere`, `mouvement_stock`, `type_mouvement` (déjà dans votre schéma).

**Logique en base de données** :
```sql
-- Fonction déclenchée à chaque mouvement de stock
CREATE OR REPLACE FUNCTION fn_appliquer_mouvement_stock()
RETURNS TRIGGER AS $$
DECLARE
    v_sens sens_mouvement;
BEGIN
    SELECT sens INTO v_sens FROM type_mouvement WHERE id_type_mvt = NEW.id_type_mvt;

    IF v_sens = 'ENTREE' THEN
        UPDATE matiere_premiere
        SET quantite_stock = quantite_stock + NEW.quantite
        WHERE id_matiere = NEW.id_matiere;
    ELSE
        -- Empêche un stock négatif
        IF (SELECT quantite_stock FROM matiere_premiere WHERE id_matiere = NEW.id_matiere) < NEW.quantite THEN
            RAISE EXCEPTION 'Stock insuffisant pour la matière %', NEW.id_matiere;
        END IF;
        UPDATE matiere_premiere
        SET quantite_stock = quantite_stock - NEW.quantite
        WHERE id_matiere = NEW.id_matiere;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_mouvement_stock_apply
    AFTER INSERT ON mouvement_stock
    FOR EACH ROW EXECUTE FUNCTION fn_appliquer_mouvement_stock();

-- Vue pour les alertes de seuil (utilisée par le dashboard)
CREATE VIEW vue_stock_alertes AS
SELECT id_matiere, nom, quantite_stock, seuil_alerte
FROM matiere_premiere
WHERE quantite_stock <= seuil_alerte AND actif = TRUE;
```

**Côté Laravel** :
- `MatierePremiereController` : CRUD classique (validation via `FormRequest`).
- `MouvementStockController::store()` : insère juste la ligne dans `mouvement_stock`, laisse le trigger faire le calcul, capture l'exception PostgreSQL (`RAISE EXCEPTION`) si stock insuffisant → renvoie une erreur 422 propre.
- Endpoint `GET /api/matieres/alertes` → interroge `vue_stock_alertes`.

**Côté React** :
- Page *Matières premières* : tableau + formulaire d'ajout/édition.
- Page *Stock & mouvements* : historique (table paginée), formulaire entrée/sortie, badge OK/Alerte calculé côté back.
- Widget alertes dans le Dashboard.

**Tests à écrire** :
- Un mouvement `ENTREE` augmente bien `quantite_stock`.
- Un mouvement `SORTIE` supérieur au stock disponible est rejeté (exception catchée → 422).
- La vue `vue_stock_alertes` retourne bien les matières sous le seuil.

---

### 🔹 Fonctionnalité 2 — Production (Ordres de Fabrication) + calcul automatique du coût de revient

**Pourquoi c'est un bon point CV** : démontre la gestion d'un processus métier complet (nomenclature → fabrication → consommation → stock produit fini) avec transactions, et un calcul métier paramétrable (fonction SQL réutilisable).

**Modèle** : `bijou`, `composition_bijou` (nomenclature/BOM), `ordre_fabrication`, `consommation_of`, `produit_fini`, `parametre` (taux horaire, marge).

**Logique en base de données** :
```sql
-- Fonction de calcul du coût de revient d'un bijou
CREATE OR REPLACE FUNCTION fn_cout_revient(p_id_bijou INT)
RETURNS DECIMAL(10,2) AS $$
DECLARE
    v_cout_matieres DECIMAL(10,2);
    v_temps_min INT;
    v_taux_horaire DECIMAL(10,2);
    v_charges DECIMAL(10,2);
BEGIN
    SELECT COALESCE(SUM(cb.quantite_necessaire * mp.prix_unitaire), 0)
    INTO v_cout_matieres
    FROM composition_bijou cb
    JOIN matiere_premiere mp ON mp.id_matiere = cb.id_matiere
    WHERE cb.id_bijou = p_id_bijou;

    SELECT temps_fabrication_minutes INTO v_temps_min FROM bijou WHERE id_bijou = p_id_bijou;
    SELECT valeur::DECIMAL INTO v_taux_horaire FROM parametre WHERE cle = 'taux_horaire';
    SELECT valeur::DECIMAL INTO v_charges FROM parametre WHERE cle = 'charges_indirectes_forfait';

    RETURN v_cout_matieres + (v_temps_min / 60.0 * v_taux_horaire) + COALESCE(v_charges, 0);
END;
$$ LANGUAGE plpgsql;

-- Trigger : à la clôture d'un OF, consommation des matières + sortie de stock automatique
CREATE OR REPLACE FUNCTION fn_cloturer_of()
RETURNS TRIGGER AS $$
DECLARE
    r RECORD;
    v_id_type_sortie INT;
BEGIN
    IF NEW.id_statut_prod <> OLD.id_statut_prod
       AND (SELECT code FROM statut_production WHERE id_statut_prod = NEW.id_statut_prod) = 'TERMINE' THEN

        SELECT id_type_mvt INTO v_id_type_sortie FROM type_mouvement WHERE code = 'PRODUCTION';

        FOR r IN
            SELECT id_matiere, quantite_necessaire * NEW.quantite_realisee AS qte
            FROM composition_bijou WHERE id_bijou = NEW.id_bijou
        LOOP
            INSERT INTO consommation_of(id_of, id_matiere, quantite_consommee)
            VALUES (NEW.id_of, r.id_matiere, r.qte);

            INSERT INTO mouvement_stock(id_matiere, id_type_mvt, quantite, reference_externe)
            VALUES (r.id_matiere, v_id_type_sortie, r.qte, NEW.reference);
        END LOOP;

        INSERT INTO produit_fini(id_bijou, id_of, reference, quantite_stock, cout_revient, date_fabrication)
        VALUES (NEW.id_bijou, NEW.id_of, NEW.reference || '-PF', NEW.quantite_realisee,
                fn_cout_revient(NEW.id_bijou), CURRENT_DATE);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_of_cloture
    AFTER UPDATE ON ordre_fabrication
    FOR EACH ROW EXECUTE FUNCTION fn_cloturer_of();
```
> Cette approche réutilise le trigger de la Fonctionnalité 1 (`mouvement_stock` déclenche déjà la décrémentation) → **un seul point de vérité** pour la gestion du stock, c'est un vrai argument d'architecture à mentionner en entretien.

**Côté Laravel** :
- `BijouController` + `CompositionBijouController` : gestion de la fiche bijou et de sa nomenclature.
- `OrdreFabricationController::store()` : validation (stock suffisant avant lancement — appel d'une fonction SQL `fn_verifier_disponibilite`), création dans `DB::transaction()`.
- `OrdreFabricationController::updateStatut()` : change juste le statut, le trigger fait le reste. Laravel encapsule ça dans une transaction pour rester atomique.
- Endpoint `GET /api/bijoux/{id}/cout-revient` → appelle `SELECT fn_cout_revient(:id)`.

**Côté React** :
- Page *Fiches bijoux* : formulaire bijou + éditeur de nomenclature (ajout de matières + quantités).
- Page *Production* : liste des OF, formulaire de création, changement de statut (workflow simplifié à 3 statuts : `EN_ATTENTE` → `EN_COURS` → `TERMINE`).
- Page *Coût de revient* (peut être un onglet de la fiche bijou) : affichage détaillé + comparaison entre bijoux (petit graphique barres).

**Tests à écrire** :
- `fn_cout_revient` retourne la bonne valeur sur un jeu de données connu.
- Clôturer un OF décrémente bien le stock des matières concernées.
- Lancer un OF sans stock suffisant est refusé.

---

### 🔹 Fonctionnalité 3 — Contrôle qualité + reporting (statistiques/dashboard)

**Pourquoi c'est un bon point CV** : montre la capacité à faire du reporting via des vues agrégées SQL plutôt que de tout calculer en PHP — bonne pratique de performance.

**Modèle** : `controle_qualite`, `defaut_constate`, `type_defaut`.

**Logique en base de données** :
```sql
-- Vue : taux de rejet par bijou
CREATE VIEW vue_taux_rejet_par_bijou AS
SELECT b.id_bijou, b.nom,
       SUM(cq.quantite_controlee) AS total_controle,
       SUM(cq.quantite_rejetee) AS total_rejete,
       ROUND(100.0 * SUM(cq.quantite_rejetee) / NULLIF(SUM(cq.quantite_controlee),0), 2) AS taux_rejet_pct
FROM controle_qualite cq
JOIN ordre_fabrication of2 ON of2.id_of = cq.id_of
JOIN bijou b ON b.id_bijou = of2.id_bijou
GROUP BY b.id_bijou, b.nom;

-- Vue : KPI dashboard
CREATE VIEW vue_kpi_dashboard AS
SELECT
    (SELECT COUNT(*) FROM ordre_fabrication of2 JOIN statut_production sp ON sp.id_statut_prod=of2.id_statut_prod WHERE sp.code='EN_COURS') AS bijoux_en_cours,
    (SELECT COALESCE(SUM(quantite_rejetee),0) FROM ordre_fabrication) AS total_rejetes,
    (SELECT ROUND(AVG(fn_cout_revient(id_bijou)),2) FROM bijou WHERE actif) AS cout_moyen_bijou,
    (SELECT COALESCE(SUM(quantite_stock),0) FROM produit_fini WHERE statut='EN_STOCK') AS produits_en_stock;
```

**Trigger complémentaire** : à l'insertion d'un `controle_qualite`, incrémenter `ordre_fabrication.quantite_rejetee` automatiquement (même logique de trigger que ci-dessus).

**Côté Laravel** :
- `ControleQualiteController::store()` : insère le contrôle + les défauts associés (transaction), le trigger met à jour l'OF.
- `StatistiquesController` : endpoints qui exposent simplement `vue_taux_rejet_par_bijou` et `vue_kpi_dashboard` (aucun calcul PHP nécessaire → performant).

**Côté React** :
- Page *Contrôle qualité* : checklist de défauts (cases à cocher reliées à `type_defaut`), décision valider/rejeter.
- Onglet *Statistiques* : graphiques avec **Recharts** (taux de rejet par bijou en barres, répartition des défauts en camembert).
- Dashboard : cartes KPI alimentées par `vue_kpi_dashboard` (au lieu de données statiques comme dans la maquette fournie).

**Tests à écrire** :
- Un contrôle qualité avec rejets incrémente bien `quantite_rejetee` sur l'OF.
- La vue `vue_taux_rejet_par_bijou` calcule le bon pourcentage.

---

## 2. RÉPARTITION DE LA LOGIQUE MÉTIER (pour rester performant)

| Où | Quoi |
|---|---|
| **PostgreSQL** (triggers, fonctions, vues) | Mise à jour du stock, blocage stock négatif, calcul du coût de revient, consommation automatique des matières, incrémentation des rejets, toutes les agrégations pour le dashboard/statistiques |
| **Laravel** | Validation des entrées, authentification, orchestration des transactions (`DB::transaction`), formatage JSON (API Resources), gestion des erreurs métier remontées par PostgreSQL |
| **React** | Formulaires, affichage, appels API, état d'interface (filtres, pagination côté UI), graphiques (à partir des données déjà agrégées côté back) |

Cette répartition évite les allers-retours inutiles entre PHP et la base, et réduit fortement le risque d'incohérence de données — un vrai argument de performance/fiabilité à mettre en avant en entretien.

---

## 3. PLAN DÉTAILLÉ — JOUR 1

### Matin (env. 4h) — Setup + Backend Fonctionnalité 1

**Installer / vérifier :**
- [x] PHP ≥ 8.2, Composer
- [x] Node.js ≥ 18, npm
- [x] PostgreSQL ≥ 14 (+ pgAdmin ou DBeaver pour visualiser)
- [x] Laravel installer (`composer global require laravel/installer`)
- [x] Git configuré, dépôt GitHub créé

**Initialisation projet :**
```bash
laravel new backend --git
cd backend
composer require laravel/sanctum
php artisan install:api   # ou config manuelle Sanctum
# .env : DB_CONNECTION=pgsql, créer la base "bijoux_db"


ou

composer create-project laravel/laravel backend
cd backend
composer require laravel/sanctum
php artisan install:api
```
```bash
npm create vite@latest frontend -- --template react
cd frontend
npm install axios react-router-dom recharts
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```
- [x] Configurer CORS (`config/cors.php`) pour autoriser `http://localhost:5173`
- [x] Créer un `.env.example` propre, initialiser le README

**Migrations & modèles :**
- [x] Traduire le schéma SQL fourni en migrations Laravel (tables : `unite_mesure`, `categorie_matiere`, `matiere_premiere`, `type_mouvement`, `mouvement_stock`, `type_bijou`, `collection`, `bijou`, `composition_bijou`, `parametre`)
- [x] Créer les `ENUM` PostgreSQL via `DB::statement()` dans une migration dédiée (`sens_mouvement`, `taille_bijou`, `complexite_bijou`, `statut_produit`)
- [x] Créer les modèles Eloquent + relations (`Bijou::compositions()`, `MatierePremiere::mouvements()`, etc.)
- [x] Migration séparée pour les triggers/fonctions/vues de la **Fonctionnalité 1** (via `DB::unprepared()`)
- [xsq] Seeders : unités, catégories, quelques matières premières, types de mouvement, paramètres (taux horaire, charges)

**Backend Fonctionnalité 1 :**
- [x] `MatierePremiereController` (CRUD) + `FormRequest` de validation
- [x] `MouvementStockController@store` (capture des exceptions PostgreSQL)
- [x] Route `GET /api/matieres/alertes`
- [ ] Tester tout avec Postman
- [x] 2-3 tests PHPUnit (Feature) sur le trigger de stock

### Après-midi (env. 4h) — Frontend layout + Fonctionnalité 1

- [x] Layout général React : `Sidebar` + `Header` en reprenant le Design System (couleurs, Poppins, structure de `dashboard.html` fourni)
- [x] Mise en place React Router (routes des 6 pages retenues)
- [x] Client API (axios instance avec base URL + intercepteur d'erreurs)
- [x] Page *Matières premières* (tableau + modal ajout/édition)
- [x] Page *Stock & mouvements* (historique + formulaire entrée/sortie + badges Alerte/OK)
- [x] Widget alertes sur le Dashboard (données réelles via `/api/matieres/alertes`)
- [x] Commit + push

---

## 4. PLAN DÉTAILLÉ — JOUR 2

### Matin (env. 4h) — Fonctionnalité 2 (Production + coût de revient)

**Backend :**
- [x] Migrations restantes : `type_defaut`, `statut_production`, `ordre_fabrication`, `consommation_of`, `produit_fini`, `controle_qualite`, `defaut_constate`
- [x] Migration triggers/fonctions Fonctionnalité 2 (`fn_cout_revient`, `fn_cloturer_of`)
- [x] `BijouController` + `CompositionBijouController`
- [x] `OrdreFabricationController` (création avec vérification de disponibilité, changement de statut)
- [x] Route `GET /api/bijoux/{id}/cout-revient`
- [x] Tests Postman + 2-3 tests PHPUnit (clôture OF → stock décrémenté, calcul coût de revient)

**Frontend :**
- [x] Page *Fiches bijoux* : formulaire + éditeur de nomenclature (ajout de lignes matière/quantité)
- [x] Page *Production* : liste des OF, création, workflow de statut simplifié (3 statuts), affichage coût de revient calculé
- [x] Commit + push

### Après-midi (env. 4h) — Fonctionnalité 3 (Qualité + Stats) + finitions

**Backend :**
- [ ] Migration triggers/vues Fonctionnalité 3 (`fn_incrementer_rejet`, `vue_taux_rejet_par_bijou`, `vue_kpi_dashboard`)
- [ ] `ControleQualiteController` (store avec défauts associés, transaction)
- [ ] `StatistiquesController` (expose les vues)
- [ ] Route `GET /api/dashboard/kpi`
- [ ] Tests PHPUnit restants

**Frontend :**
- [ ] Page *Contrôle qualité* (checklist défauts + décision)
- [ ] Onglet *Statistiques* (graphiques Recharts : taux de rejet, défauts par type)
- [ ] Finaliser le Dashboard avec les vraies données KPI (`vue_kpi_dashboard`)
- [ ] Passe de cohérence visuelle (couleurs, espacements, responsive basique)

**Finitions (dernière heure) :**
- [ ] Relire et compléter le `README.md` (stack, install, captures d'écran, roadmap des fonctionnalités non traitées)
- [ ] Vérifier que `.env` n'est pas commité, que `.env.example` est à jour
- [ ] (Optionnel si le temps le permet) Déploiement : backend sur Railway/Render, frontend sur Vercel/Netlify, base PostgreSQL managée (Neon/Railway)
- [ ] Enregistrer une courte démo (GIF ou vidéo 1 min) pour le portfolio
- [ ] Dernier commit + tag `v1.0`

---

## 5. CHECKLIST FINALE — CE QUE VOUS DEVEZ POUVOIR MONTRER

- [ ] Un dépôt Git propre avec historique de commits progressif
- [ ] Un README complet avec captures d'écran
- [ ] Les 3 fonctionnalités phares fonctionnelles de bout en bout (DB → API → UI)
- [ ] Au moins 6-8 tests PHPUnit passants
- [ ] Une explication claire, prête pour l'entretien, de **pourquoi** la logique a été mise en base (triggers/fonctions/vues) plutôt qu'en PHP — c'est le point différenciant de ce projet.

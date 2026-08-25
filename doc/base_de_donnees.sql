-- ============================================
-- TYPES ENUM POSTGRESQL
-- ============================================
CREATE TYPE sens_mouvement AS ENUM ('ENTREE', 'SORTIE');
CREATE TYPE taille_bijou AS ENUM ('XS', 'S', 'M', 'L', 'XL');
CREATE TYPE complexite_bijou AS ENUM ('Simple', 'Moyenne', 'Complexe');
CREATE TYPE statut_produit AS ENUM ('EN_STOCK', 'RESERVE', 'VENDU');

-- ============================================
-- 1. TABLE DES UNITÉS DE MESURE
-- ============================================
CREATE TABLE unite_mesure (
    id_unite SERIAL PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    libelle VARCHAR(50) NOT NULL
);

-- ============================================
-- 2. TABLE DES CATÉGORIES DE MATIÈRES
-- ============================================
CREATE TABLE categorie_matiere (
    id_categorie SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- ============================================
-- 3. TABLE DES MATIÈRES PREMIÈRES
-- ============================================
CREATE TABLE matiere_premiere (
    id_matiere SERIAL PRIMARY KEY,
    id_categorie INT NOT NULL,
    id_unite INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    couleur VARCHAR(50),
    quantite_stock DECIMAL(10,2) DEFAULT 0,
    seuil_alerte DECIMAL(10,2) NOT NULL,
    prix_unitaire DECIMAL(10,4) NOT NULL,
    date_peremption DATE,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_matiere_categorie FOREIGN KEY (id_categorie) 
        REFERENCES categorie_matiere(id_categorie),
    CONSTRAINT fk_matiere_unite FOREIGN KEY (id_unite) 
        REFERENCES unite_mesure(id_unite)
);

-- ============================================
-- 4. TABLE DES TYPES DE MOUVEMENTS
-- ============================================
CREATE TABLE type_mouvement (
    id_type_mvt SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(50) NOT NULL,
    sens sens_mouvement NOT NULL
);

-- ============================================
-- 5. TABLE DES MOUVEMENTS DE STOCK
-- ============================================
CREATE TABLE mouvement_stock (
    id_mouvement SERIAL PRIMARY KEY,
    id_matiere INT NOT NULL,
    id_type_mvt INT NOT NULL,
    quantite DECIMAL(10,2) NOT NULL,
    prix_total DECIMAL(10,2),
    reference_externe VARCHAR(100),
    commentaire TEXT,
    date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_mouvement_matiere FOREIGN KEY (id_matiere) 
        REFERENCES matiere_premiere(id_matiere),
    CONSTRAINT fk_mouvement_type FOREIGN KEY (id_type_mvt) 
        REFERENCES type_mouvement(id_type_mvt)
);

-- ============================================
-- 6. TABLE DES TYPES DE BIJOUX
-- ============================================
CREATE TABLE type_bijou (
    id_type_bijou SERIAL PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- ============================================
-- 7. TABLE DES COLLECTIONS
-- ============================================
CREATE TABLE collection (
    id_collection SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    saison VARCHAR(50),
    annee INT CHECK (annee >= 1900 AND annee <= 2100),
    description TEXT,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 8. TABLE DES BIJOUX (MODÈLES)
-- ============================================
CREATE TABLE bijou (
    id_bijou SERIAL PRIMARY KEY,
    id_type_bijou INT NOT NULL,
    id_collection INT,
    reference VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    taille taille_bijou DEFAULT 'M',
    complexite complexite_bijou DEFAULT 'Moyenne',
    temps_fabrication_minutes INT NOT NULL,
    photo_url VARCHAR(255),
    description TEXT,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_bijou_type FOREIGN KEY (id_type_bijou) 
        REFERENCES type_bijou(id_type_bijou),
    CONSTRAINT fk_bijou_collection FOREIGN KEY (id_collection) 
        REFERENCES collection(id_collection)
);

-- ============================================
-- 9. TABLE COMPOSITION BIJOU (NOMENCLATURE)
-- ============================================
CREATE TABLE composition_bijou (
    id_composition SERIAL PRIMARY KEY,
    id_bijou INT NOT NULL,
    id_matiere INT NOT NULL,
    quantite_necessaire DECIMAL(10,2) NOT NULL,
    
    CONSTRAINT fk_composition_bijou FOREIGN KEY (id_bijou) 
        REFERENCES bijou(id_bijou) ON DELETE CASCADE,
    CONSTRAINT fk_composition_matiere FOREIGN KEY (id_matiere) 
        REFERENCES matiere_premiere(id_matiere),
    CONSTRAINT unique_bijou_matiere UNIQUE (id_bijou, id_matiere)
);

-- ============================================
-- 10. TABLE DES PARAMÈTRES
-- ============================================
CREATE TABLE parametre (
    id_parametre SERIAL PRIMARY KEY,
    cle VARCHAR(50) NOT NULL UNIQUE,
    valeur VARCHAR(255) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 11. TABLE DES CLIENTS
-- ============================================
CREATE TABLE client (
    id_client SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    email VARCHAR(150),
    telephone VARCHAR(20),
    adresse TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 12. TABLE DES STATUTS DE COMMANDE
-- ============================================
CREATE TABLE statut_commande (
    id_statut SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(50) NOT NULL,
    ordre INT NOT NULL
);

-- ============================================
-- 13. TABLE DES COMMANDES
-- ============================================
CREATE TABLE commande (
    id_commande SERIAL PRIMARY KEY,
    id_client INT,
    id_statut INT NOT NULL,
    reference VARCHAR(50) NOT NULL UNIQUE,
    date_commande DATE NOT NULL,
    date_livraison_prevue DATE,
    date_livraison_reelle DATE,
    montant_total DECIMAL(10,2),
    acompte_verse DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_commande_client FOREIGN KEY (id_client) 
        REFERENCES client(id_client),
    CONSTRAINT fk_commande_statut FOREIGN KEY (id_statut) 
        REFERENCES statut_commande(id_statut)
);

-- ============================================
-- 14. TABLE DES LIGNES DE COMMANDE
-- ============================================
CREATE TABLE ligne_commande (
    id_ligne SERIAL PRIMARY KEY,
    id_commande INT NOT NULL,
    id_bijou INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    personnalisation TEXT,
    
    CONSTRAINT fk_ligne_commande FOREIGN KEY (id_commande) 
        REFERENCES commande(id_commande) ON DELETE CASCADE,
    CONSTRAINT fk_ligne_bijou FOREIGN KEY (id_bijou) 
        REFERENCES bijou(id_bijou)
);

-- ============================================
-- 15. TABLE DES STATUTS DE PRODUCTION
-- ============================================
CREATE TABLE statut_production (
    id_statut_prod SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(50) NOT NULL,
    ordre INT NOT NULL
);

-- ============================================
-- 16. TABLE DES ORDRES DE FABRICATION
-- ============================================
CREATE TABLE ordre_fabrication (
    id_of SERIAL PRIMARY KEY,
    id_bijou INT NOT NULL,
    id_statut_prod INT NOT NULL,
    id_commande INT,
    reference VARCHAR(50) NOT NULL UNIQUE,
    quantite_prevue INT NOT NULL,
    quantite_realisee INT DEFAULT 0,
    quantite_rejetee INT DEFAULT 0,
    date_debut DATE,
    date_fin_prevue DATE,
    date_fin_reelle DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_of_bijou FOREIGN KEY (id_bijou) 
        REFERENCES bijou(id_bijou),
    CONSTRAINT fk_of_statut FOREIGN KEY (id_statut_prod) 
        REFERENCES statut_production(id_statut_prod),
    CONSTRAINT fk_of_commande FOREIGN KEY (id_commande) 
        REFERENCES commande(id_commande)
);

-- ============================================
-- 17. TABLE DES CONSOMMATIONS MATIÈRES
-- ============================================
CREATE TABLE consommation_of (
    id_consommation SERIAL PRIMARY KEY,
    id_of INT NOT NULL,
    id_matiere INT NOT NULL,
    quantite_consommee DECIMAL(10,2) NOT NULL,
    date_consommation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_consommation_of FOREIGN KEY (id_of) 
        REFERENCES ordre_fabrication(id_of) ON DELETE CASCADE,
    CONSTRAINT fk_consommation_matiere FOREIGN KEY (id_matiere) 
        REFERENCES matiere_premiere(id_matiere)
);

-- ============================================
-- 18. TABLE DES TYPES DE DÉFAUTS
-- ============================================
CREATE TABLE type_defaut (
    id_type_defaut SERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    description TEXT
);

-- ============================================
-- 19. TABLE DES CONTRÔLES QUALITÉ
-- ============================================
CREATE TABLE controle_qualite (
    id_controle SERIAL PRIMARY KEY,
    id_of INT NOT NULL,
    date_controle TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    quantite_controlee INT NOT NULL,
    quantite_validee INT NOT NULL,
    quantite_rejetee INT NOT NULL,
    commentaire TEXT,
    
    CONSTRAINT fk_controle_of FOREIGN KEY (id_of) 
        REFERENCES ordre_fabrication(id_of)
);

-- ============================================
-- 20. TABLE DES DÉFAUTS CONSTATÉS
-- ============================================
CREATE TABLE defaut_constate (
    id_defaut SERIAL PRIMARY KEY,
    id_controle INT NOT NULL,
    id_type_defaut INT NOT NULL,
    quantite INT NOT NULL,
    photo_url VARCHAR(255),
    commentaire TEXT,
    
    CONSTRAINT fk_defaut_controle FOREIGN KEY (id_controle) 
        REFERENCES controle_qualite(id_controle) ON DELETE CASCADE,
    CONSTRAINT fk_defaut_type FOREIGN KEY (id_type_defaut) 
        REFERENCES type_defaut(id_type_defaut)
);

-- ============================================
-- 21. TABLE DES PRODUITS FINIS
-- ============================================
CREATE TABLE produit_fini (
    id_produit_fini SERIAL PRIMARY KEY,
    id_bijou INT NOT NULL,
    id_of INT,
    reference VARCHAR(50) NOT NULL UNIQUE,
    quantite_stock INT NOT NULL DEFAULT 0,
    cout_revient DECIMAL(10,2),
    prix_vente DECIMAL(10,2),
    date_fabrication DATE,
    statut statut_produit DEFAULT 'EN_STOCK',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_produit_bijou FOREIGN KEY (id_bijou) 
        REFERENCES bijou(id_bijou),
    CONSTRAINT fk_produit_of FOREIGN KEY (id_of) 
        REFERENCES ordre_fabrication(id_of)
);

-- ============================================
-- 22. TABLE DES FOURNISSEURS
-- ============================================
CREATE TABLE fournisseur (
    id_fournisseur SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    contact VARCHAR(100),
    email VARCHAR(150),
    telephone VARCHAR(20),
    adresse TEXT,
    notes TEXT,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 23. TABLE LIAISON FOURNISSEUR-MATIÈRE
-- ============================================
CREATE TABLE fournisseur_matiere (
    id_fournisseur INT NOT NULL,
    id_matiere INT NOT NULL,
    prix_achat DECIMAL(10,4),
    delai_livraison_jours INT,
    
    PRIMARY KEY (id_fournisseur, id_matiere),
    CONSTRAINT fk_fm_fournisseur FOREIGN KEY (id_fournisseur) 
        REFERENCES fournisseur(id_fournisseur),
    CONSTRAINT fk_fm_matiere FOREIGN KEY (id_matiere) 
        REFERENCES matiere_premiere(id_matiere)
);

-- ============================================
-- FONCTION POUR MISE À JOUR AUTOMATIQUE updated_at
-- ============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- TRIGGERS POUR updated_at
-- ============================================
CREATE TRIGGER tr_matiere_premiere_updated
    BEFORE UPDATE ON matiere_premiere
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER tr_bijou_updated
    BEFORE UPDATE ON bijou
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER tr_parametre_updated
    BEFORE UPDATE ON parametre
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER tr_commande_updated
    BEFORE UPDATE ON commande
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER tr_ordre_fabrication_updated
    BEFORE UPDATE ON ordre_fabrication
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- INDEX POUR OPTIMISATION
-- ============================================
CREATE INDEX idx_matiere_categorie ON matiere_premiere(id_categorie);
CREATE INDEX idx_matiere_stock ON matiere_premiere(quantite_stock, seuil_alerte);
CREATE INDEX idx_bijou_type ON bijou(id_type_bijou);
CREATE INDEX idx_bijou_collection ON bijou(id_collection);
CREATE INDEX idx_commande_client ON commande(id_client);
CREATE INDEX idx_commande_statut ON commande(id_statut);
CREATE INDEX idx_of_bijou ON ordre_fabrication(id_bijou);
CREATE INDEX idx_of_statut ON ordre_fabrication(id_statut_prod);
CREATE INDEX idx_mouvement_date ON mouvement_stock(date_mouvement);
-- ============================================================
--  FICHES PÉDAGOGIQUES - Schéma PostgreSQL
-- ============================================================

-- Extensions
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ============================================================
--  UTILISATEURS
-- ============================================================
CREATE TABLE users (
    id          SERIAL PRIMARY KEY,
    email       VARCHAR(255) UNIQUE NOT NULL,
    password    VARCHAR(255),                    -- NULL si OAuth uniquement
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    avatar_url  TEXT,
    oauth_provider VARCHAR(50),                  -- 'google', 'facebook', NULL
    oauth_id    VARCHAR(255),
    is_admin    BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP DEFAULT NOW(),
    updated_at  TIMESTAMP DEFAULT NOW()
);

-- ============================================================
--  PROGRAMMES - Référentiel complet
-- ============================================================

-- Cycles
CREATE TABLE cycles (
    id    SERIAL PRIMARY KEY,
    code  VARCHAR(10) UNIQUE NOT NULL,   -- 'C1', 'C2', 'C3'
    label VARCHAR(100) NOT NULL          -- 'Cycle 1 (Maternelle)', ...
);

-- Classes
CREATE TABLE classes (
    id       SERIAL PRIMARY KEY,
    cycle_id INTEGER REFERENCES cycles(id),
    code     VARCHAR(20) UNIQUE NOT NULL, -- 'CP', 'CE1', 'CM1', etc.
    label    VARCHAR(100) NOT NULL
);

-- Matières/Domaines
CREATE TABLE matieres (
    id    SERIAL PRIMARY KEY,
    code  VARCHAR(50) UNIQUE NOT NULL,
    label VARCHAR(150) NOT NULL
);

-- Versions de programme (ancien / nouveau)
CREATE TABLE programme_versions (
    id            SERIAL PRIMARY KEY,
    matiere_id    INTEGER REFERENCES matieres(id),
    cycle_id      INTEGER REFERENCES cycles(id),
    classe_id     INTEGER REFERENCES classes(id),  -- NULL = tout le cycle
    annee_entree  INTEGER NOT NULL,                 -- 2020, 2024, 2025...
    label         VARCHAR(200) NOT NULL,
    en_vigueur    BOOLEAN DEFAULT TRUE,
    source_url    TEXT,
    notes         TEXT                              -- ex: "inchangé pour CM2"
);

-- Items du programme (compétences, objectifs, attendus)
CREATE TABLE programme_items (
    id                   SERIAL PRIMARY KEY,
    programme_version_id INTEGER REFERENCES programme_versions(id),
    parent_id            INTEGER REFERENCES programme_items(id),
    niveau               INTEGER DEFAULT 0,         -- 0=domaine, 1=sous-domaine, 2=compétence, 3=attendu
    code                 VARCHAR(50),               -- ex: "L1.1", "E2.3"
    label                TEXT NOT NULL,
    description          TEXT,
    ordre                INTEGER DEFAULT 0
);

-- ============================================================
--  FICHES
-- ============================================================

-- Séquences
CREATE TABLE sequences (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER REFERENCES users(id) ON DELETE CASCADE,
    titre           TEXT NOT NULL,
    domaine         VARCHAR(200),
    matiere_id      INTEGER REFERENCES matieres(id),
    cycle_id        INTEGER REFERENCES cycles(id),
    classe_id       INTEGER REFERENCES classes(id),
    programme_version_id INTEGER REFERENCES programme_versions(id),
    nb_seances      INTEGER DEFAULT 1,
    tache_finale    TEXT,
    objectifs_generaux TEXT,
    materiel        TEXT,
    comportements_remediations JSONB DEFAULT '[]',  -- [{comportement, remediation}]
    is_public       BOOLEAN DEFAULT FALSE,
    forked_from     INTEGER REFERENCES sequences(id),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

-- Liaison séquences <-> items du programme
CREATE TABLE sequence_programme_items (
    sequence_id         INTEGER REFERENCES sequences(id) ON DELETE CASCADE,
    programme_item_id   INTEGER REFERENCES programme_items(id),
    PRIMARY KEY (sequence_id, programme_item_id)
);

-- Séances
CREATE TABLE seances (
    id              SERIAL PRIMARY KEY,
    sequence_id     INTEGER REFERENCES sequences(id) ON DELETE CASCADE,
    numero          INTEGER NOT NULL DEFAULT 1,
    titre           TEXT NOT NULL,
    champ_apprentissage TEXT,
    competence_visee TEXT,
    afc             TEXT,                           -- Attendu de Fin de Cycle
    objectif_general TEXT,
    objectif_intermediaire TEXT,
    duree           INTEGER,                        -- en minutes
    materiel        TEXT,
    deroulement     JSONB DEFAULT '[]',             -- [{duree, enseignant, eleves}]
    criteres_realisation TEXT,
    criteres_reussite TEXT,
    variables_didactiques TEXT,
    comportements_remediations JSONB DEFAULT '[]',
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

-- Situations
CREATE TABLE situations (
    id              SERIAL PRIMARY KEY,
    seance_id       INTEGER REFERENCES seances(id) ON DELETE CASCADE,
    numero          INTEGER NOT NULL DEFAULT 1,
    titre           TEXT NOT NULL,
    champ_apprentissage TEXT,
    duree           INTEGER,                        -- en minutes
    afc             TEXT,
    objectif_moteur TEXT,
    objectif_socio_affectif TEXT,
    objectif_cognitif TEXT,
    materiel        TEXT,
    but             TEXT,
    dispositif      TEXT,
    organisation    TEXT,
    fonctionnement  TEXT,
    consignes_base  TEXT,
    variables_evolution JSONB DEFAULT '[]',         -- [{variable, plus, moins}]
    criteres_realisation TEXT,
    criteres_reussite TEXT,
    comportements_remediations JSONB DEFAULT '[]',
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

-- Tags libres
CREATE TABLE tags (
    id    SERIAL PRIMARY KEY,
    label VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE sequence_tags (
    sequence_id INTEGER REFERENCES sequences(id) ON DELETE CASCADE,
    tag_id      INTEGER REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (sequence_id, tag_id)
);

-- Favoris
CREATE TABLE favoris (
    user_id     INTEGER REFERENCES users(id) ON DELETE CASCADE,
    sequence_id INTEGER REFERENCES sequences(id) ON DELETE CASCADE,
    created_at  TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (user_id, sequence_id)
);

-- ============================================================
--  DONNÉES - Cycles
-- ============================================================
INSERT INTO cycles (code, label) VALUES
('C1', 'Cycle 1 – École Maternelle'),
('C2', 'Cycle 2 – CP, CE1, CE2'),
('C3', 'Cycle 3 – CM1, CM2, 6ème');

-- Classes
INSERT INTO classes (cycle_id, code, label) VALUES
(1, 'PS',  'Petite Section'),
(1, 'MS',  'Moyenne Section'),
(1, 'GS',  'Grande Section'),
(2, 'CP',  'Cours Préparatoire'),
(2, 'CE1', 'Cours Élémentaire 1ère année'),
(2, 'CE2', 'Cours Élémentaire 2ème année'),
(3, 'CM1', 'Cours Moyen 1ère année'),
(3, 'CM2', 'Cours Moyen 2ème année'),
(3, '6E',  'Sixième');

-- Matières
INSERT INTO matieres (code, label) VALUES
('FRANCAIS',       'Français'),
('MATHEMATIQUES',  'Mathématiques'),
('EMC',            'Enseignement Moral et Civique'),
('EVAR',           'Éducation à la Vie Affective et Relationnelle'),
('LVE',            'Langues Vivantes Étrangères'),
('LVR',            'Langues Vivantes Régionales'),
('ARTS_PLASTIQUES','Arts Plastiques'),
('EDUCATION_MUSICALE','Éducation Musicale'),
('HISTOIRE_ARTS',  'Histoire des Arts'),
('EPS',            'Éducation Physique et Sportive'),
('HIST_GEO',       'Histoire et Géographie'),
('SCIENCES_TECH',  'Sciences et Technologie'),
('LANGAGE_ORAL',   'Développement et Structuration du Langage Oral et Écrit'),
('OUTILS_MATH',    'Acquisition des Premiers Outils Mathématiques'),
('QUESTMONDE',     'Questionner le Monde'),
('AUTRES_C1',      'Autres Domaines – Cycle 1');

-- ============================================================
--  VERSIONS DE PROGRAMME (rentrée 2025)
-- ============================================================

-- CYCLE 1 - Nouveau 2025
INSERT INTO programme_versions (matiere_id, cycle_id, classe_id, annee_entree, label, en_vigueur, notes) VALUES
((SELECT id FROM matieres WHERE code='LANGAGE_ORAL'), 1, NULL, 2025, 'Développement et structuration du langage oral et écrit – Cycle 1 (2025)', TRUE, 'Nouveau programme rentrée 2025'),
((SELECT id FROM matieres WHERE code='OUTILS_MATH'),  1, NULL, 2025, 'Acquisition des premiers outils mathématiques – Cycle 1 (2025)', TRUE, 'Nouveau programme rentrée 2025'),
((SELECT id FROM matieres WHERE code='AUTRES_C1'),    1, NULL, 2021, 'Autres domaines – Cycle 1 (2021)', TRUE, 'Programme inchangé');

-- CYCLE 2 - Nouveau 2025
INSERT INTO programme_versions (matiere_id, cycle_id, classe_id, annee_entree, label, en_vigueur, notes) VALUES
((SELECT id FROM matieres WHERE code='FRANCAIS'),      2, NULL, 2025, 'Français – Cycle 2 (2025)', TRUE, 'Nouveau programme pour CP, CE1, CE2'),
((SELECT id FROM matieres WHERE code='MATHEMATIQUES'), 2, NULL, 2025, 'Mathématiques – Cycle 2 (2025)', TRUE, 'Nouveau programme pour CP, CE1, CE2'),
((SELECT id FROM matieres WHERE code='EMC'),           2, (SELECT id FROM classes WHERE code='CE1'), 2025, 'EMC – CE1 (2025)', TRUE, 'Nouveau programme pour CE1'),
((SELECT id FROM matieres WHERE code='EMC'),           2, (SELECT id FROM classes WHERE code='CP'),  2024, 'EMC – CP (2024)', TRUE, 'Programme inchangé CP'),
((SELECT id FROM matieres WHERE code='EMC'),           2, (SELECT id FROM classes WHERE code='CE2'), 2020, 'EMC – CE2 (2020)', TRUE, 'Programme inchangé CE2'),
((SELECT id FROM matieres WHERE code='EVAR'),          2, NULL, 2025, 'EVAR – Cycle 2 (2025)', TRUE, 'Nouveau programme CP, CE1, CE2');

-- CYCLE 3 - Mixte 2025
INSERT INTO programme_versions (matiere_id, cycle_id, classe_id, annee_entree, label, en_vigueur, notes) VALUES
((SELECT id FROM matieres WHERE code='FRANCAIS'),      3, (SELECT id FROM classes WHERE code='CM1'), 2025, 'Français – CM1 (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='FRANCAIS'),      3, (SELECT id FROM classes WHERE code='6E'),  2025, 'Français – 6ème (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='FRANCAIS'),      3, (SELECT id FROM classes WHERE code='CM2'), 2023, 'Français – CM2 (inchangé 2023)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='MATHEMATIQUES'), 3, (SELECT id FROM classes WHERE code='CM1'), 2025, 'Mathématiques – CM1 (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='MATHEMATIQUES'), 3, (SELECT id FROM classes WHERE code='6E'),  2025, 'Mathématiques – 6ème (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='MATHEMATIQUES'), 3, (SELECT id FROM classes WHERE code='CM2'), 2023, 'Mathématiques – CM2 (inchangé 2023)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='EMC'),           3, (SELECT id FROM classes WHERE code='CM2'), 2025, 'EMC – CM2 (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='EMC'),           3, (SELECT id FROM classes WHERE code='CM1'), 2024, 'EMC – CM1 (2024)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='EMC'),           3, (SELECT id FROM classes WHERE code='6E'),  2020, 'EMC – 6ème (2020)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='EVAR'),          3, NULL, 2025, 'EVAR – CM1 et CM2 (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='LVE'),           3, (SELECT id FROM classes WHERE code='6E'),  2025, 'LVE – 6ème (2025)', TRUE, 'Nouveau programme'),
((SELECT id FROM matieres WHERE code='LVE'),           3, (SELECT id FROM classes WHERE code='CM1'), 2020, 'LVE – CM1/CM2 (inchangé)', TRUE, 'Programme inchangé');

-- Autres matières C3 inchangées
INSERT INTO programme_versions (matiere_id, cycle_id, classe_id, annee_entree, label, en_vigueur, notes) VALUES
((SELECT id FROM matieres WHERE code='EPS'),           3, NULL, 2020, 'EPS – Cycle 3 (inchangé)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='HIST_GEO'),      3, NULL, 2020, 'Histoire et Géographie – Cycle 3 (inchangé)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='SCIENCES_TECH'), 3, NULL, 2020, 'Sciences et Technologie – Cycle 3 (inchangé)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='ARTS_PLASTIQUES'),3, NULL, 2020, 'Arts Plastiques – Cycle 3 (inchangé)', TRUE, 'Programme inchangé'),
((SELECT id FROM matieres WHERE code='HISTOIRE_ARTS'), 3, NULL, 2020, 'Histoire des Arts – Cycle 3 (inchangé)', TRUE, 'Programme inchangé');

-- ============================================================
--  ITEMS - Français Cycle 2 (2025) - extraits structurants
-- ============================================================
DO $$
DECLARE v_pv INTEGER;
BEGIN
  SELECT id INTO v_pv FROM programme_versions WHERE label LIKE 'Français – Cycle 2%';

  -- LECTURE
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, NULL, 0, 'L', 'Lecture', 1);
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, currval('programme_items_id_seq'), 1, 'L.CP', 'Cours Préparatoire', 1),
  (v_pv, currval('programme_items_id_seq'), 1, 'L.CE1', 'Cours Élémentaire 1ère année', 2),
  (v_pv, currval('programme_items_id_seq'), 1, 'L.CE2', 'Cours Élémentaire 2ème année', 3);

  -- ÉCRITURE
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, NULL, 0, 'E', 'Écriture', 2);

  -- ORAL
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, NULL, 0, 'O', 'Oral', 3);

  -- VOCABULAIRE
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, NULL, 0, 'V', 'Vocabulaire', 4);

  -- GRAMMAIRE
  INSERT INTO programme_items (programme_version_id, parent_id, niveau, code, label, ordre) VALUES
  (v_pv, NULL, 0, 'G', 'Grammaire et Orthographe', 5);
END $$;

-- ============================================================
--  INDEXES
-- ============================================================
CREATE INDEX idx_sequences_user   ON sequences(user_id);
CREATE INDEX idx_sequences_public ON sequences(is_public) WHERE is_public = TRUE;
CREATE INDEX idx_sequences_cycle  ON sequences(cycle_id);
CREATE INDEX idx_sequences_classe ON sequences(classe_id);
CREATE INDEX idx_seances_seq      ON seances(sequence_id);
CREATE INDEX idx_situations_seance ON situations(seance_id);
CREATE INDEX idx_items_version    ON programme_items(programme_version_id);
CREATE INDEX idx_items_parent     ON programme_items(parent_id);

-- ============================================================
--  VUES UTILES
-- ============================================================
CREATE VIEW v_sequences_public AS
SELECT s.*, u.nom, u.prenom, u.avatar_url,
       m.label AS matiere_label,
       c.label AS cycle_label,
       cl.label AS classe_label,
       pv.label AS programme_label,
       pv.annee_entree AS programme_annee
FROM sequences s
JOIN users u ON s.user_id = u.id
LEFT JOIN matieres m ON s.matiere_id = m.id
LEFT JOIN cycles c ON s.cycle_id = c.id
LEFT JOIN classes cl ON s.classe_id = cl.id
LEFT JOIN programme_versions pv ON s.programme_version_id = pv.id
WHERE s.is_public = TRUE;

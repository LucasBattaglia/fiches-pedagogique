<?php
// src/DAO/ProgrammeDAO.php

namespace src\DAO;

class ProgrammeDAO
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    // ════════════════════════════════════════════════════════════════
    //  CYCLES & CLASSES (référentiel statique)
    // ════════════════════════════════════════════════════════════════

    public function getCycles(): array
    {
        return ConnectionPool::getConnection()
            ->query('SELECT * FROM cycles ORDER BY id')
            ->fetchAll();
    }

    public function getClassesByCycle(int $cycleId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM classes WHERE cycle_id = :c ORDER BY id'
        );
        $st->execute(['c' => $cycleId]);
        return $st->fetchAll();
    }

    public function getAllClasses(): array
    {
        return ConnectionPool::getConnection()
            ->query('SELECT cl.*, c.label as cycle_label FROM classes cl JOIN cycles c ON cl.cycle_id = c.id ORDER BY c.id, cl.id')
            ->fetchAll();
    }

    // ════════════════════════════════════════════════════════════════
    //  MATIÈRES
    // ════════════════════════════════════════════════════════════════

    public function getMatieres(): array
    {
        return ConnectionPool::getConnection()
            ->query('SELECT * FROM matieres ORDER BY label')
            ->fetchAll();
    }

    public function getMatieresByCycleClasse(?int $cycleId, ?int $classeId): array
    {
        $db = ConnectionPool::getConnection();
        if (!$cycleId) return $this->getMatieres();

        if ($classeId !== null) {
            $st = $db->prepare(
                'SELECT DISTINCT m.id, m.label, m.code
                 FROM matieres m
                 JOIN programme_versions pv ON pv.matiere_id = m.id
                 WHERE pv.cycle_id = :c AND pv.en_vigueur = TRUE
                   AND (pv.classe_id IS NULL OR pv.classe_id = :cl)
                 ORDER BY m.label'
            );
            $st->execute(['c' => $cycleId, 'cl' => $classeId]);
        } else {
            $st = $db->prepare(
                'SELECT DISTINCT m.id, m.label, m.code
                 FROM matieres m
                 JOIN programme_versions pv ON pv.matiere_id = m.id
                 WHERE pv.cycle_id = :c AND pv.en_vigueur = TRUE
                 ORDER BY m.label'
            );
            $st->execute(['c' => $cycleId]);
        }
        return $st->fetchAll();
    }

    public function createMatiere(string $label, string $code): int
    {
        $st = ConnectionPool::getConnection()->prepare(
            'INSERT INTO matieres (label, code) VALUES (:label, :code)
             ON CONFLICT (code) DO UPDATE SET label = EXCLUDED.label
             RETURNING id'
        );
        $st->execute(['label' => trim($label), 'code' => strtoupper(trim($code))]);
        return (int)$st->fetchColumn();
    }

    public function updateMatiere(int $id, string $label, string $code): void
    {
        ConnectionPool::getConnection()->prepare(
            'UPDATE matieres SET label = :label, code = :code WHERE id = :id'
        )->execute(['label' => trim($label), 'code' => strtoupper(trim($code)), 'id' => $id]);
    }

    public function deleteMatiere(int $id): void
    {
        // Les programme_matieres sont supprimées en cascade,
        // ce qui supprime les programme_versions via CASCADE
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM matieres WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function patchMatiere(int $id, array $data): void
    {
        $allowed = ['label', 'code'];
        $sets = []; $params = ['id' => $id];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[]    = "$k = :$k";
            $params[$k] = ($k === 'code') ? strtoupper(trim($v)) : trim($v);
        }
        if (empty($sets)) return;
        ConnectionPool::getConnection()
            ->prepare('UPDATE matieres SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);
    }

    // ════════════════════════════════════════════════════════════════
    //  PROGRAMMES (niveau 1)
    // ════════════════════════════════════════════════════════════════

    /**
     * Crée un nouveau programme (année + cycle).
     * Retourne l'id du programme créé.
     */
    public function createProgramme(int $annee, int $cycleId, ?string $sourceUrl): int
    {
        $st = ConnectionPool::getConnection()->prepare(
            'INSERT INTO programmes (annee_entree, cycle_id, source_url, en_vigueur)
             VALUES (:annee, :cycle, :src, TRUE)
             ON CONFLICT (annee_entree, cycle_id) DO UPDATE
                SET source_url = EXCLUDED.source_url
             RETURNING id'
        );
        $st->execute([
            'annee' => $annee,
            'cycle' => $cycleId,
            'src'   => $sourceUrl ?: null,
        ]);
        return (int)$st->fetchColumn();
    }

    public function updateProgramme(int $id, int $annee, int $cycleId, ?string $sourceUrl): void
    {
        ConnectionPool::getConnection()->prepare(
            'UPDATE programmes SET annee_entree = :annee, cycle_id = :cycle, source_url = :src WHERE id = :id'
        )->execute(['annee' => $annee, 'cycle' => $cycleId, 'src' => $sourceUrl ?: null, 'id' => $id]);
    }

    public function deleteProgramme(int $id): void
    {
        // Cascade : programme_matieres → programme_versions → programme_items
        // On supprime aussi les items manuellement pour être sûr
        $db = ConnectionPool::getConnection();

        // Récupérer toutes les versions liées
        $st = $db->prepare(
            'SELECT pv.id FROM programme_versions pv
             JOIN programme_matieres pm ON pv.programme_matiere_id = pm.id
             WHERE pm.programme_id = :id'
        );
        $st->execute(['id' => $id]);
        foreach ($st->fetchAll() as $row) {
            $db->prepare('DELETE FROM programme_items WHERE programme_version_id = :id')
                ->execute(['id' => $row['id']]);
        }

        // La suppression du programme cascade sur programme_matieres
        // qui cascade sur programme_versions (via programme_matiere_id)
        $db->prepare('DELETE FROM programmes WHERE id = :id')->execute(['id' => $id]);
    }

    public function getProgrammeById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT p.*, c.label as cycle_label, c.code as cycle_code
             FROM programmes p
             JOIN cycles c ON p.cycle_id = c.id
             WHERE p.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    // ════════════════════════════════════════════════════════════════
    //  PROGRAMME_MATIERES (niveau 2)
    // ════════════════════════════════════════════════════════════════

    /**
     * Lie une matière à un programme.
     * Retourne l'id de la liaison programme_matiere.
     */
    public function addMatiereToProgamme(int $programmeId, int $matiereId): int
    {
        $st = ConnectionPool::getConnection()->prepare(
            'INSERT INTO programme_matieres (programme_id, matiere_id)
             VALUES (:pid, :mid)
             ON CONFLICT (programme_id, matiere_id) DO UPDATE SET programme_id = EXCLUDED.programme_id
             RETURNING id'
        );
        $st->execute(['pid' => $programmeId, 'mid' => $matiereId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Ajoute une matière créée à la volée et la lie au programme.
     * Retourne ['programme_matiere_id' => ..., 'matiere_id' => ...]
     */
    public function createAndAddMatiere(int $programmeId, string $label, string $code): array
    {
        $matiereId         = $this->createMatiere($label, $code);
        $programmeMatId    = $this->addMatiereToProgamme($programmeId, $matiereId);
        return ['programme_matiere_id' => $programmeMatId, 'matiere_id' => $matiereId];
    }

    public function removeMatiereFromProgramme(int $programmeMatiereId): void
    {
        // Cascade supprime les programme_versions liées
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('SELECT id FROM programme_versions WHERE programme_matiere_id = :id');
        $st->execute(['id' => $programmeMatiereId]);
        foreach ($st->fetchAll() as $row) {
            $db->prepare('DELETE FROM programme_items WHERE programme_version_id = :id')
                ->execute(['id' => $row['id']]);
        }
        $db->prepare('DELETE FROM programme_matieres WHERE id = :id')
            ->execute(['id' => $programmeMatiereId]);
    }

    // ════════════════════════════════════════════════════════════════
    //  PROGRAMME_VERSIONS (niveau 3 = une classe dans une matière)
    // ════════════════════════════════════════════════════════════════

    public function getVersions(int $cycleId, ?int $classeId, int $matiereId): array
    {
        $db = ConnectionPool::getConnection();
        if ($classeId !== null) {
            $st = $db->prepare(
                'SELECT * FROM programme_versions
                 WHERE matiere_id = :m AND cycle_id = :c AND en_vigueur = TRUE
                   AND (classe_id IS NULL OR classe_id = :cl)
                 ORDER BY annee_entree DESC'
            );
            $st->execute(['m' => $matiereId, 'c' => $cycleId, 'cl' => $classeId]);
        } else {
            $st = $db->prepare(
                'SELECT * FROM programme_versions
                 WHERE matiere_id = :m AND cycle_id = :c AND en_vigueur = TRUE
                 ORDER BY annee_entree DESC'
            );
            $st->execute(['m' => $matiereId, 'c' => $cycleId]);
        }
        return $st->fetchAll();
    }

    public function getVersionById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT pv.*, m.label as matiere_label, c.label as cycle_label,
                    p.annee_entree as prog_annee, p.id as programme_id
             FROM programme_versions pv
             JOIN programme_matieres pm ON pv.programme_matiere_id = pm.id
             JOIN matieres m ON pm.matiere_id = m.id
             JOIN programmes p ON pm.programme_id = p.id
             JOIN cycles c ON p.cycle_id = c.id
             WHERE pv.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Crée une version (classe) dans une matière d'un programme.
     * Nécessite programme_matiere_id ou (matiere_id + programme_id).
     */
    public function createVersion(array $data): int
    {
        $db = ConnectionPool::getConnection();

        // Résoudre programme_matiere_id
        $pmId = !empty($data['programme_matiere_id']) ? (int)$data['programme_matiere_id'] : null;

        if (!$pmId) {
            // Fallback : chercher via programme_id + matiere_id
            if (!empty($data['programme_id']) && !empty($data['matiere_id'])) {
                $st = $db->prepare(
                    'SELECT id FROM programme_matieres
                     WHERE programme_id = :pid AND matiere_id = :mid'
                );
                $st->execute(['pid' => (int)$data['programme_id'], 'mid' => (int)$data['matiere_id']]);
                $row = $st->fetch();
                if ($row) {
                    $pmId = (int)$row['id'];
                } else {
                    // Créer la liaison si elle n'existe pas
                    $pmId = $this->addMatiereToProgamme((int)$data['programme_id'], (int)$data['matiere_id']);
                }
            }
        }

        if (!$pmId) {
            throw new \RuntimeException('Impossible de déterminer programme_matiere_id.');
        }

        // Récupérer cycle_id et matiere_id depuis programme_matieres pour la compatibilité
        $st = $db->prepare(
            'SELECT pm.matiere_id, p.cycle_id, p.annee_entree
             FROM programme_matieres pm
             JOIN programmes p ON pm.programme_id = p.id
             WHERE pm.id = :id'
        );
        $st->execute(['id' => $pmId]);
        $pm = $st->fetch();
        if (!$pm) throw new \RuntimeException('programme_matieres introuvable.');

        $enVigueur = filter_var($data['en_vigueur'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        $annee     = !empty($data['annee_entree']) ? (int)$data['annee_entree'] : (int)$pm['annee_entree'];

        $st = $db->prepare('
            INSERT INTO programme_versions
              (programme_matiere_id, matiere_id, cycle_id, classe_id,
               annee_entree, label, en_vigueur, notes, source_url)
            VALUES
              (:pm_id, :matiere_id, :cycle_id, :classe_id,
               :annee_entree, :label, :en_vigueur, :notes, :source_url)
            RETURNING id
        ');
        $st->execute([
            'pm_id'        => $pmId,
            'matiere_id'   => (int)$pm['matiere_id'],
            'cycle_id'     => (int)$pm['cycle_id'],
            'classe_id'    => !empty($data['classe_id']) ? (int)$data['classe_id'] : null,
            'annee_entree' => $annee,
            'label'        => $data['label'],
            'en_vigueur'   => $enVigueur,
            'notes'        => $data['notes'] ?? null,
            'source_url'   => $data['source_url'] ?? null,
        ]);
        return (int)$st->fetchColumn();
    }

    public function patchVersion(int $id, array $data): void
    {
        $allowed = ['label', 'annee_entree', 'notes', 'source_url', 'en_vigueur'];
        $sets = []; $params = ['id' => $id];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[]    = "$k = :$k";
            $params[$k] = ($k === 'en_vigueur')
                ? (($v === true || $v === 'true' || $v === 1) ? 'true' : 'false')
                : $v;
        }
        if (empty($sets)) return;
        ConnectionPool::getConnection()
            ->prepare('UPDATE programme_versions SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);
    }

    public function deleteVersion(int $id): void
    {
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_items WHERE programme_version_id = :id')
            ->execute(['id' => $id]);
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_versions WHERE id = :id')
            ->execute(['id' => $id]);
    }

    // ════════════════════════════════════════════════════════════════
    //  VUE ADMIN — structure complète hiérarchique
    // ════════════════════════════════════════════════════════════════

    /**
     * Retourne la structure complète pour la page admin :
     * programmes[] > matieres[] > classes[]
     * Inclut les programmes sans matières.
     */
    public function getProgrammesAdmin(): array
    {
        $db = ConnectionPool::getConnection();

        // 1. Charger tous les programmes
        $stProg = $db->query(
            'SELECT p.*, c.label as cycle_label, c.code as cycle_code
             FROM programmes p
             JOIN cycles c ON p.cycle_id = c.id
             ORDER BY p.annee_entree DESC, c.id ASC'
        );
        $programmes = $stProg->fetchAll();

        if (empty($programmes)) return [];

        // 2. Charger toutes les matières liées
        $stMat = $db->query(
            'SELECT pm.id as pm_id, pm.programme_id,
                    m.id as matiere_id, m.label as matiere_label, m.code as matiere_code
             FROM programme_matieres pm
             JOIN matieres m ON pm.matiere_id = m.id
             ORDER BY m.label ASC'
        );
        $matieres = $stMat->fetchAll();

        // 3. Charger toutes les versions (classes)
        $stVer = $db->query(
            'SELECT pv.*, cl.label as classe_label, cl.code as classe_code,
                    pm.programme_id
             FROM programme_versions pv
             JOIN programme_matieres pm ON pv.programme_matiere_id = pm.id
             LEFT JOIN classes cl ON pv.classe_id = cl.id
             ORDER BY cl.id ASC NULLS FIRST'
        );
        $versions = $stVer->fetchAll();

        // 4. Indexer versions par pm_id
        $versionsByPm = [];
        foreach ($versions as $v) {
            $pmId = (int)$v['programme_matiere_id'];
            $versionsByPm[$pmId][] = $v;
        }

        // 5. Indexer matières par programme_id
        $matieresByProg = [];
        foreach ($matieres as $m) {
            $pid = (int)$m['programme_id'];
            $matieresByProg[$pid][] = array_merge($m, [
                'classes' => $versionsByPm[(int)$m['pm_id']] ?? [],
            ]);
        }

        // 6. Assembler
        $result = [];
        foreach ($programmes as $prog) {
            $pid      = (int)$prog['id'];
            $result[] = array_merge($prog, [
                'matieres' => $matieresByProg[$pid] ?? [],
            ]);
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    //  ITEMS DU PROGRAMME (compétences / objectifs)
    // ════════════════════════════════════════════════════════════════

    public function getItemsTree(int $versionId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM programme_items
             WHERE programme_version_id = :v ORDER BY niveau, ordre, id'
        );
        $st->execute(['v' => $versionId]);
        return $this->buildTree($st->fetchAll(), null);
    }

    public function getItemsFlat(int $versionId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM programme_items
             WHERE programme_version_id = :v ORDER BY niveau, ordre, id'
        );
        $st->execute(['v' => $versionId]);
        return $st->fetchAll();
    }

    private function buildTree(array $items, ?int $parentId): array
    {
        $result = [];
        foreach ($items as $item) {
            $ip = ($item['parent_id'] === null || $item['parent_id'] === '') ? null : (int)$item['parent_id'];
            if ($ip === $parentId) {
                $item['children'] = $this->buildTree($items, (int)$item['id']);
                $result[] = $item;
            }
        }
        return $result;
    }

    public function saveItem(array $data): int
    {
        $db = ConnectionPool::getConnection();
        if (!empty($data['id'])) {
            $db->prepare(
                'UPDATE programme_items SET label=:l, code=:c, ordre=:o WHERE id=:id'
            )->execute(['l'=>$data['label'],'c'=>$data['code'],'o'=>$data['ordre'],'id'=>$data['id']]);
            return (int)$data['id'];
        }
        $st = $db->prepare(
            'INSERT INTO programme_items (programme_version_id, parent_id, label, code, niveau, ordre)
             VALUES (:v, :p, :l, :c, :n, :o) RETURNING id'
        );
        $st->execute([
            'v' => $data['version_id'],
            'p' => $data['parent_id'] ?: null,
            'l' => $data['label'],
            'c' => $data['code'],
            'n' => $data['niveau'],
            'o' => $data['ordre'] ?? 0,
        ]);
        return (int)$st->fetchColumn();
    }

    public function deleteItem(int $id): void
    {
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_items WHERE id = :id')
            ->execute(['id' => $id]);
    }

    // ════════════════════════════════════════════════════════════════
    //  MÉTHODES PUBLIQUES (utilisées dans les formulaires séquence)
    // ════════════════════════════════════════════════════════════════

    public function getAllVersionsGrouped(): array
    {
        $sql = '
            SELECT pv.*, m.label as matiere_label, c.label as cycle_label,
                   cl.label as classe_label, cl.code as classe_code
            FROM programme_versions pv
            JOIN programme_matieres pm ON pv.programme_matiere_id = pm.id
            JOIN matieres m  ON pm.matiere_id  = m.id
            JOIN programmes p ON pm.programme_id = p.id
            JOIN cycles   c  ON p.cycle_id = c.id
            LEFT JOIN classes cl ON pv.classe_id = cl.id
            WHERE pv.en_vigueur = TRUE
            ORDER BY c.id, m.label, pv.annee_entree DESC
        ';
        $rows = ConnectionPool::getConnection()->query($sql)->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['cycle_label']][$r['matiere_label']][] = $r;
        }
        return $grouped;
    }

    public function toggleVigueur(int $versionId, bool $state): void
    {
        ConnectionPool::getConnection()->prepare(
            'UPDATE programme_versions SET en_vigueur = :s WHERE id = :id'
        )->execute(['s' => $state ? 'true' : 'false', 'id' => $versionId]);
    }

    public function updateVigueur(int $id, bool $state): void
    {
        $this->toggleVigueur($id, $state);
    }
}
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

    public function getCycles(): array
    {
        return ConnectionPool::getConnection()
            ->query('SELECT * FROM cycles ORDER BY id')
            ->fetchAll();
    }

    public function getClassesByCycle(int $cycleId): array
    {
        $st = ConnectionPool::getConnection()->prepare('SELECT * FROM classes WHERE cycle_id = :c ORDER BY id');
        $st->execute(['c' => $cycleId]);
        return $st->fetchAll();
    }

    public function getMatieres(): array
    {
        return ConnectionPool::getConnection()
            ->query('SELECT * FROM matieres ORDER BY label')
            ->fetchAll();
    }

    /**
     * Retourne les matières disponibles pour un cycle et optionnellement une classe.
     */
    public function getMatieresByCycleClasse(?int $cycleId, ?int $classeId): array
    {
        $db = ConnectionPool::getConnection();

        if (!$cycleId) {
            return $this->getMatieres();
        }

        if ($classeId !== null) {
            $sql = 'SELECT DISTINCT m.id, m.label, m.code
                    FROM matieres m
                    JOIN programme_versions pv ON pv.matiere_id = m.id
                    WHERE pv.cycle_id = :c
                      AND pv.en_vigueur = TRUE
                      AND (pv.classe_id IS NULL OR pv.classe_id = :cl)
                    ORDER BY m.label';
            $st = $db->prepare($sql);
            $st->execute(['c' => $cycleId, 'cl' => $classeId]);
        } else {
            $sql = 'SELECT DISTINCT m.id, m.label, m.code
                    FROM matieres m
                    JOIN programme_versions pv ON pv.matiere_id = m.id
                    WHERE pv.cycle_id = :c
                      AND pv.en_vigueur = TRUE
                    ORDER BY m.label';
            $st = $db->prepare($sql);
            $st->execute(['c' => $cycleId]);
        }

        return $st->fetchAll();
    }

    public function getVersions(int $cycleId, ?int $classeId, int $matiereId): array
    {
        $db = ConnectionPool::getConnection();
        if ($classeId !== null) {
            $sql = 'SELECT * FROM programme_versions
                    WHERE matiere_id = :m AND cycle_id = :c AND en_vigueur = TRUE
                    AND (classe_id IS NULL OR classe_id = :cl)
                    ORDER BY annee_entree DESC';
            $st = $db->prepare($sql);
            $st->execute(['m' => $matiereId, 'c' => $cycleId, 'cl' => $classeId]);
        } else {
            $sql = 'SELECT * FROM programme_versions
                    WHERE matiere_id = :m AND cycle_id = :c AND en_vigueur = TRUE
                    ORDER BY annee_entree DESC';
            $st = $db->prepare($sql);
            $st->execute(['m' => $matiereId, 'c' => $cycleId]);
        }
        return $st->fetchAll();
    }

    public function getVersionById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT pv.*, m.label as matiere_label, c.label as cycle_label
             FROM programme_versions pv
             JOIN matieres m ON pv.matiere_id = m.id
             JOIN cycles c ON pv.cycle_id = c.id
             WHERE pv.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Retourne l'arbre complet des items d'un programme
     */
    public function getItemsTree(int $versionId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM programme_items WHERE programme_version_id = :v ORDER BY niveau, ordre, id'
        );
        $st->execute(['v' => $versionId]);
        $all = $st->fetchAll();
        return $this->buildTree($all, null);
    }

    /**
     * Retourne les items à plat (pour les selects)
     */
    public function getItemsFlat(int $versionId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM programme_items WHERE programme_version_id = :v ORDER BY niveau, ordre, id'
        );
        $st->execute(['v' => $versionId]);
        return $st->fetchAll();
    }

    private function buildTree(array $items, ?int $parentId): array
    {
        $result = [];
        foreach ($items as $item) {
            $itemParent = $item['parent_id'] === null || $item['parent_id'] === '' ? null : (int)$item['parent_id'];
            if ($itemParent === $parentId) {
                $item['children'] = $this->buildTree($items, (int)$item['id']);
                $result[] = $item;
            }
        }
        return $result;
    }

    public function getAllVersionsGrouped(): array
    {
        $sql = '
            SELECT pv.*, m.label as matiere_label, c.label as cycle_label, cl.label as classe_label, cl.code as classe_code
            FROM programme_versions pv
            JOIN matieres m ON pv.matiere_id = m.id
            JOIN cycles c ON pv.cycle_id = c.id
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

    // ── Méthodes admin ──────────────────────────────────────────────

    /**
     * Retourne TOUTES les versions (en vigueur ou non) pour l'admin,
     * triées par : cycle > année desc > matière alpha > classe.
     */
    public function getAllVersionsAdmin(): array
    {
        $sql = '
            SELECT
                pv.id, pv.en_vigueur, pv.annee_entree, pv.label, pv.notes, pv.source_url,
                pv.matiere_id, m.label AS matiere_label, m.code AS matiere_code,
                pv.cycle_id,  c.label AS cycle_label,   c.code AS cycle_code,
                pv.classe_id, cl.label AS classe_label,  cl.code AS classe_code
            FROM programme_versions pv
            JOIN matieres m  ON pv.matiere_id = m.id
            JOIN cycles   c  ON pv.cycle_id   = c.id
            LEFT JOIN classes cl ON pv.classe_id = cl.id
            ORDER BY c.id ASC, pv.annee_entree DESC, m.label ASC, cl.id ASC NULLS FIRST
        ';
        return ConnectionPool::getConnection()->query($sql)->fetchAll();
    }

    /**
     * Sauvegarde un ou plusieurs champs de programme_versions (PATCH partiel).
     * Champs autorisés : label, annee_entree, notes, source_url, en_vigueur
     */
    public function patchVersion(int $id, array $data): void
    {
        $allowed = ['label', 'annee_entree', 'notes', 'source_url', 'en_vigueur'];
        $sets    = [];
        $params  = ['id' => $id];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[]    = "$k = :$k";
            $params[$k] = ($k === 'en_vigueur') ? ($v === true || $v === 'true' || $v === 1 ? 'true' : 'false') : $v;
        }
        if (empty($sets)) return;
        ConnectionPool::getConnection()
            ->prepare('UPDATE programme_versions SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);
    }

    /**
     * Crée une nouvelle version de programme.
     */
    public function createVersion(array $data): int
    {
        $st = ConnectionPool::getConnection()->prepare('
            INSERT INTO programme_versions
              (matiere_id, cycle_id, classe_id, annee_entree, label, en_vigueur, notes, source_url)
            VALUES
              (:matiere_id, :cycle_id, :classe_id, :annee_entree, :label, :en_vigueur, :notes, :source_url)
            RETURNING id
        ');
        $enVigueur = filter_var($data['en_vigueur'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        $st->execute([
            'matiere_id'   => (int)$data['matiere_id'],
            'cycle_id'     => (int)$data['cycle_id'],
            'classe_id'    => !empty($data['classe_id']) ? (int)$data['classe_id'] : null,
            'annee_entree' => (int)$data['annee_entree'],
            'label'        => $data['label'],
            'en_vigueur'   => $enVigueur,
            'notes'        => $data['notes'] ?? null,
            'source_url'   => $data['source_url'] ?? null,
        ]);
        return (int)$st->fetchColumn();
    }

    /**
     * Supprime une version de programme et tous ses items.
     */
    public function deleteVersion(int $id): void
    {
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_items WHERE programme_version_id = :id')
            ->execute(['id' => $id]);
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_versions WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /**
     * Sauvegarde un champ de la table matieres (label ou code).
     */
    public function patchMatiere(int $id, array $data): void
    {
        $allowed = ['label', 'code'];
        $sets    = [];
        $params  = ['id' => $id];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[]    = "$k = :$k";
            $params[$k] = $v;
        }
        if (empty($sets)) return;
        ConnectionPool::getConnection()
            ->prepare('UPDATE matieres SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);
    }

    // ── Méthodes items programme ────────────────────────────────────

    public function saveItem(array $data): int
    {
        $db = ConnectionPool::getConnection();
        if (!empty($data['id'])) {
            $st = $db->prepare('UPDATE programme_items SET label = :l, code = :c, ordre = :o WHERE id = :id');
            $st->execute(['l' => $data['label'], 'c' => $data['code'], 'o' => $data['ordre'], 'id' => $data['id']]);
            return (int)$data['id'];
        } else {
            $st = $db->prepare('INSERT INTO programme_items (programme_version_id, parent_id, label, code, niveau, ordre)
                            VALUES (:v, :p, :l, :c, :n, :o) RETURNING id');
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
    }

    public function deleteItem(int $id): void
    {
        ConnectionPool::getConnection()
            ->prepare('DELETE FROM programme_items WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function toggleVigueur(int $versionId, bool $state): void
    {
        $st = ConnectionPool::getConnection()->prepare('UPDATE programme_versions SET en_vigueur = :s WHERE id = :id');
        $st->execute(['s' => $state ? 'true' : 'false', 'id' => $versionId]);
    }

    public function getAllVersionsByYear(): array
    {
        $sql = 'SELECT pv.*, m.label as matiere_label, c.label as cycle_label, cl.label as classe_label
            FROM programme_versions pv
            JOIN matieres m ON pv.matiere_id = m.id
            JOIN cycles c ON pv.cycle_id = c.id
            LEFT JOIN classes cl ON pv.classe_id = cl.id
            ORDER BY pv.annee_entree DESC, m.label ASC, cl.id ASC';
        $rows = ConnectionPool::getConnection()->query($sql)->fetchAll();

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['annee_entree']][$r['matiere_label']][] = $r;
        }
        return $grouped;
    }

    public function updateVigueur(int $id, bool $state): void
    {
        $st = ConnectionPool::getConnection()->prepare('UPDATE programme_versions SET en_vigueur = :s WHERE id = :id');
        $st->execute(['s' => $state ? 'true' : 'false', 'id' => $id]);
    }
}
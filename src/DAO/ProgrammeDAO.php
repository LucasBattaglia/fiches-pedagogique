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
     * Filtre via la table programme_versions (seules les matières ayant un programme
     * en vigueur pour ce cycle/classe sont retournées).
     */
    public function getMatieresByCycleClasse(?int $cycleId, ?int $classeId): array
    {
        $db = ConnectionPool::getConnection();

        // Sans cycle → toutes les matières
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
        // Si classeId fourni : versions du cycle + versions spécifiques à cette classe
        // Si classeId NULL : toutes les versions du cycle
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

    // Dans ProgrammeDAO.php

    public function saveItem(array $data): int {
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
                'o' => $data['ordre'] ?? 0
            ]);
            return (int)$st->fetchColumn();
        }
    }

    public function deleteItem(int $id): void {
        $st = ConnectionPool::getConnection()->prepare('DELETE FROM programme_items WHERE id = :id');
        $st->execute(['id' => $id]);
    }

    public function toggleVigueur(int $versionId, bool $state): void {
        $st = ConnectionPool::getConnection()->prepare('UPDATE programme_versions SET en_vigueur = :s WHERE id = :id');
        $st->execute(['s' => $state ? 1 : 0, 'id' => $versionId]);
    }

    // Dans ProgrammeDAO.php

    public function getAllVersionsByYear(): array {
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

    public function updateVigueur(int $id, bool $state): void {
        $st = ConnectionPool::getConnection()->prepare('UPDATE programme_versions SET en_vigueur = :s WHERE id = :id');
        $st->execute(['s' => $state ? 'true' : 'false', 'id' => $id]);
    }
}
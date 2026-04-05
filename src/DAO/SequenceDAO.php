<?php
// src/DAO/SequenceDAO.php

namespace src\DAO;

class SequenceDAO
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    // ---- SEQUENCES ----

    public function create(int $userId, array $data): int
    {
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('
            INSERT INTO sequences
              (user_id, titre, domaine, matiere_id, cycle_id, classe_id, programme_version_id,
               nb_seances, tache_finale, objectifs_generaux, materiel, is_public)
            VALUES
              (:user_id, :titre, :domaine, :matiere_id, :cycle_id, :classe_id, :programme_version_id,
               :nb_seances, :tache_finale, :objectifs_generaux, :materiel, :is_public)
            RETURNING id
        ');
        $st->execute([
            'user_id'              => $userId,
            'titre'                => $data['titre'],
            'domaine'              => $data['domaine'] ?? null,
            'matiere_id'           => isset($data['matiere_id'])   && $data['matiere_id']   !== '' ? (int)$data['matiere_id']   : null,
            'cycle_id'             => isset($data['cycle_id'])     && $data['cycle_id']     !== '' ? (int)$data['cycle_id']     : null,
            'classe_id'            => isset($data['classe_id'])    && $data['classe_id']    !== '' ? (int)$data['classe_id']    : null,
            'programme_version_id' => isset($data['programme_version_id']) && $data['programme_version_id'] !== '' ? (int)$data['programme_version_id'] : null,
            'nb_seances'           => isset($data['nb_seances'])   && $data['nb_seances']   !== '' ? (int)$data['nb_seances']   : 1,
            'tache_finale'         => $data['tache_finale'] ?? null,
            'objectifs_generaux'   => $data['objectifs_generaux'] ?? null,
            'materiel'             => $data['materiel'] ?? null,
            'is_public'            => !empty($data['is_public']) ? 'true' : 'false',
        ]);
        $id = (int)$st->fetchColumn();

        // Lier les items de programme
        if (!empty($data['programme_items'])) {
            $this->syncProgrammeItems($id, $data['programme_items']);
        }

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $db = ConnectionPool::getConnection();
        $db->prepare('
            UPDATE sequences SET
              titre=:titre, domaine=:domaine, matiere_id=:matiere_id, cycle_id=:cycle_id,
              classe_id=:classe_id, programme_version_id=:programme_version_id,
              nb_seances=:nb_seances, tache_finale=:tache_finale,
              objectifs_generaux=:objectifs_generaux, materiel=:materiel,
              is_public=:is_public, updated_at=NOW()
            WHERE id=:id
        ')->execute([
            'titre'                => $data['titre'],
            'domaine'              => $data['domaine'] ?? null,
            'matiere_id'           => isset($data['matiere_id'])   && $data['matiere_id']   !== '' ? (int)$data['matiere_id']   : null,
            'cycle_id'             => isset($data['cycle_id'])     && $data['cycle_id']     !== '' ? (int)$data['cycle_id']     : null,
            'classe_id'            => isset($data['classe_id'])    && $data['classe_id']    !== '' ? (int)$data['classe_id']    : null,
            'programme_version_id' => isset($data['programme_version_id']) && $data['programme_version_id'] !== '' ? (int)$data['programme_version_id'] : null,
            'nb_seances'           => isset($data['nb_seances'])   && $data['nb_seances']   !== '' ? (int)$data['nb_seances']   : 1,
            'tache_finale'         => $data['tache_finale'] ?? null,
            'objectifs_generaux'   => $data['objectifs_generaux'] ?? null,
            'materiel'             => $data['materiel'] ?? null,
            'is_public'            => !empty($data['is_public']) ? 'true' : 'false',
            'id'                   => $id,
        ]);

        if (isset($data['programme_items'])) {
            $this->syncProgrammeItems($id, $data['programme_items']);
        }

        if (isset($data['comportements_remediations'])) {
            $db->prepare('UPDATE sequences SET comportements_remediations=:cr WHERE id=:id')
                ->execute(['cr' => json_encode($data['comportements_remediations']), 'id' => $id]);
        }
    }

    public function delete(int $id, int $userId): bool
    {
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('DELETE FROM sequences WHERE id=:id AND user_id=:uid RETURNING id');
        $st->execute(['id' => $id, 'uid' => $userId]);
        return (bool)$st->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT s.*, u.nom, u.prenom, u.avatar_url,
                   m.label as matiere_label, m.code as matiere_code,
                   c.label as cycle_label, c.code as cycle_code,
                   cl.label as classe_label, cl.code as classe_code,
                   pv.label as programme_label, pv.annee_entree, pv.notes as programme_notes
            FROM sequences s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN matieres m ON s.matiere_id = m.id
            LEFT JOIN cycles c ON s.cycle_id = c.id
            LEFT JOIN classes cl ON s.classe_id = cl.id
            LEFT JOIN programme_versions pv ON s.programme_version_id = pv.id
            WHERE s.id = :id
        ');
        $st->execute(['id' => $id]);
        $seq = $st->fetch();
        if (!$seq) return null;

        // Items du programme liés
        $seq['programme_items'] = $this->getProgrammeItemIds($id);
        return $seq;
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT s.id, s.titre, s.is_public, s.created_at, s.updated_at, s.nb_seances,
                   m.label as matiere_label, c.label as cycle_label, cl.label as classe_label,
                   pv.annee_entree,
                   (SELECT COUNT(*) FROM seances WHERE sequence_id = s.id) as nb_seances_reelles
            FROM sequences s
            LEFT JOIN matieres m ON s.matiere_id = m.id
            LEFT JOIN cycles c ON s.cycle_id = c.id
            LEFT JOIN classes cl ON s.classe_id = cl.id
            LEFT JOIN programme_versions pv ON s.programme_version_id = pv.id
            WHERE s.user_id = :uid
            ORDER BY s.updated_at DESC
            LIMIT :lim OFFSET :off
        ');
        $st->execute(['uid' => $userId, 'lim' => $limit, 'off' => $offset]);
        return $st->fetchAll();
    }

    public function findPublic(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = ['s.is_public = TRUE'];
        $params = [];

        if (!empty($filters['cycle_id'])) {
            $where[] = 's.cycle_id = :cycle_id';
            $params['cycle_id'] = $filters['cycle_id'];
        }
        if (!empty($filters['classe_id'])) {
            $where[] = 's.classe_id = :classe_id';
            $params['classe_id'] = $filters['classe_id'];
        }
        if (!empty($filters['matiere_id'])) {
            $where[] = 's.matiere_id = :matiere_id';
            $params['matiere_id'] = $filters['matiere_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(s.titre ILIKE :search OR s.objectifs_generaux ILIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $st = ConnectionPool::getConnection()->prepare("
            SELECT s.id, s.titre, s.is_public, s.created_at, s.nb_seances,
                   u.nom, u.prenom, u.avatar_url,
                   m.label as matiere_label, c.label as cycle_label, cl.label as classe_label,
                   pv.annee_entree,
                   (SELECT COUNT(*) FROM seances WHERE sequence_id = s.id) as nb_seances_reelles
            FROM sequences s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN matieres m ON s.matiere_id = m.id
            LEFT JOIN cycles c ON s.cycle_id = c.id
            LEFT JOIN classes cl ON s.classe_id = cl.id
            LEFT JOIN programme_versions pv ON s.programme_version_id = pv.id
            WHERE $whereStr
            ORDER BY s.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        $params['lim'] = $limit;
        $params['off'] = $offset;
        $st->execute($params);
        return $st->fetchAll();
    }

    public function canAccess(int $sequenceId, ?int $userId): bool
    {
        $seq = $this->findById($sequenceId);
        if (!$seq) return false;
        if ($seq['is_public']) return true;
        return $userId && $seq['user_id'] === $userId;
    }

    public function isOwner(int $sequenceId, int $userId): bool
    {
        $st = ConnectionPool::getConnection()->prepare('SELECT id FROM sequences WHERE id=:id AND user_id=:uid');
        $st->execute(['id' => $sequenceId, 'uid' => $userId]);
        return (bool)$st->fetchColumn();
    }

    public function fork(int $sequenceId, int $newUserId): int
    {
        $seq = $this->findById($sequenceId);
        if (!$seq) throw new \RuntimeException("Séquence introuvable");

        $db = ConnectionPool::getConnection();
        // Copier la séquence
        $newSeqId = $this->create($newUserId, array_merge($seq, [
            'titre'       => $seq['titre'] . ' (copie)',
            'is_public'   => false,
            'forked_from' => $sequenceId,
        ]));

        // Copier les séances
        $seances = SeanceDAO::getInstance()->findBySequence($sequenceId);
        foreach ($seances as $seance) {
            $newSeanceId = SeanceDAO::getInstance()->create($newSeqId, $seance);
            // Copier les situations
            $situations = SituationDAO::getInstance()->findBySeance($seance['id']);
            foreach ($situations as $sit) {
                SituationDAO::getInstance()->create($newSeanceId, $sit);
            }
        }

        return $newSeqId;
    }

    // ---- PROGRAMME ITEMS ----

    private function syncProgrammeItems(int $sequenceId, array $itemIds): void
    {
        $db = ConnectionPool::getConnection();
        $db->prepare('DELETE FROM sequence_programme_items WHERE sequence_id = :sid')
            ->execute(['sid' => $sequenceId]);
        if (empty($itemIds)) return;
        $st = $db->prepare('INSERT INTO sequence_programme_items (sequence_id, programme_item_id) VALUES (:sid, :iid) ON CONFLICT DO NOTHING');
        foreach ($itemIds as $iid) {
            $st->execute(['sid' => $sequenceId, 'iid' => (int)$iid]);
        }
    }

    public function getProgrammeItemIds(int $sequenceId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT programme_item_id FROM sequence_programme_items WHERE sequence_id = :sid'
        );
        $st->execute(['sid' => $sequenceId]);
        return array_column($st->fetchAll(), 'programme_item_id');
    }

    // ---- FAVORIS ----

    public function addFavori(int $userId, int $sequenceId): void
    {
        ConnectionPool::getConnection()->prepare(
            'INSERT INTO favoris (user_id, sequence_id) VALUES (:u, :s) ON CONFLICT DO NOTHING'
        )->execute(['u' => $userId, 's' => $sequenceId]);
    }

    public function removeFavori(int $userId, int $sequenceId): void
    {
        ConnectionPool::getConnection()->prepare(
            'DELETE FROM favoris WHERE user_id=:u AND sequence_id=:s'
        )->execute(['u' => $userId, 's' => $sequenceId]);
    }

    public function isFavori(int $userId, int $sequenceId): bool
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT 1 FROM favoris WHERE user_id=:u AND sequence_id=:s'
        );
        $st->execute(['u' => $userId, 's' => $sequenceId]);
        return (bool)$st->fetchColumn();
    }
}
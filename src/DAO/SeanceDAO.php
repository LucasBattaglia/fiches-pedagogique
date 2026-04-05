<?php
// src/DAO/SeanceDAO.php

namespace src\DAO;

class SeanceDAO
{
    private static ?self $instance = null;
    public static function getInstance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    // sequence_id peut être NULL (séance autonome)
    public function create(?int $sequenceId, array $data): int
    {
        $db  = ConnectionPool::getConnection();
        $num = $data['numero'] ?? ($sequenceId ? $this->nextNumero($sequenceId) : 1);
        $st  = $db->prepare('
            INSERT INTO seances
              (sequence_id, numero, titre, champ_apprentissage, competence_visee, afc,
               objectif_general, objectif_intermediaire, duree, materiel,
               deroulement, criteres_realisation, criteres_reussite,
               variables_didactiques, comportements_remediations)
            VALUES
              (:sequence_id, :numero, :titre, :champ_apprentissage, :competence_visee, :afc,
               :objectif_general, :objectif_intermediaire, :duree, :materiel,
               :deroulement, :criteres_realisation, :criteres_reussite,
               :variables_didactiques, :comportements_remediations)
            RETURNING id
        ');
        $st->execute([
            'sequence_id'              => $sequenceId,
            'numero'                   => $num,
            'titre'                    => $data['titre'],
            'champ_apprentissage'      => $data['champ_apprentissage']    ?? null,
            'competence_visee'         => $data['competence_visee']       ?? null,
            'afc'                      => $data['afc']                    ?? null,
            'objectif_general'         => $data['objectif_general']       ?? null,
            'objectif_intermediaire'   => $data['objectif_intermediaire'] ?? null,
            'duree'                    => ($data['duree'] !== '' && $data['duree'] !== null) ? (int)$data['duree'] : null,
            'materiel'                 => $data['materiel']               ?? null,
            'deroulement'              => json_encode($data['deroulement'] ?? []),
            'criteres_realisation'     => $data['criteres_realisation']   ?? null,
            'criteres_reussite'        => $data['criteres_reussite']      ?? null,
            'variables_didactiques'    => $data['variables_didactiques']  ?? null,
            'comportements_remediations' => json_encode($data['comportements_remediations'] ?? []),
        ]);
        return (int)$st->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        ConnectionPool::getConnection()->prepare('
            UPDATE seances SET
              titre=:titre, champ_apprentissage=:champ_apprentissage, competence_visee=:competence_visee,
              afc=:afc, objectif_general=:objectif_general, objectif_intermediaire=:objectif_intermediaire,
              duree=:duree, materiel=:materiel, deroulement=:deroulement,
              criteres_realisation=:criteres_realisation, criteres_reussite=:criteres_reussite,
              variables_didactiques=:variables_didactiques, comportements_remediations=:comportements_remediations,
              updated_at=NOW()
            WHERE id=:id
        ')->execute([
            'titre'                    => $data['titre'],
            'champ_apprentissage'      => $data['champ_apprentissage']    ?? null,
            'competence_visee'         => $data['competence_visee']       ?? null,
            'afc'                      => $data['afc']                    ?? null,
            'objectif_general'         => $data['objectif_general']       ?? null,
            'objectif_intermediaire'   => $data['objectif_intermediaire'] ?? null,
            'duree'                    => ($data['duree'] !== '' && $data['duree'] !== null) ? (int)$data['duree'] : null,
            'materiel'                 => $data['materiel']               ?? null,
            'deroulement'              => json_encode($data['deroulement'] ?? []),
            'criteres_realisation'     => $data['criteres_realisation']   ?? null,
            'criteres_reussite'        => $data['criteres_reussite']      ?? null,
            'variables_didactiques'    => $data['variables_didactiques']  ?? null,
            'comportements_remediations' => json_encode($data['comportements_remediations'] ?? []),
            'id'                       => $id,
        ]);
    }

    // Attacher une séance autonome à une séquence
    public function attachToSequence(int $seanceId, int $sequenceId): void
    {
        $num = $this->nextNumero($sequenceId);
        ConnectionPool::getConnection()->prepare(
            'UPDATE seances SET sequence_id=:sid, numero=:num WHERE id=:id'
        )->execute(['sid' => $sequenceId, 'num' => $num, 'id' => $seanceId]);
    }

    // Détacher une séance de sa séquence (la rendre autonome)
    public function detachFromSequence(int $seanceId): void
    {
        ConnectionPool::getConnection()->prepare(
            'UPDATE seances SET sequence_id=NULL WHERE id=:id'
        )->execute(['id' => $seanceId]);
    }

    public function delete(int $id): void
    {
        ConnectionPool::getConnection()->prepare('DELETE FROM seances WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('SELECT * FROM seances WHERE id=:id');
        $st->execute(['id' => $id]);
        $r = $st->fetch();
        if (!$r) return null;
        $r['deroulement']               = json_decode($r['deroulement']               ?? '[]', true) ?? [];
        $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        return $r;
    }

    public function findBySequence(int $sequenceId): array
    {
        // Utilise la table N-N sequence_seances (supporte séance dans plusieurs séquences)
        // Fallback sur l'ancienne colonne sequence_id si la table n'existe pas encore
        try {
            $st = ConnectionPool::getConnection()->prepare('
                SELECT s.*, ss.numero as position_in_seq
                FROM   seances s
                JOIN   sequence_seances ss ON ss.seance_id = s.id
                WHERE  ss.sequence_id = :sid
                ORDER  BY ss.numero ASC
            ');
            $st->execute(['sid' => $sequenceId]);
            $rows = $st->fetchAll();
            // Si la table existe mais est vide pour cette séquence,
            // on tente aussi l'ancienne colonne (migration pas encore faite)
            if (empty($rows)) {
                $st2 = ConnectionPool::getConnection()->prepare(
                    'SELECT * FROM seances WHERE sequence_id=:sid ORDER BY numero'
                );
                $st2->execute(['sid' => $sequenceId]);
                $rows = $st2->fetchAll();
            }
            return $this->decodeRows($rows);
        } catch (\PDOException $e) {
            // Table sequence_seances n'existe pas encore — fallback
            $st = ConnectionPool::getConnection()->prepare(
                'SELECT * FROM seances WHERE sequence_id=:sid ORDER BY numero'
            );
            $st->execute(['sid' => $sequenceId]);
            return $this->decodeRows($st->fetchAll());
        }
    }

    // Séances autonomes d'un utilisateur (sans séquence)
    public function findAutonomousByUser(int $userId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT s.*
            FROM   seances s
            JOIN   sequences seq ON seq.user_id = :uid
            WHERE  s.sequence_id IS NULL
            UNION
            SELECT s.*
            FROM   seances s
            WHERE  s.sequence_id IS NULL
              AND  NOT EXISTS (SELECT 1 FROM sequences WHERE user_id = :uid2)
            ORDER  BY updated_at DESC
        ');
        // Requête simplifiée : toutes les séances sans sequence_id
        // (en prod on ajouterait un user_id sur seances)
        $st2 = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM seances WHERE sequence_id IS NULL ORDER BY updated_at DESC'
        );
        $st2->execute();
        return $this->decodeRows($st2->fetchAll());
    }

    // Toutes les séances (pour liste globale)
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT s.*, seq.titre as sequence_titre
             FROM seances s
             LEFT JOIN sequences seq ON s.sequence_id = seq.id
             ORDER BY s.updated_at DESC
             LIMIT :lim OFFSET :off'
        );
        $st->execute(['lim' => $limit, 'off' => $offset]);
        return $this->decodeRows($st->fetchAll());
    }

    public function reorder(int $sequenceId, array $orderedIds): void
    {
        $db = ConnectionPool::getConnection();
        foreach ($orderedIds as $num => $id) {
            // Mettre à jour dans la table N-N
            try {
                $db->prepare(
                    'UPDATE sequence_seances SET numero = :n WHERE seance_id = :id AND sequence_id = :sid'
                )->execute(['n' => $num + 1, 'id' => $id, 'sid' => $sequenceId]);
            } catch (\PDOException $e) {}
            // Fallback ancienne colonne
            $db->prepare('UPDATE seances SET numero=:n WHERE id=:id AND sequence_id=:sid')
                ->execute(['n' => $num + 1, 'id' => $id, 'sid' => $sequenceId]);
        }
    }

    public function setPositionInSequence(int $seanceId, int $sequenceId, int $position): void
    {
        $db = ConnectionPool::getConnection();
        try {
            $db->prepare(
                'UPDATE sequence_seances SET numero = :pos WHERE seance_id = :sea AND sequence_id = :seq'
            )->execute(['pos' => $position, 'sea' => $seanceId, 'seq' => $sequenceId]);
        } catch (\PDOException $e) {}
        // Fallback
        $db->prepare('UPDATE seances SET numero = :pos WHERE id = :id AND sequence_id = :sid')
            ->execute(['pos' => $position, 'id' => $seanceId, 'sid' => $sequenceId]);
    }

    private function decodeRows(array $rows): array
    {
        foreach ($rows as &$r) {
            $r['deroulement']               = json_decode($r['deroulement']               ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        return $rows;
    }

    private function nextNumero(?int $sequenceId): int
    {
        if (!$sequenceId) return 1;
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT COALESCE(MAX(numero), 0) + 1 FROM seances WHERE sequence_id=:sid'
        );
        $st->execute(['sid' => $sequenceId]);
        return (int)$st->fetchColumn();
    }

    // ── Méthodes liaison N-N sequence_seances ──────────────

    public function getSequenceIds(int $seanceId): array
    {
        $st = \src\DAO\ConnectionPool::getConnection()->prepare(
            'SELECT sequence_id FROM sequence_seances WHERE seance_id = :sid ORDER BY numero'
        );
        $st->execute(['sid' => $seanceId]);
        return array_column($st->fetchAll(), 'sequence_id');
    }

    public function linkToSequence(int $seanceId, int $sequenceId): void
    {
        $db  = ConnectionPool::getConnection();
        // Numéro = DERNIER (ajouter à la fin)
        $st  = $db->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM sequence_seances WHERE sequence_id = :sid');
        $st->execute(['sid' => $sequenceId]);
        $num = (int)$st->fetchColumn();
        // Insérer dans la table N-N
        $db->prepare(
            'INSERT INTO sequence_seances (sequence_id, seance_id, numero)
             VALUES (:seq, :sea, :num)
             ON CONFLICT (sequence_id, seance_id) DO NOTHING'
        )->execute(['seq' => $sequenceId, 'sea' => $seanceId, 'num' => $num]);
        // Mettre à jour la colonne legacy sequence_id si encore NULL
        $db->prepare(
            'UPDATE seances SET sequence_id = :seq WHERE id = :id AND sequence_id IS NULL'
        )->execute(['seq' => $sequenceId, 'id' => $seanceId]);
    }

    public function unlinkFromSequence(int $seanceId, int $sequenceId): void
    {
        \src\DAO\ConnectionPool::getConnection()->prepare(
            'DELETE FROM sequence_seances WHERE seance_id=:sea AND sequence_id=:seq'
        )->execute(['sea' => $seanceId, 'seq' => $sequenceId]);
        // Si c'était la séquence principale, effacer le lien direct
        \src\DAO\ConnectionPool::getConnection()->prepare(
            'UPDATE seances SET sequence_id=NULL WHERE id=:id AND sequence_id=:seq'
        )->execute(['id' => $seanceId, 'seq' => $sequenceId]);
    }

    public function findBySequenceNtoN(int $sequenceId): array
    {
        $st = \src\DAO\ConnectionPool::getConnection()->prepare('
            SELECT s.*
            FROM   seances s
            JOIN   sequence_seances ss ON ss.seance_id = s.id
            WHERE  ss.sequence_id = :sid
            ORDER  BY ss.numero
        ');
        $st->execute(['sid' => $sequenceId]);
        return $this->decodeRows($st->fetchAll());
    }

}
<?php
// src/DAO/SituationDAO.php  ← VERSION COMPLÈTE avec user_id autonome

namespace src\DAO;

class SituationDAO
{
    private static ?self $instance = null;
    public static function getInstance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function create(?int $seanceId, array $data, ?int $userId = null): int
    {
        $db  = ConnectionPool::getConnection();
        $num = $data['numero'] ?? ($seanceId ? $this->nextNumero($seanceId) : 1);

        // Récupérer user_id depuis la séance si non fourni
        if ($userId === null && $seanceId) {
            $st = $db->prepare('SELECT user_id FROM seances WHERE id = :id');
            $st->execute(['id' => $seanceId]);
            $userId = (int)($st->fetchColumn() ?: 0) ?: null;
        }

        $st = $db->prepare('
            INSERT INTO situations
              (seance_id, user_id, numero, titre, champ_apprentissage, duree, afc,
               objectif_moteur, objectif_socio_affectif, objectif_cognitif, materiel,
               but, dispositif, organisation, fonctionnement, consignes_base,
               variables_evolution, criteres_realisation, criteres_reussite,
               comportements_remediations)
            VALUES
              (:seance_id, :user_id, :numero, :titre, :champ_apprentissage, :duree, :afc,
               :objectif_moteur, :objectif_socio_affectif, :objectif_cognitif, :materiel,
               :but, :dispositif, :organisation, :fonctionnement, :consignes_base,
               :variables_evolution, :criteres_realisation, :criteres_reussite,
               :comportements_remediations)
            RETURNING id
        ');
        $st->execute([
            'seance_id'                => $seanceId,
            'user_id'                  => $userId,
            'numero'                   => $num,
            'titre'                    => $data['titre'],
            'champ_apprentissage'      => $data['champ_apprentissage'] ?? null,
            'duree'                    => ($data['duree'] !== '' && $data['duree'] !== null) ? (int)$data['duree'] : null,
            'afc'                      => $data['afc'] ?? null,
            'objectif_moteur'          => $data['objectif_moteur'] ?? null,
            'objectif_socio_affectif'  => $data['objectif_socio_affectif'] ?? null,
            'objectif_cognitif'        => $data['objectif_cognitif'] ?? null,
            'materiel'                 => $data['materiel'] ?? null,
            'but'                      => $data['but'] ?? null,
            'dispositif'               => $data['dispositif'] ?? null,
            'organisation'             => $data['organisation'] ?? null,
            'fonctionnement'           => $data['fonctionnement'] ?? null,
            'consignes_base'           => $data['consignes_base'] ?? null,
            'variables_evolution'      => json_encode($data['variables_evolution'] ?? []),
            'criteres_realisation'     => $data['criteres_realisation'] ?? null,
            'criteres_reussite'        => $data['criteres_reussite'] ?? null,
            'comportements_remediations' => json_encode($data['comportements_remediations'] ?? []),
        ]);
        return (int)$st->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        ConnectionPool::getConnection()->prepare('
            UPDATE situations SET
              titre=:titre, champ_apprentissage=:champ_apprentissage, duree=:duree, afc=:afc,
              objectif_moteur=:objectif_moteur, objectif_socio_affectif=:objectif_socio_affectif,
              objectif_cognitif=:objectif_cognitif, materiel=:materiel,
              but=:but, dispositif=:dispositif, organisation=:organisation,
              fonctionnement=:fonctionnement, consignes_base=:consignes_base,
              variables_evolution=:variables_evolution, criteres_realisation=:criteres_realisation,
              criteres_reussite=:criteres_reussite, comportements_remediations=:comportements_remediations,
              updated_at=NOW()
            WHERE id=:id
        ')->execute([
            'titre'                    => $data['titre'],
            'champ_apprentissage'      => $data['champ_apprentissage'] ?? null,
            'duree'                    => ($data['duree'] !== '' && $data['duree'] !== null) ? (int)$data['duree'] : null,
            'afc'                      => $data['afc'] ?? null,
            'objectif_moteur'          => $data['objectif_moteur'] ?? null,
            'objectif_socio_affectif'  => $data['objectif_socio_affectif'] ?? null,
            'objectif_cognitif'        => $data['objectif_cognitif'] ?? null,
            'materiel'                 => $data['materiel'] ?? null,
            'but'                      => $data['but'] ?? null,
            'dispositif'               => $data['dispositif'] ?? null,
            'organisation'             => $data['organisation'] ?? null,
            'fonctionnement'           => $data['fonctionnement'] ?? null,
            'consignes_base'           => $data['consignes_base'] ?? null,
            'variables_evolution'      => json_encode($data['variables_evolution'] ?? []),
            'criteres_realisation'     => $data['criteres_realisation'] ?? null,
            'criteres_reussite'        => $data['criteres_reussite'] ?? null,
            'comportements_remediations' => json_encode($data['comportements_remediations'] ?? []),
            'id'                       => $id,
        ]);
    }

    /** Vérifie ownership : via user_id direct, ou via séance, ou via séquence */
    public function isOwner(int $situationId, int $userId): bool
    {
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('SELECT user_id, seance_id FROM situations WHERE id = :id');
        $st->execute(['id' => $situationId]);
        $row = $st->fetch();
        if (!$row) return false;

        // Propriétaire direct
        if ((int)($row['user_id'] ?? 0) === $userId) return true;

        // Via séance
        if ($row['seance_id']) {
            return \src\DAO\SeanceDAO::getInstance()->isOwner((int)$row['seance_id'], $userId);
        }
        return false;
    }

    public function delete(int $id): void
    {
        ConnectionPool::getConnection()->prepare('DELETE FROM situations WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('SELECT * FROM situations WHERE id=:id');
        $st->execute(['id' => $id]);
        $r = $st->fetch();
        if (!$r) return null;
        $r['variables_evolution']        = json_decode($r['variables_evolution'] ?? '[]', true) ?? [];
        $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        return $r;
    }

    public function findBySeance(int $seanceId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT * FROM situations WHERE seance_id=:sid ORDER BY numero'
        );
        $st->execute(['sid' => $seanceId]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['variables_evolution']        = json_decode($r['variables_evolution'] ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        return $rows;
    }

    /** Situations autonomes (sans séance parente) d'un utilisateur */
    public function findAutonomousByUser(int $userId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT * FROM situations
            WHERE seance_id IS NULL
              AND user_id = :uid
            ORDER BY updated_at DESC
        ');
        $st->execute(['uid' => $userId]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['variables_evolution']        = json_decode($r['variables_evolution'] ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        return $rows;
    }

    /** Toutes les situations d'un utilisateur */
    public function findByUser(int $userId, int $limit = 100): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sit.*,
                   s.titre as seance_titre, s.numero as seance_numero,
                   seq.titre as sequence_titre
            FROM situations sit
            LEFT JOIN seances s ON sit.seance_id = s.id
            LEFT JOIN sequences seq ON s.sequence_id = seq.id
            WHERE sit.user_id = :uid
               OR s.user_id = :uid2
               OR seq.user_id = :uid3
            GROUP BY sit.id, s.titre, s.numero, seq.titre
            ORDER BY sit.updated_at DESC
            LIMIT :lim
        ');
        $st->execute(['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId, 'lim' => $limit]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['variables_evolution']        = json_decode($r['variables_evolution'] ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        return $rows;
    }

    private function nextNumero(int $seanceId): int
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT COALESCE(MAX(numero), 0) + 1 FROM situations WHERE seance_id=:sid'
        );
        $st->execute(['sid' => $seanceId]);
        return (int)$st->fetchColumn();
    }
}
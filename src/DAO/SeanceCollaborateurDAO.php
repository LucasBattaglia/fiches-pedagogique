<?php
// src/DAO/SeanceCollaborateurDAO.php
// Gestion des collaborateurs sur les séances ET situations
// (même logique que CollaborateurDAO mais pour les entités séance/situation)

namespace src\DAO;

class SeanceCollaborateurDAO
{
    private static ?self $instance = null;
    public static function getInstance(): self
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    // ══════════════════════════════════════════════════════════
    //  SÉANCES
    // ══════════════════════════════════════════════════════════

    /** Vérifie si l'utilisateur peut éditer la séance (propriétaire, collab direct, ou collab de la séquence parente) */
    public function canEdit(int $seanceId, int $userId): bool
    {
        $db = ConnectionPool::getConnection();

        // 1. Propriétaire direct de la séance
        $st = $db->prepare('SELECT user_id, sequence_id FROM seances WHERE id = :id');
        $st->execute(['id' => $seanceId]);
        $row = $st->fetch();
        if (!$row) return false;
        if ((int)($row['user_id'] ?? 0) === $userId) return true;

        // 2. Collaborateur accepté sur la séance
        $st2 = $db->prepare(
            'SELECT id FROM seance_collaborateurs
             WHERE seance_id = :sid AND user_id = :uid AND accepted_at IS NOT NULL'
        );
        $st2->execute(['sid' => $seanceId, 'uid' => $userId]);
        if ($st2->fetchColumn()) return true;

        // 3. Héritage : collaborateur de la séquence parente
        if ($row['sequence_id']) {
            try {
                return \src\DAO\CollaborateurDAO::getInstance()->canEdit(
                    (int)$row['sequence_id'], $userId
                );
            } catch (\Throwable $e) {
                // CollaborateurDAO pas encore disponible
                $st3 = $db->prepare(
                    'SELECT id FROM sequence_collaborateurs
                     WHERE sequence_id = :sid AND user_id = :uid AND accepted_at IS NOT NULL'
                );
                $st3->execute(['sid' => $row['sequence_id'], 'uid' => $userId]);
                if ($st3->fetchColumn()) return true;
            }
        }

        return false;
    }

    /** Vérifie si l'utilisateur est propriétaire de la séance */
    public function isOwner(int $seanceId, int $userId): bool
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT id FROM seances WHERE id = :id AND user_id = :uid'
        );
        $st->execute(['id' => $seanceId, 'uid' => $userId]);
        return (bool)$st->fetchColumn();
    }

    /** Retourne tous les collaborateurs acceptés + invitations en attente d'une séance */
    public function getCollaborateurs(int $seanceId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*, u.nom, u.prenom, u.email, u.avatar_url
            FROM seance_collaborateurs sc
            LEFT JOIN users u ON sc.user_id = u.id
            WHERE sc.seance_id = :sid
            ORDER BY sc.role DESC, sc.accepted_at ASC NULLS LAST, sc.created_at ASC
        ');
        $st->execute(['sid' => $seanceId]);
        return $st->fetchAll();
    }

    /** Retourne les collaborateurs hérités de la séquence parente */
    public function getCollaborateursHeritesDeLaSequence(int $seanceId): array
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT sequence_id FROM seances WHERE id = :id'
        );
        $st->execute(['id' => $seanceId]);
        $seqId = $st->fetchColumn();
        if (!$seqId) return [];

        $st2 = ConnectionPool::getConnection()->prepare('
            SELECT sc.*, u.nom, u.prenom, u.email, u.avatar_url
            FROM sequence_collaborateurs sc
            LEFT JOIN users u ON sc.user_id = u.id
            WHERE sc.sequence_id = :sid AND sc.accepted_at IS NOT NULL
            ORDER BY sc.role DESC, sc.accepted_at ASC
        ');
        $st2->execute(['sid' => $seqId]);
        return $st2->fetchAll();
    }

    /** Crée un token d'invitation pour une séance */
    public function createInvitation(int $seanceId, int $invitedBy): string
    {
        $db = ConnectionPool::getConnection();
        // Invalider les anciennes invitations en attente
        $db->prepare(
            'DELETE FROM seance_collaborateurs
             WHERE seance_id = :sid AND accepted_at IS NULL AND invitation_token IS NOT NULL'
        )->execute(['sid' => $seanceId]);

        $token = bin2hex(random_bytes(32));
        $db->prepare('
            INSERT INTO seance_collaborateurs (seance_id, invitation_token, invited_by, expires_at)
            VALUES (:sid, :tok, :by, NOW() + INTERVAL \'7 days\')
        ')->execute(['sid' => $seanceId, 'tok' => $token, 'by' => $invitedBy]);
        return $token;
    }

    /** Trouve une invitation par token */
    public function findInvitationByToken(string $token): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*, s.titre as seance_titre, u.nom as inviteur_nom, u.prenom as inviteur_prenom
            FROM seance_collaborateurs sc
            JOIN seances s ON sc.seance_id = s.id
            LEFT JOIN users u ON sc.invited_by = u.id
            WHERE sc.invitation_token = :tok
              AND sc.accepted_at IS NULL
              AND sc.expires_at > NOW()
        ');
        $st->execute(['tok' => $token]);
        return $st->fetch() ?: null;
    }

    /** Accepte une invitation */
    public function acceptInvitation(string $token, int $userId): bool
    {
        $db = ConnectionPool::getConnection();
        $inv = $this->findInvitationByToken($token);
        if (!$inv) return false;

        // Vérifier que l'utilisateur n'est pas déjà collaborateur
        $st = $db->prepare(
            'SELECT id FROM seance_collaborateurs
             WHERE seance_id = :sid AND user_id = :uid AND accepted_at IS NOT NULL'
        );
        $st->execute(['sid' => $inv['seance_id'], 'uid' => $userId]);
        if ($st->fetchColumn()) return true; // déjà membre

        $db->prepare('
            UPDATE seance_collaborateurs
            SET user_id = :uid, accepted_at = NOW(), role = \'editeur\', invitation_token = NULL
            WHERE id = :id
        ')->execute(['uid' => $userId, 'id' => $inv['id']]);
        return true;
    }

    /** S'assurer que le propriétaire a une entrée dans seance_collaborateurs */
    public function ensureOwnerEntry(int $seanceId, int $userId): void
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT id FROM seance_collaborateurs
             WHERE seance_id = :sid AND user_id = :uid AND role = \'proprietaire\''
        );
        $st->execute(['sid' => $seanceId, 'uid' => $userId]);
        if (!$st->fetchColumn()) {
            ConnectionPool::getConnection()->prepare('
                INSERT INTO seance_collaborateurs (seance_id, user_id, role, accepted_at)
                VALUES (:sid, :uid, \'proprietaire\', NOW())
                ON CONFLICT DO NOTHING
            ')->execute(['sid' => $seanceId, 'uid' => $userId]);
        }
    }

    /** Retire un collaborateur d'une séance */
    public function removeCollaborateur(int $seanceId, int $userId): void
    {
        ConnectionPool::getConnection()->prepare(
            'DELETE FROM seance_collaborateurs
             WHERE seance_id = :sid AND user_id = :uid AND role != \'proprietaire\''
        )->execute(['sid' => $seanceId, 'uid' => $userId]);
    }

    /** Révoque une invitation en attente */
    public function revokeInvitation(int $rowId): void
    {
        ConnectionPool::getConnection()->prepare(
            'DELETE FROM seance_collaborateurs WHERE id = :id AND accepted_at IS NULL'
        )->execute(['id' => $rowId]);
    }

    // ══════════════════════════════════════════════════════════
    //  SITUATIONS
    // ══════════════════════════════════════════════════════════

    /** Vérifie si l'utilisateur peut éditer la situation */
    public function canEditSituation(int $situationId, int $userId): bool
    {
        $db = ConnectionPool::getConnection();

        // 1. Propriétaire direct
        $st = $db->prepare('SELECT user_id, seance_id FROM situations WHERE id = :id');
        $st->execute(['id' => $situationId]);
        $row = $st->fetch();
        if (!$row) return false;
        if ((int)($row['user_id'] ?? 0) === $userId) return true;

        // 2. Collaborateur accepté sur la situation
        $st2 = $db->prepare(
            'SELECT id FROM situation_collaborateurs
             WHERE situation_id = :sid AND user_id = :uid AND accepted_at IS NOT NULL'
        );
        $st2->execute(['sid' => $situationId, 'uid' => $userId]);
        if ($st2->fetchColumn()) return true;

        // 3. Héritage : collaborateur de la séance parente
        if ($row['seance_id']) {
            if ($this->canEdit((int)$row['seance_id'], $userId)) return true;
        }

        return false;
    }

    /** Retourne tous les collaborateurs d'une situation */
    public function getCollaborateursSituation(int $situationId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*, u.nom, u.prenom, u.email, u.avatar_url
            FROM situation_collaborateurs sc
            LEFT JOIN users u ON sc.user_id = u.id
            WHERE sc.situation_id = :sid
            ORDER BY sc.role DESC, sc.accepted_at ASC NULLS LAST
        ');
        $st->execute(['sid' => $situationId]);
        return $st->fetchAll();
    }

    /** Crée un token d'invitation pour une situation */
    public function createInvitationSituation(int $situationId, int $invitedBy): string
    {
        $db = ConnectionPool::getConnection();
        $db->prepare(
            'DELETE FROM situation_collaborateurs
             WHERE situation_id = :sid AND accepted_at IS NULL AND invitation_token IS NOT NULL'
        )->execute(['sid' => $situationId]);

        $token = bin2hex(random_bytes(32));
        $db->prepare('
            INSERT INTO situation_collaborateurs (situation_id, invitation_token, invited_by, expires_at)
            VALUES (:sid, :tok, :by, NOW() + INTERVAL \'7 days\')
        ')->execute(['sid' => $situationId, 'tok' => $token, 'by' => $invitedBy]);
        return $token;
    }

    /** Trouve une invitation situation par token */
    public function findInvitationSituationByToken(string $token): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*, s.titre as situation_titre, u.nom as inviteur_nom, u.prenom as inviteur_prenom
            FROM situation_collaborateurs sc
            JOIN situations s ON sc.situation_id = s.id
            LEFT JOIN users u ON sc.invited_by = u.id
            WHERE sc.invitation_token = :tok
              AND sc.accepted_at IS NULL
              AND sc.expires_at > NOW()
        ');
        $st->execute(['tok' => $token]);
        return $st->fetch() ?: null;
    }

    /** Accepte une invitation situation */
    public function acceptInvitationSituation(string $token, int $userId): bool
    {
        $inv = $this->findInvitationSituationByToken($token);
        if (!$inv) return false;

        ConnectionPool::getConnection()->prepare('
            UPDATE situation_collaborateurs
            SET user_id = :uid, accepted_at = NOW(), role = \'editeur\', invitation_token = NULL
            WHERE id = :id
        ')->execute(['uid' => $userId, 'id' => $inv['id']]);
        return true;
    }

    /** Retire un collaborateur d'une situation */
    public function removeCollaborateurSituation(int $situationId, int $userId): void
    {
        ConnectionPool::getConnection()->prepare(
            'DELETE FROM situation_collaborateurs
             WHERE situation_id = :sid AND user_id = :uid AND role != \'proprietaire\''
        )->execute(['sid' => $situationId, 'uid' => $userId]);
    }

    /** S'assurer que le propriétaire a une entrée dans situation_collaborateurs */
    public function ensureOwnerEntrySituation(int $situationId, int $userId): void
    {
        $st = ConnectionPool::getConnection()->prepare(
            'SELECT id FROM situation_collaborateurs
             WHERE situation_id = :sid AND user_id = :uid AND role = \'proprietaire\''
        );
        $st->execute(['sid' => $situationId, 'uid' => $userId]);
        if (!$st->fetchColumn()) {
            ConnectionPool::getConnection()->prepare('
                INSERT INTO situation_collaborateurs (situation_id, user_id, role, accepted_at)
                VALUES (:sid, :uid, \'proprietaire\', NOW())
                ON CONFLICT DO NOTHING
            ')->execute(['sid' => $situationId, 'uid' => $userId]);
        }
    }
}
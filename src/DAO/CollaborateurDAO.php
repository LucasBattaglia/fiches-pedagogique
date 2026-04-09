<?php
// src/DAO/CollaborateurDAO.php

namespace src\DAO;

/**
 * Gestion du co-enseignement sur les séquences.
 *
 * Rôles :
 *  - proprietaire  : droits complets + suppression + invitation
 *  - collaborateur : lecture + édition (séances, situations, modifications séquence)
 *
 * Flux d'invitation :
 *  1. Le propriétaire génère un lien via createInvitation()
 *  2. Le lien contient un token unique (64 hex chars)
 *  3. Le destinataire clique → acceptInvitation()
 *  4. Si l'utilisateur n'existe pas encore → il est redirigé vers /auth/register
 *     avec le token en session, puis acceptation automatique après inscription
 */
class CollaborateurDAO
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    // ════════════════════════════════════════════════════════════════
    //  VÉRIFICATIONS DE DROITS
    // ════════════════════════════════════════════════════════════════

    /**
     * Retourne true si l'utilisateur peut ÉDITER la séquence
     * (propriétaire ou collaborateur accepté).
     */
    public function canEdit(int $sequenceId, ?int $userId): bool
    {
        if (!$userId) return false;
        $st = ConnectionPool::getConnection()->prepare('
            SELECT 1 FROM sequence_collaborateurs
            WHERE sequence_id = :sid
              AND user_id     = :uid
              AND accepted_at IS NOT NULL
        ');
        $st->execute(['sid' => $sequenceId, 'uid' => $userId]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Retourne true si l'utilisateur est propriétaire de la séquence.
     */
    public function isOwner(int $sequenceId, int $userId): bool
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT 1 FROM sequence_collaborateurs
            WHERE sequence_id = :sid
              AND user_id     = :uid
              AND role        = \'proprietaire\'
              AND accepted_at IS NOT NULL
        ');
        $st->execute(['sid' => $sequenceId, 'uid' => $userId]);
        return (bool)$st->fetchColumn();
    }

    // ════════════════════════════════════════════════════════════════
    //  LISTER LES COLLABORATEURS
    // ════════════════════════════════════════════════════════════════

    /**
     * Retourne tous les membres (acceptés + invitations en attente)
     * d'une séquence, avec les infos utilisateur.
     */
    public function findBySequence(int $sequenceId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*,
                   u.nom, u.prenom, u.email, u.avatar_url,
                   inv.nom as inviter_nom, inv.prenom as inviter_prenom
            FROM   sequence_collaborateurs sc
            LEFT JOIN users u   ON sc.user_id     = u.id
            LEFT JOIN users inv ON sc.invited_by   = inv.id
            WHERE  sc.sequence_id = :sid
            ORDER  BY sc.role DESC, sc.accepted_at ASC NULLS LAST, sc.created_at ASC
        ');
        $st->execute(['sid' => $sequenceId]);
        return $st->fetchAll();
    }

    /**
     * Retourne les séquences partagées avec un utilisateur
     * (dont il est collaborateur, pas propriétaire).
     */
    public function findSharedWithUser(int $userId): array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT s.id, s.titre, s.updated_at, s.is_public,
                   u.nom, u.prenom,
                   m.label as matiere_label, c.label as cycle_label, cl.label as classe_label,
                   sc.role, sc.accepted_at,
                   (SELECT COUNT(*) FROM seances WHERE sequence_id = s.id) as nb_seances_reelles
            FROM   sequence_collaborateurs sc
            JOIN   sequences s ON sc.sequence_id = s.id
            JOIN   users u     ON s.user_id       = u.id
            LEFT JOIN matieres m  ON s.matiere_id = m.id
            LEFT JOIN cycles   c  ON s.cycle_id   = c.id
            LEFT JOIN classes  cl ON s.classe_id  = cl.id
            WHERE  sc.user_id    = :uid
              AND  sc.role       = \'collaborateur\'
              AND  sc.accepted_at IS NOT NULL
            ORDER BY s.updated_at DESC
        ');
        $st->execute(['uid' => $userId]);
        return $st->fetchAll();
    }

    // ════════════════════════════════════════════════════════════════
    //  INVITATIONS
    // ════════════════════════════════════════════════════════════════

    /**
     * Crée une invitation et retourne le token.
     * Si l'utilisateur est déjà membre, retourne le token existant
     * ou null si déjà accepté.
     */
    public function createInvitation(int $sequenceId, int $invitedBy): string
    {
        $db    = ConnectionPool::getConnection();
        $token = bin2hex(random_bytes(32)); // 64 chars hex

        // Vérifier qu'il n'y a pas déjà un token actif pour cette séquence
        // (on peut régénérer un lien universel par séquence)
        // Supprime l'ancien token non accepté s'il existe
        $db->prepare('
            DELETE FROM sequence_collaborateurs
            WHERE sequence_id  = :sid
              AND accepted_at  IS NULL
              AND user_id      IS NULL
              AND token_invitation IS NOT NULL
        ')->execute(['sid' => $sequenceId]);

        $db->prepare('
            INSERT INTO sequence_collaborateurs
              (sequence_id, user_id, role, invited_by, token_invitation)
            VALUES
              (:sid, NULL, \'collaborateur\', :inv, :token)
        ')->execute([
            'sid'   => $sequenceId,
            'inv'   => $invitedBy,
            'token' => $token,
        ]);

        return $token;
    }

    /**
     * Récupère les infos d'une invitation par token.
     * Retourne null si le token est invalide ou déjà utilisé.
     */
    public function findByToken(string $token): ?array
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT sc.*,
                   s.titre as sequence_titre, s.id as sequence_id,
                   u.nom as inviter_nom, u.prenom as inviter_prenom
            FROM   sequence_collaborateurs sc
            JOIN   sequences s ON sc.sequence_id = s.id
            LEFT JOIN users u  ON sc.invited_by  = u.id
            WHERE  sc.token_invitation = :token
              AND  sc.accepted_at      IS NULL
        ');
        $st->execute(['token' => $token]);
        return $st->fetch() ?: null;
    }

    /**
     * Accepte une invitation : lie l'utilisateur au token, marque comme accepté.
     * Retourne false si le token est invalide ou si l'utilisateur est déjà membre.
     */
    public function acceptInvitation(string $token, int $userId): bool
    {
        $invitation = $this->findByToken($token);
        if (!$invitation) return false;

        $sequenceId = (int)$invitation['sequence_id'];

        // L'utilisateur est-il déjà membre de cette séquence ?
        $st = ConnectionPool::getConnection()->prepare('
            SELECT id FROM sequence_collaborateurs
            WHERE sequence_id = :sid AND user_id = :uid
        ');
        $st->execute(['sid' => $sequenceId, 'uid' => $userId]);
        if ($st->fetchColumn()) {
            // Déjà membre : supprimer le token orphelin
            ConnectionPool::getConnection()->prepare('
                DELETE FROM sequence_collaborateurs
                WHERE token_invitation = :token AND user_id IS NULL
            ')->execute(['token' => $token]);
            return true; // Considéré comme succès
        }

        // Mettre à jour le token avec le user_id et la date d'acceptation
        $st = ConnectionPool::getConnection()->prepare('
            UPDATE sequence_collaborateurs
            SET user_id          = :uid,
                accepted_at      = NOW(),
                token_invitation = NULL
            WHERE token_invitation = :token
              AND accepted_at      IS NULL
        ');
        $st->execute(['uid' => $userId, 'token' => $token]);
        return $st->rowCount() > 0;
    }

    // ════════════════════════════════════════════════════════════════
    //  GESTION DES MEMBRES
    // ════════════════════════════════════════════════════════════════

    /**
     * Retire un collaborateur d'une séquence.
     * Un propriétaire ne peut pas être retiré (sauf transfert).
     */
    public function removeCollaborateur(int $sequenceId, int $userId): bool
    {
        $st = ConnectionPool::getConnection()->prepare('
            DELETE FROM sequence_collaborateurs
            WHERE sequence_id = :sid
              AND user_id     = :uid
              AND role        = \'collaborateur\'
        ');
        $st->execute(['sid' => $sequenceId, 'uid' => $userId]);
        return $st->rowCount() > 0;
    }

    /**
     * Annule une invitation en attente (token non encore accepté).
     */
    public function revokeInvitation(int $collaborateurRowId): bool
    {
        $st = ConnectionPool::getConnection()->prepare('
            DELETE FROM sequence_collaborateurs
            WHERE id           = :id
              AND accepted_at  IS NULL
        ');
        $st->execute(['id' => $collaborateurRowId]);
        return $st->rowCount() > 0;
    }

    // ════════════════════════════════════════════════════════════════
    //  TRANSFERT DE PROPRIÉTÉ
    // ════════════════════════════════════════════════════════════════

    /**
     * Transfère la propriété à un collaborateur existant.
     * L'ancien propriétaire devient collaborateur.
     */
    public function transferOwnership(int $sequenceId, int $newOwnerId, int $oldOwnerId): bool
    {
        $db = ConnectionPool::getConnection();

        // Vérifier que le nouveau propriétaire est bien collaborateur accepté
        $st = $db->prepare('
            SELECT id FROM sequence_collaborateurs
            WHERE sequence_id = :sid AND user_id = :uid AND accepted_at IS NOT NULL
        ');
        $st->execute(['sid' => $sequenceId, 'uid' => $newOwnerId]);
        if (!$st->fetchColumn()) return false;

        $db->beginTransaction();
        try {
            // Rétrograder l'ancien propriétaire
            $db->prepare('
                UPDATE sequence_collaborateurs
                SET role = \'collaborateur\'
                WHERE sequence_id = :sid AND user_id = :old AND role = \'proprietaire\'
            ')->execute(['sid' => $sequenceId, 'old' => $oldOwnerId]);

            // Promouvoir le nouveau
            $db->prepare('
                UPDATE sequence_collaborateurs
                SET role = \'proprietaire\'
                WHERE sequence_id = :sid AND user_id = :new
            ')->execute(['sid' => $sequenceId, 'new' => $newOwnerId]);

            // Mettre à jour sequences.user_id (référence rapide)
            $db->prepare('
                UPDATE sequences SET user_id = :new WHERE id = :sid
            ')->execute(['new' => $newOwnerId, 'sid' => $sequenceId]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Appelé quand le propriétaire supprime sa séquence.
     * Si des collaborateurs existent, transfère au premier collaborateur
     * trouvé et retourne son ID. Sinon retourne null (suppression normale).
     */
    public function handleOwnerDeletion(int $sequenceId, int $ownerId): ?int
    {
        $st = ConnectionPool::getConnection()->prepare('
            SELECT user_id FROM sequence_collaborateurs
            WHERE sequence_id = :sid
              AND role        = \'collaborateur\'
              AND accepted_at IS NOT NULL
              AND user_id     IS NOT NULL
            ORDER BY accepted_at ASC
            LIMIT 1
        ');
        $st->execute(['sid' => $sequenceId]);
        $newOwnerId = $st->fetchColumn();

        if (!$newOwnerId) return null; // Pas de collaborateur → suppression normale

        $this->transferOwnership($sequenceId, (int)$newOwnerId, $ownerId);
        return (int)$newOwnerId;
    }

    // ════════════════════════════════════════════════════════════════
    //  SYNCHRONISATION INITIALE
    // ════════════════════════════════════════════════════════════════

    /**
     * S'assure que le propriétaire d'une séquence est bien dans la table.
     * Utile pour les séquences créées avant la migration.
     */
    public function ensureOwnerEntry(int $sequenceId, int $userId): void
    {
        ConnectionPool::getConnection()->prepare('
            INSERT INTO sequence_collaborateurs (sequence_id, user_id, role, accepted_at)
            VALUES (:sid, :uid, \'proprietaire\', NOW())
            ON CONFLICT (sequence_id, user_id) DO NOTHING
        ')->execute(['sid' => $sequenceId, 'uid' => $userId]);
    }
}

<?php
/**
 * resources/views/partials/collaborateurs.php
 *
 * Panneau de gestion des collaborateurs d'une séquence.
 * Variables attendues :
 *  - $sequence      : array  — données de la séquence
 *  - $collaborateurs: array  — résultat de CollaborateurDAO::findBySequence()
 *  - $isOwner       : bool   — l'utilisateur courant est propriétaire
 *  - $currentUserId : ?int   — ID de l'utilisateur courant
 *  - $inviteToken   : ?string — token d'invitation actif (si généré)
 */

use src\Service\AuthService;

$inviteUrl = null;
if (!empty($inviteToken)) {
    $inviteUrl = (getenv('APP_URL') ?: '') . '/sequence/invitation/' . $inviteToken;
}
?>

<div class="fiche-section mb-24" id="section-collaborateurs">
    <div class="fiche-section__title" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span>👥 Co-enseignants
            <span style="font-size:.78rem;font-weight:400;color:var(--gris-500);margin-left:6px">(<?= count(array_filter($collaborateurs, fn($c) => $c['accepted_at'])) ?> membre(s) actif(s))</span>
        </span>
        <?php if ($isOwner): ?>
            <button type="button" class="btn btn--outline btn--sm" data-modal-open="modal-invite">
                🔗 Inviter un co-enseignant
            </button>
        <?php endif; ?>
    </div>
    <div class="fiche-section__body" style="padding:0">

        <?php if (empty($collaborateurs)): ?>
            <div style="padding:20px;text-align:center;color:var(--gris-500);font-size:.88rem">
                Aucun collaborateur pour l'instant.
            </div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:.86rem">
                <thead>
                <tr>
                    <th style="padding:8px 16px;text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--gris-500);background:var(--gris-50);border-bottom:1px solid var(--gris-300)">Enseignant</th>
                    <th style="padding:8px 16px;text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--gris-500);background:var(--gris-50);border-bottom:1px solid var(--gris-300)">Rôle</th>
                    <th style="padding:8px 16px;text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--gris-500);background:var(--gris-50);border-bottom:1px solid var(--gris-300)">Statut</th>
                    <?php if ($isOwner): ?>
                        <th style="padding:8px 16px;text-align:right;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--gris-500);background:var(--gris-50);border-bottom:1px solid var(--gris-300)">Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($collaborateurs as $collab): ?>
                    <?php
                    $isPending  = empty($collab['accepted_at']);
                    $isSelf     = $collab['user_id'] == $currentUserId;
                    $isThisOwner = $collab['role'] === 'proprietaire';
                    $displayName = $isPending
                        ? ('Invitation en attente' . ($collab['email_invite'] ? ' — ' . htmlspecialchars($collab['email_invite']) : ''))
                        : htmlspecialchars(($collab['prenom'] ?? '') . ' ' . ($collab['nom'] ?? ''));
                    ?>
                    <tr style="border-bottom:1px solid var(--gris-100);<?= $isPending ? 'opacity:.65' : '' ?>">
                        <td style="padding:10px 16px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <?php if (!$isPending): ?>
                                    <div style="width:32px;height:32px;border-radius:50%;background:<?= $isThisOwner ? 'var(--bleu-med)' : 'var(--vert)' ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:.78rem;font-weight:700;flex-shrink:0;overflow:hidden">
                                        <?php if (!empty($collab['avatar_url'])): ?>
                                            <img src="<?= htmlspecialchars($collab['avatar_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                                        <?php else: ?>
                                            <?= strtoupper(mb_substr($collab['prenom'] ?? 'U', 0, 1)) . strtoupper(mb_substr($collab['nom'] ?? '', 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--gris-200);display:flex;align-items:center;justify-content:center;color:var(--gris-500);font-size:.88rem;flex-shrink:0">⏳</div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:<?= $isPending ? '400' : '600' ?>;color:<?= $isPending ? 'var(--gris-500)' : 'var(--gris-900)' ?>">
                                        <?= $displayName ?>
                                        <?php if ($isSelf && !$isPending): ?>
                                            <span style="font-size:.72rem;color:var(--gris-500);margin-left:4px">(vous)</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$isPending && !empty($collab['email'])): ?>
                                        <div style="font-size:.75rem;color:var(--gris-500)"><?= htmlspecialchars($collab['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="padding:10px 16px">
                            <?php if ($isThisOwner): ?>
                                <span class="badge badge--bleu">👑 Propriétaire</span>
                            <?php else: ?>
                                <span class="badge badge--vert">✏️ Collaborateur</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 16px">
                            <?php if ($isPending): ?>
                                <span class="badge badge--ambre">⏳ En attente</span>
                            <?php else: ?>
                                <span class="badge badge--vert">✅ Actif</span>
                            <?php endif; ?>
                            <?php if (!$isPending && $collab['accepted_at']): ?>
                                <div style="font-size:.72rem;color:var(--gris-500);margin-top:2px">
                                    Depuis le <?= date('d/m/Y', strtotime($collab['accepted_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php if ($isOwner): ?>
                            <td style="padding:10px 16px;text-align:right">
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                                    <?php if ($isPending): ?>
                                        <!-- Annuler l'invitation en attente -->
                                        <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/collaborateur/<?= $collab['id'] ?>/revoquer" method="post" style="display:inline">
                                            <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--gris-500)"
                                                    data-confirm="Annuler cette invitation ?">✕ Annuler</button>
                                        </form>
                                    <?php elseif (!$isThisOwner): ?>
                                        <!-- Transférer la propriété -->
                                        <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/collaborateur/<?= $collab['user_id'] ?>/transferer" method="post" style="display:inline">
                                            <button type="submit" class="btn btn--ghost btn--sm"
                                                    data-confirm="Transférer la propriété à <?= htmlspecialchars($displayName) ?> ? Vous deviendrez collaborateur.">
                                                👑 Propriétaire
                                            </button>
                                        </form>
                                        <!-- Retirer le collaborateur -->
                                        <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/collaborateur/<?= $collab['user_id'] ?>/retirer" method="post" style="display:inline">
                                            <button type="submit" class="btn btn--danger btn--sm"
                                                    data-confirm="Retirer <?= htmlspecialchars($displayName) ?> de cette séquence ?">
                                                🗑 Retirer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ MODAL INVITATION ═══════════════════════════════════════ -->
<?php if ($isOwner): ?>
<div class="modal-overlay" id="modal-invite">
    <div class="modal" style="max-width:560px">
        <div class="modal__header">
            <h3>🔗 Inviter un co-enseignant</h3>
            <button class="btn btn--ghost btn--sm" data-modal-close>✕</button>
        </div>
        <div class="modal__body">

            <?php if ($inviteUrl): ?>
                <!-- Lien déjà généré -->
                <div class="alert alert--info" style="margin-bottom:16px">
                    <div>
                        <strong>Lien d'invitation actif</strong><br>
                        <span class="text-sm">Partagez ce lien avec votre collègue. Il est valable jusqu'à utilisation.</span>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
                    <input type="text" id="invite-url-input" value="<?= htmlspecialchars($inviteUrl) ?>"
                           readonly style="flex:1;font-size:.82rem;background:var(--gris-50);cursor:text">
                    <button type="button" class="btn btn--outline btn--sm" onclick="copyInviteUrl()" id="btn-copy">
                        📋 Copier
                    </button>
                </div>
                <div class="alert alert--warn" style="font-size:.82rem">
                    ⚠️ Ce lien donne accès en édition à toute personne qui le reçoit. Ne le partagez qu'avec des collègues de confiance.
                </div>
                <div style="margin-top:16px;text-align:center">
                    <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/invitation/regenerer" method="post" style="display:inline">
                        <button type="submit" class="btn btn--ghost btn--sm"
                                data-confirm="Régénérer le lien rendra l'ancien invalide. Continuer ?">
                            🔄 Régénérer un nouveau lien
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Pas encore de lien -->
                <p style="margin-bottom:20px;color:var(--gris-700)">
                    Générez un lien d'invitation à partager avec votre collègue.
                    Toute personne possédant ce lien pourra rejoindre la séquence en tant que <strong>collaborateur</strong>.
                </p>
                <div class="alert alert--info" style="font-size:.82rem;margin-bottom:20px">
                    💡 Le collaborateur devra avoir (ou créer) un compte pour accepter l'invitation.
                    Il aura accès en lecture et édition à toutes les séances et situations de la séquence.
                </div>
                <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/invitation/generer" method="post" style="text-align:center">
                    <button type="submit" class="btn btn--primary">
                        🔗 Générer le lien d'invitation
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-modal-close>Fermer</button>
        </div>
    </div>
</div>

<script>
function copyInviteUrl() {
    const input = document.getElementById('invite-url-input');
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.getElementById('btn-copy');
            btn.textContent = '✅ Copié !';
            setTimeout(() => btn.textContent = '📋 Copier', 2000);
        });
    } catch(e) {
        document.execCommand('copy');
        const btn = document.getElementById('btn-copy');
        btn.textContent = '✅ Copié !';
        setTimeout(() => btn.textContent = '📋 Copier', 2000);
    }
}
</script>
<?php endif; ?>

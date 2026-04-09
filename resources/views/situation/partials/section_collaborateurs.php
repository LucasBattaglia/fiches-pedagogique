<?php
/**
 * PATCH resources/views/situation/show.php
 *
 * 1. Insérer ce bloc dans la colonne GAUCHE, après les "Comportements"
 *    et avant include layout_end.
 *
 * 2. La route /situation/{id}/show doit passer ces variables :
 *    $collaborateurs → SeanceCollaborateurDAO::getCollaborateursSituation($sit['id'])
 *    $isOwner        → (int)($sit['user_id'] ?? 0) === currentUserId()
 *    $inviteToken    → $_GET['invite_token'] ?? null
 */
?>

<!-- ═══ SECTION COLLABORATEURS SITUATION ════════════════════════════ -->
<div class="fiche-section mb-24" id="section-collaborateurs">
    <div class="fiche-section__title" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span>👥 Collaborateurs
            <span class="badge badge--ambre" style="margin-left:6px"><?= count($collaborateurs) ?></span>
        </span>
        <?php if ($isOwner): ?>
            <form action="<?= $base ?>/situation/<?= $situation['id'] ?>/invitation/generer" method="post" style="display:inline">
                <button type="submit" class="btn btn--ambre btn--sm">
                    🔗 Générer un lien
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="fiche-section__body">

        <!-- Lien d'invitation affiché après génération -->
        <?php
        $inviteToken = $_GET['invite_token'] ?? null;
        if ($inviteToken && $isOwner):
            $inviteUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                . $base . '/situation/invitation/' . $inviteToken;
            ?>
            <div class="alert alert--info" style="margin-bottom:16px">
                <div style="flex:1">
                    <strong>🔗 Lien d'invitation</strong>
                    <div style="margin-top:6px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <input type="text" value="<?= htmlspecialchars($inviteUrl) ?>"
                               id="invite-url-sit" readonly
                               style="flex:1;min-width:200px;font-size:.8rem;padding:6px 10px;border:1px solid var(--ambre);border-radius:var(--rayon);background:white">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('invite-url-sit').value);this.textContent='✅ Copié !';setTimeout(()=>this.textContent='📋 Copier',2000)"
                                class="btn btn--outline btn--sm">📋 Copier</button>
                    </div>
                    <p class="text-sm text-muted" style="margin-top:6px">⏱ Valide 7 jours</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($collaborateurs)): ?>
            <div style="display:flex;flex-direction:column;gap:8px">
                <?php foreach ($collaborateurs as $collab): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--gris-50);border-radius:var(--rayon);border:1px solid var(--gris-100)">
                        <div style="width:34px;height:34px;border-radius:50%;background:<?= $collab['role']==='proprietaire'?'var(--ambre)':'var(--vert)' ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:.8rem;font-weight:700;flex-shrink:0;overflow:hidden">
                            <?php if (!empty($collab['avatar_url']) && $collab['accepted_at']): ?>
                                <img src="<?= htmlspecialchars($collab['avatar_url']) ?>" style="width:100%;height:100%;object-fit:cover">
                            <?php elseif ($collab['accepted_at']): ?>
                                <?= strtoupper(mb_substr($collab['prenom']??'?',0,1).mb_substr($collab['nom']??'',0,1)) ?>
                            <?php else: ?>✉<?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <?php if ($collab['accepted_at']): ?>
                                <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars(($collab['prenom']??'').' '.($collab['nom']??'')) ?></div>
                                <div class="text-sm text-muted"><?= htmlspecialchars($collab['email']??'') ?></div>
                            <?php else: ?>
                                <div style="font-weight:600;font-size:.88rem;color:var(--gris-500)">Invitation en attente</div>
                                <div class="text-sm text-muted">Expire le <?= date('d/m/Y', strtotime($collab['expires_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?= $collab['role']==='proprietaire'?'badge--ambre':'badge--vert' ?>">
                            <?= $collab['role']==='proprietaire'?'👑 Propriétaire':'✏️ Éditeur' ?>
                        </span>
                        <?php if ($isOwner && $collab['role'] !== 'proprietaire'): ?>
                            <?php if ($collab['accepted_at']): ?>
                                <form action="<?= $base ?>/situation/<?= $situation['id'] ?>/collaborateur/<?= $collab['user_id'] ?>/retirer" method="post" style="display:inline">
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--rouge)"
                                            data-confirm="Retirer ce collaborateur ?">✕</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= $base ?>/situation/<?= $situation['id'] ?>/collaborateur/<?= $collab['id'] ?>/revoquer-sit" method="post" style="display:inline">
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--ambre)"
                                            data-confirm="Annuler cette invitation ?">✕</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-muted">
                Aucun collaborateur.
                <?php if ($isOwner): ?>Générez un lien pour inviter des collègues.<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<!-- ═══ FIN SECTION COLLABORATEURS SITUATION ════════════════════════ -->
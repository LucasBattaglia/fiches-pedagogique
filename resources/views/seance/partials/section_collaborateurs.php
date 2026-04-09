<?php
/**
 * PATCH resources/views/seance/show.php
 *
 * Insérer ce bloc JUSTE AVANT la section "Situations"
 * (chercher : <div class="fiche-section fiche-section--ambre">
 *              <div class="fiche-section__title" style="display:flex;...
 *              <span>🎯 Situations)
 *
 * Les variables nécessaires sont passées depuis la route show :
 *   $collaborateurs     → getCollaborateurs($seance['id'])
 *   $collabsHerites     → getCollaborateursHeritesDeLaSequence($seance['id'])
 *   $inviteToken        → ?invite_token= dans l'URL (flash après génération)
 *   $isOwner            → currentUserId() === $seance['user_id']
 */
?>

<!-- ═══ SECTION COLLABORATEURS SÉANCE ═══════════════════════════════ -->
<div class="fiche-section mb-24" id="section-collaborateurs">
    <div class="fiche-section__title" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span>👥 Collaborateurs
            <span class="badge badge--bleu" style="margin-left:6px"><?= count($collaborateurs) ?></span>
        </span>
        <?php if ($isOwner): ?>
            <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/invitation/generer" method="post" style="display:inline">
                <button type="submit" class="btn btn--primary btn--sm">
                    🔗 Générer un lien d'invitation
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="fiche-section__body">

        <!-- Lien d'invitation (affiché après génération) -->
        <?php
        $inviteToken = $_GET['invite_token'] ?? null;
        if ($inviteToken && $isOwner):
            $inviteUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                . $base . '/seance/invitation/' . $inviteToken;
            ?>
            <div class="alert alert--info" style="margin-bottom:16px">
                <div style="flex:1">
                    <strong>🔗 Lien d'invitation généré</strong>
                    <div style="margin-top:6px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <input type="text" value="<?= htmlspecialchars($inviteUrl) ?>"
                               id="invite-url-seance" readonly
                               style="flex:1;min-width:200px;font-size:.8rem;padding:6px 10px;border:1px solid var(--bleu-med);border-radius:var(--rayon);background:white">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('invite-url-seance').value);this.textContent='✅ Copié !';setTimeout(()=>this.textContent='📋 Copier',2000)"
                                class="btn btn--outline btn--sm">📋 Copier</button>
                    </div>
                    <p class="text-sm text-muted" style="margin-top:6px">⏱ Valide 7 jours · Partager ce lien avec vos collègues</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Collaborateurs directs -->
        <?php if (!empty($collaborateurs)): ?>
            <div style="margin-bottom:16px">
                <div class="text-sm text-muted" style="margin-bottom:8px;font-weight:600">Membres de cette séance</div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <?php foreach ($collaborateurs as $collab): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--gris-50);border-radius:var(--rayon);border:1px solid var(--gris-100)">

                            <!-- Avatar -->
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $collab['role']==='proprietaire'?'var(--bleu-med)':'var(--vert)' ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:.85rem;font-weight:700;flex-shrink:0;overflow:hidden">
                                <?php if (!empty($collab['avatar_url']) && $collab['accepted_at']): ?>
                                    <img src="<?= htmlspecialchars($collab['avatar_url']) ?>" style="width:100%;height:100%;object-fit:cover">
                                <?php elseif ($collab['accepted_at']): ?>
                                    <?= strtoupper(mb_substr($collab['prenom'] ?? '?', 0, 1) . mb_substr($collab['nom'] ?? '', 0, 1)) ?>
                                <?php else: ?>
                                    ✉
                                <?php endif; ?>
                            </div>

                            <!-- Infos -->
                            <div style="flex:1;min-width:0">
                                <?php if ($collab['accepted_at']): ?>
                                    <div style="font-weight:600;font-size:.88rem">
                                        <?= htmlspecialchars(($collab['prenom'] ?? '') . ' ' . ($collab['nom'] ?? '')) ?>
                                    </div>
                                    <div class="text-sm text-muted"><?= htmlspecialchars($collab['email'] ?? '') ?></div>
                                <?php else: ?>
                                    <div style="font-weight:600;font-size:.88rem;color:var(--gris-500)">Invitation en attente</div>
                                    <div class="text-sm text-muted">Envoyée le <?= date('d/m/Y', strtotime($collab['created_at'])) ?> · Expire le <?= date('d/m/Y', strtotime($collab['expires_at'])) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Badge rôle -->
                            <span class="badge <?= $collab['role']==='proprietaire'?'badge--bleu':'badge--vert' ?>">
                                <?= $collab['role'] === 'proprietaire' ? '👑 Propriétaire' : '✏️ Éditeur' ?>
                            </span>

                            <!-- Actions (propriétaire uniquement) -->
                            <?php if ($isOwner && $collab['role'] !== 'proprietaire'): ?>
                                <?php if ($collab['accepted_at']): ?>
                                    <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/collaborateur/<?= $collab['user_id'] ?>/retirer" method="post" style="display:inline">
                                        <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--rouge)"
                                                data-confirm="Retirer <?= htmlspecialchars(($collab['prenom']??'').' '.($collab['nom']??'')) ?> de cette séance ?">
                                            ✕ Retirer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/collaborateur/<?= $collab['id'] ?>/revoquer" method="post" style="display:inline">
                                        <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--ambre)"
                                                data-confirm="Annuler cette invitation ?">
                                            ✕ Annuler
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Collaborateurs hérités de la séquence -->
        <?php if (!empty($collabsHerites)): ?>
            <details style="margin-top:8px">
                <summary class="text-sm text-muted" style="cursor:pointer;padding:6px 0;list-style:none;display:flex;align-items:center;gap:6px">
                    <span>↩ <?= count($collabsHerites) ?> collaborateur(s) hérité(s) de la séquence parente</span>
                </summary>
                <div style="margin-top:8px;padding:12px;background:var(--bleu-pale);border-radius:var(--rayon);border:1px solid var(--bleu-clair)">
                    <p class="text-sm text-muted" style="margin-bottom:8px">Ces personnes ont accès car elles collaborent sur la séquence parente.</p>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <?php foreach ($collabsHerites as $c): ?>
                            <div style="display:flex;align-items:center;gap:8px;font-size:.85rem">
                                <div style="width:28px;height:28px;border-radius:50%;background:var(--bleu-clair);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--bleu)">
                                    <?= strtoupper(mb_substr($c['prenom']??'?',0,1).mb_substr($c['nom']??'',0,1)) ?>
                                </div>
                                <span><?= htmlspecialchars(($c['prenom']??'').' '.($c['nom']??'')) ?></span>
                                <span class="badge badge--bleu" style="font-size:.65rem">Via séquence</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endif; ?>

        <?php if (empty($collaborateurs) && empty($collabsHerites)): ?>
            <p class="text-sm text-muted">Aucun collaborateur pour le moment.
                <?php if ($isOwner): ?>Générez un lien d'invitation pour inviter des collègues.<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<!-- ═══ FIN SECTION COLLABORATEURS ══════════════════════════════════ -->
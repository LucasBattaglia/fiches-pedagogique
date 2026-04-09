<?php
$pageTitle = $sequence['titre'] ?? 'Séquence';
$activeNav = 'seq-list';
include __DIR__.'/../partials/layout_start.php';
$uid      = \src\Service\AuthService::currentUserId();
$isLogged = \src\Service\AuthService::isLoggedIn();
$isOwner  = $uid && \src\DAO\CollaborateurDAO::getInstance()->isOwner($sequence['id'], $uid);
$canEdit  = $uid && \src\DAO\CollaborateurDAO::getInstance()->canEdit($sequence['id'], $uid);

// Migration progressive
if ($isLogged && $sequence['user_id'] == $uid) {
    \src\DAO\CollaborateurDAO::getInstance()->ensureOwnerEntry($sequence['id'], $uid);
}

// Collaborateurs
$collaborateurs = \src\DAO\CollaborateurDAO::getInstance()->findBySequence($sequence['id']);

// Token invitation
$inviteToken = $_GET['invite_token'] ?? null;
if ($inviteToken) {
    $tokenCheck = \src\DAO\CollaborateurDAO::getInstance()->findByToken($inviteToken);
    if (!$tokenCheck || (int)$tokenCheck['sequence_id'] !== (int)$sequence['id']) {
        $inviteToken = null;
    }
}
?>
    <div class="container container--md">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <a href="/sequence/list">Mes séquences</a>
            <span class="breadcrumb__sep">›</span>
            <span><?= htmlspecialchars($sequence['titre']) ?></span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $msgs): ?>
                <?php foreach ((array)$msgs as $msg): ?>
                    <div class="alert alert--<?= htmlspecialchars($type) ?>" data-dismiss="4000"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            <?php endforeach; unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- En-tête fiche -->
        <div class="fiche-header">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div style="flex:1;min-width:0">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                        <?php if ($sequence['is_public']): ?>
                            <span class="badge badge--vert">🌐 Public</span>
                        <?php else: ?>
                            <span class="badge badge--gris">🔒 Privé</span>
                        <?php endif; ?>
                        <?php if ($sequence['matiere_label']): ?>
                            <span class="badge badge--bleu"><?= htmlspecialchars($sequence['matiere_label']) ?></span>
                        <?php endif; ?>
                        <?php if ($sequence['classe_label']): ?>
                            <span class="badge badge--ambre"><?= htmlspecialchars($sequence['classe_label']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sequence['annee_entree']) && $sequence['annee_entree'] >= 2025): ?>
                            <span class="badge badge--vert">Programme <?= $sequence['annee_entree'] ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 style="font-family:var(--font-titre);font-size:1.6rem;color:var(--gris-900);margin-bottom:8px">
                        <?= htmlspecialchars($sequence['titre']) ?>
                    </h1>
                    <p class="text-muted text-sm">
                        Par <?= htmlspecialchars($sequence['prenom'].' '.$sequence['nom']) ?>
                        · Créée le <?= date('d/m/Y', strtotime($sequence['created_at'])) ?>
                        · Modifiée le <?= date('d/m/Y', strtotime($sequence['updated_at'])) ?>
                    </p>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
                    <?php if($canEdit) : ?>
                        <a href="/sequence/<?= $sequence['id'] ?>/edit" class="btn btn--outline btn--sm">✏️ Modifier</a>
                        <a href="/sequence/<?= $sequence['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                        <?php if ($isOwner): ?>
                            <form action="/sequence/<?= $sequence['id'] ?>/delete" method="post" style="display:inline">
                                <button type="submit" class="btn btn--danger btn--sm"
                                        data-confirm="Supprimer cette séquence et toutes ses séances/situations ?">🗑 Supprimer</button>
                            </form>
                        <?php else : ?>
                            <form action="/sequence/<?= $sequence['id'] ?>/fork" method="post" style="display:inline">
                                <button type="submit" class="btn btn--outline btn--sm">📋 Copier dans mes fiches</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($isLogged): ?>
                        <form action="/sequence/<?= $sequence['id'] ?>/fork" method="post" style="display:inline">
                            <button type="submit" class="btn btn--outline btn--sm">📋 Copier dans mes fiches</button>
                        </form>
                        <a href="/sequence/<?= $sequence['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                    <?php else: ?>
                        <a href="/sequence/<?= $sequence['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="fiche-section fiche-section--bleu mb-24">
            <div class="fiche-section__title">📋 Informations générales</div>
            <div class="fiche-section__body">
                <div class="fiche-grid">
                    <div class="fiche-field">
                        <label>Domaine / Champ</label>
                        <p><?= htmlspecialchars($sequence['domaine'] ?: '—') ?></p>
                    </div>
                    <div class="fiche-field">
                        <label>Niveau</label>
                        <p><?= htmlspecialchars(($sequence['cycle_label'] ?? '') . ($sequence['classe_label'] ? ' – '.$sequence['classe_label'] : '')) ?></p>
                    </div>
                    <div class="fiche-field">
                        <label>Nombre de séances prévu</label>
                        <p><?= $sequence['nb_seances'] ?? '—' ?></p>
                    </div>
                    <div class="fiche-field">
                        <label>Programme applicable</label>
                        <p><?= htmlspecialchars($sequence['programme_label'] ?? '—') ?>
                            <?php if ($sequence['annee_entree']): ?>
                                <span class="badge badge--gris" style="margin-left:6px">Rentrée <?= $sequence['annee_entree'] ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($sequence['tache_finale']): ?>
                    <div class="fiche-field" style="margin-top:8px">
                        <label>Tâche finale</label>
                        <p><?= nl2br(htmlspecialchars($sequence['tache_finale'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($sequence['objectifs_generaux']): ?>
                    <div class="fiche-field">
                        <label>Objectifs généraux</label>
                        <p><?= nl2br(htmlspecialchars($sequence['objectifs_generaux'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($sequence['materiel']): ?>
                    <div class="fiche-field">
                        <label>Matériel</label>
                        <p><?= nl2br(htmlspecialchars($sequence['materiel'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tableau des séances -->
        <div class="fiche-section fiche-section--vert mb-24">
            <div class="fiche-section__title" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <span>📅 Séances (<?= count($seances) ?>)</span>
                <?php if ($canEdit): ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <a href="<?= $base ?>/seance/create?sequence_id=<?= $sequence['id'] ?>" class="btn btn--vert btn--sm">+ Nouvelle séance</a>
                        <button type="button" class="btn btn--outline btn--sm" data-modal-open="modal-add-seance" style="background:white">📋 Séance existante</button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="fiche-section__body">
                <?php if (empty($seances)): ?>
                    <div class="empty-state" style="padding:32px">
                        <div class="empty-state__icon">📅</div>
                        <h3>Aucune séance</h3>
                        <?php if ($canEdit): ?>
                            <p>Créez une nouvelle séance ou ajoutez-en une existante.</p>
                            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                                <a href="<?= $base ?>/seance/create?sequence_id=<?= $sequence['id'] ?>" class="btn btn--vert btn--sm">+ Nouvelle séance</a>
                                <button type="button" class="btn btn--outline btn--sm" data-modal-open="modal-add-seance">📋 Séance existante</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if (count($seances) > 1 && $isOwner): ?>
                        <div class="table-wrap mb-16">
                            <table>
                                <thead>
                                <tr>
                                    <th style="width:50px">N°</th>
                                    <th>Titre / Objectif</th>
                                    <th style="width:90px">Durée</th>
                                    <th style="width:90px">Situations</th>
                                    <th style="width:120px">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($seances as $s): ?>
                                    <tr>
                                        <td style="text-align:center">
                                            <?php if ($canEdit): ?>
                                                <form action="<?= $base ?>/seance/<?= $s['id'] ?>/position" method="post"
                                                      style="display:flex;align-items:center;gap:4px;justify-content:center">
                                                    <input type="hidden" name="sequence_id" value="<?= $sequence['id'] ?>">
                                                    <input type="number" name="position" value="<?= $s['position_in_seq'] ?? $s['numero'] ?>"
                                                           min="1" max="<?= count($seances) ?>"
                                                           style="width:46px;padding:3px 5px;text-align:center;font-weight:700;font-size:.85rem"
                                                           onchange="this.form.submit()" title="Changer la position">
                                                </form>
                                            <?php else: ?>
                                                <strong><?= $s['position_in_seq'] ?? $s['numero'] ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:600"><?= htmlspecialchars($s['titre']) ?></div>
                                            <?php if ($s['objectif_general']): ?>
                                                <div class="text-sm text-muted"><?= htmlspecialchars(mb_strimwidth($s['objectif_general'], 0, 80, '…')) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $s['duree'] ? $s['duree'].' min' : '—' ?></td>
                                        <td><?= count($situationsBySeance[$s['id']] ?? []) ?></td>
                                        <td style="display:flex;gap:4px">
                                            <?php if ($canEdit): ?>
                                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/show?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" title="Voir">👁</a>
                                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/edit?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" title="Modifier">✏️</a>
                                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" target="_blank" title="PDF">📄</a>
                                                <form action="<?= $base ?>/seance/<?= $s['id'] ?>/delete?from_seq=<?= $sequence['id'] ?>" method="post" style="display:inline">
                                                    <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette séance ?">🗑</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Détail dépliable de chaque séance -->
                    <?php foreach ($seances as $s): ?>
                        <details class="seance-item" style="display:block;margin-bottom:12px;cursor:default">
                            <summary style="display:flex;align-items:center;gap:14px;cursor:pointer;list-style:none;outline:none">
                                <div class="seance-item__num"><?= $s['position_in_seq'] ?? $s['numero'] ?></div>
                                <div class="seance-item__info">
                                    <div class="seance-item__titre"><?= htmlspecialchars($s['titre']) ?></div>
                                    <div class="seance-item__meta">
                                        <?= $s['duree'] ? $s['duree'].' min' : '' ?>
                                        <?php if ($s['objectif_intermediaire']): ?> · <?= htmlspecialchars(mb_strimwidth($s['objectif_intermediaire'], 0, 70, '…')) ?><?php endif; ?>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;flex-shrink:0">
                                    <?php if ($canEdit): ?>
                                        <a href="<?= $base ?>/seance/<?= $s['id'] ?>/show?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" onclick="event.stopPropagation()">👁 Voir</a>
                                        <a href="<?= $base ?>/seance/<?= $s['id'] ?>/edit?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" onclick="event.stopPropagation()">✏️</a>
                                        <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" target="_blank" onclick="event.stopPropagation()">📄</a>
                                    <?php endif; ?>
                                    <span class="btn btn--ghost btn--sm" style="cursor:default">▼</span>
                                </div>
                            </summary>

                            <div style="padding:16px 0 4px 50px">
                                <?php if ($s['competence_visee'] || $s['afc']): ?>
                                    <div class="fiche-grid" style="margin-bottom:12px">
                                        <div class="fiche-field"><label>Compétence visée</label><p><?= nl2br(htmlspecialchars($s['competence_visee'] ?? '—')) ?></p></div>
                                        <div class="fiche-field"><label>AFC</label><p><?= nl2br(htmlspecialchars($s['afc'] ?? '—')) ?></p></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($s['criteres_realisation'] || $s['criteres_reussite']): ?>
                                    <div class="fiche-grid" style="margin-bottom:12px">
                                        <div class="fiche-field"><label>Critères de réalisation</label><p><?= nl2br(htmlspecialchars($s['criteres_realisation'] ?? '—')) ?></p></div>
                                        <div class="fiche-field"><label>Critères de réussite</label><p><?= nl2br(htmlspecialchars($s['criteres_reussite'] ?? '—')) ?></p></div>
                                    </div>
                                <?php endif; ?>

                                <?php $sitsCourantes = $situationsBySeance[$s['id']] ?? []; ?>
                                <?php if (!empty($sitsCourantes)): ?>
                                    <h4 style="font-size:.85rem;font-weight:600;margin-bottom:8px;color:var(--ambre)">
                                        Situations (<?= count($sitsCourantes) ?>)
                                    </h4>
                                    <div class="situations-list">
                                        <?php foreach ($sitsCourantes as $sit): ?>
                                            <div class="situation-item">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
                                                    <div>
                                                        <strong>Situation <?= $sit['numero'] ?> – <?= htmlspecialchars($sit['titre']) ?></strong>
                                                        <?php if ($sit['duree']): ?>
                                                            <span class="badge badge--ambre" style="margin-left:8px"><?= $sit['duree'] ?> min</span>
                                                        <?php endif; ?>
                                                        <?php if ($sit['objectif_moteur']): ?>
                                                            <div class="text-sm text-muted mt-8">🎯 <?= htmlspecialchars($sit['objectif_moteur']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($canEdit): ?>
                                                        <div style="display:flex;gap:4px">
                                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/show?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm">👁</a>
                                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/edit?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm">✏️</a>
                                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/pdf?from_seq=<?= $sequence['id'] ?>" class="btn btn--ghost btn--sm" target="_blank">📄</a>
                                                            <form action="<?= $base ?>/situation/<?= $sit['id'] ?>/delete?from_seq=<?= $sequence['id'] ?>" method="post" style="display:inline">
                                                                <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette situation ?">🗑</button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($canEdit): ?>
                                    <div style="margin-top:12px">
                                        <a href="/situation/create?seance_id=<?= $s['id'] ?>" class="btn btn--ambre btn--sm">+ Ajouter une situation</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comportements & remédiations de la séquence -->
        <?php
        $cr = is_array($sequence['comportements_remediations'])
                ? $sequence['comportements_remediations']
                : (json_decode($sequence['comportements_remediations'] ?? '[]', true) ?? []);
        if (!empty($cr)):
            ?>
            <div class="fiche-section mb-24">
                <div class="fiche-section__title">🔄 Comportements possibles & remédiations</div>
                <div class="fiche-section__body" style="padding:0">
                    <div class="table-wrap" style="border:none;border-radius:0">
                        <table>
                            <thead><tr><th>Comportement observé</th><th>Remédiation proposée</th></tr></thead>
                            <tbody>
                            <?php foreach ($cr as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['comportement'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['remediation'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php if ($canEdit): ?>
    <div class="modal-overlay" id="modal-add-seance">
        <div class="modal" style="max-width:620px">
            <div class="modal__header">
                <h3>📋 Ajouter une séance existante</h3>
                <button class="btn btn--ghost btn--sm" data-modal-close>✕</button>
            </div>
            <?php if (empty($seancesDisponibles)): ?>
                <div class="modal__body">
                    <div class="empty-state" style="padding:32px">
                        <div class="empty-state__icon">📅</div>
                        <h3>Aucune séance disponible</h3>
                        <p>Toutes vos séances sont déjà dans cette séquence, ou vous n'en avez pas encore créé.</p>
                        <a href="<?= $base ?>/seance/create" class="btn btn--vert btn--sm" target="_blank">Créer une séance</a>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" data-modal-close>Fermer</button>
                </div>
            <?php else: ?>
                <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/add-seance" method="post">
                    <div class="modal__body">
                        <p class="text-muted text-sm" style="margin-bottom:16px">
                            Choisissez une séance parmi celles que vous avez déjà créées.<br>
                            Elle sera liée à cette séquence sans être dupliquée.
                        </p>
                        <div class="form-group">
                            <label for="seance_id_modal">Séance à ajouter <span class="required">*</span></label>
                            <select name="seance_id" id="seance_id_modal" required style="font-size:.88rem">
                                <option value="">— Sélectionner une séance —</option>
                                <?php foreach ($seancesDisponibles as $sd): ?>
                                    <option value="<?= $sd['id'] ?>">
                                        <?= htmlspecialchars($sd['titre']) ?>
                                        <?php if (!empty($sd['sequence_titre'])): ?> · (séq. <?= htmlspecialchars(mb_strimwidth($sd['sequence_titre'], 0, 30, '…')) ?>)<?php endif; ?>
                                        <?php if (empty($sd['sequence_id'])): ?> · <Autonome><?php endif; ?>
                                            <?php if ($sd['duree']): ?> · <?= $sd['duree'] ?> min<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert--info" style="font-size:.82rem">
                            💡 La séance restera accessible depuis toutes ses séquences. Ses situations sont incluses.
                        </div>
                    </div>
                    <div class="modal__footer">
                        <button type="button" class="btn btn--ghost" data-modal-close>Annuler</button>
                        <button type="submit" class="btn btn--primary">Ajouter à la séquence</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>


<?php if ($isLogged): ?>
    <?php $currentUserId = $uid; // ✅ AJOUT IMPORTANT
    include __DIR__ . '/../partials/collaborateurs.php'; ?>
<?php endif; ?>
<?php include __DIR__.'/../partials/layout_end.php'; ?>
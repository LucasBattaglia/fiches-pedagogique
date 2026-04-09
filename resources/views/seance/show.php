<?php
$pageTitle = 'Séance ' . ($seance['numero'] ?? '') . ' — ' . htmlspecialchars($seance['titre']);
$activeNav = 'seq-list';
$fromSeqId = $fromSeqId ?? null;
include __DIR__ . '/../partials/layout_start.php';
use src\Service\AuthService;
$uid = AuthService::currentUserId();
?>
    <div class="container container--md">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= $base ?>/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <?php if (!empty($sequence)): ?>
                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a>
            <?php elseif ($fromSeqId): ?>
                <a href="<?= $base ?>/sequence/<?= $fromSeqId ?>">Retour séquence</a>
                <span class="breadcrumb__sep">›</span>
            <?php else: ?>
                <a href="<?= $base ?>/seance/list">Mes séances</a>
                <span class="breadcrumb__sep">›</span>
            <?php endif; ?>
            <span>Séance <?= $seance['numero'] ?? '' ?></span>
        </div>

        <!-- En-tête -->
        <div class="fiche-header">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div style="flex:1">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                        <span class="badge badge--vert">Séance <?= $seance['numero'] ?? 1 ?></span>
                        <?php if ($seance['duree']): ?>
                            <span class="badge badge--bleu">⏱ <?= $seance['duree'] ?> min</span>
                        <?php endif; ?>
                        <?php if (empty($seance['sequence_id'])): ?>
                            <span class="badge badge--ambre">Séance autonome</span>
                        <?php endif; ?>
                    </div>
                    <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:6px">
                        <?= htmlspecialchars($seance['titre']) ?>
                    </h1>
                    <?php if (!empty($sequence)): ?>
                        <p class="text-muted text-sm">Séquence : <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a></p>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
                    <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/edit?from_seq=<?= $fromSeqId ?? '' ?>" class="btn btn--outline btn--sm">✏️ Modifier</a>
                    <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                    <?php if (empty($seance['sequence_id'])): ?>
                        <button class="btn btn--primary btn--sm" data-modal-open="modal-attach">📋 Ajouter à une séquence</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">
            <div>

                <!-- Informations générales -->
                <div class="fiche-section fiche-section--bleu mb-24">
                    <div class="fiche-section__title">📋 Informations générales</div>
                    <div class="fiche-section__body">
                        <div class="fiche-grid">
                            <div class="fiche-field">
                                <label>Champ d'apprentissage</label>
                                <p><?= htmlspecialchars($seance['champ_apprentissage'] ?: '—') ?></p>
                            </div>
                            <div class="fiche-field">
                                <label>Durée</label>
                                <p><?= $seance['duree'] ? $seance['duree'].' min' : '—' ?></p>
                            </div>
                        </div>
                        <div class="fiche-field">
                            <label>Compétence visée</label>
                            <p><?= nl2br(htmlspecialchars($seance['competence_visee'] ?: '—')) ?></p>
                        </div>
                        <div class="fiche-field">
                            <label>AFC (Attendu de Fin de Cycle)</label>
                            <p><?= nl2br(htmlspecialchars($seance['afc'] ?: '—')) ?></p>
                        </div>
                        <div class="fiche-grid">
                            <div class="fiche-field">
                                <label>Objectif général</label>
                                <p><?= nl2br(htmlspecialchars($seance['objectif_general'] ?: '—')) ?></p>
                            </div>
                            <div class="fiche-field">
                                <label>Objectif intermédiaire</label>
                                <p><?= nl2br(htmlspecialchars($seance['objectif_intermediaire'] ?: '—')) ?></p>
                            </div>
                        </div>
                        <?php if ($seance['materiel']): ?>
                            <div class="fiche-field">
                                <label>Matériel</label>
                                <p><?= nl2br(htmlspecialchars($seance['materiel'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Déroulement -->
                <?php if (!empty($seance['deroulement'])): ?>
                    <div class="fiche-section mb-24">
                        <div class="fiche-section__title">⏱ Déroulement</div>
                        <div class="fiche-section__body" style="padding:0">
                            <div class="table-wrap" style="border:none;border-radius:0">
                                <table>
                                    <thead>
                                    <tr><th style="width:80px">Durée (min)</th><th style="width:40%">Enseignant(e)</th><th>Élèves</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($seance['deroulement'] as $row): ?>
                                        <tr>
                                            <td style="text-align:center;font-weight:600"><?= htmlspecialchars($row['duree'] ?? '—') ?></td>
                                            <td><?= nl2br(htmlspecialchars($row['enseignant'] ?? '')) ?></td>
                                            <td><?= nl2br(htmlspecialchars($row['eleves'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Critères -->
                <div class="fiche-section mb-24">
                    <div class="fiche-section__title">✅ Critères & Variables didactiques</div>
                    <div class="fiche-section__body">
                        <div class="fiche-grid">
                            <div class="fiche-field">
                                <label>Critères de réalisation</label>
                                <p><?= nl2br(htmlspecialchars($seance['criteres_realisation'] ?: '—')) ?></p>
                            </div>
                            <div class="fiche-field">
                                <label>Critères de réussite</label>
                                <p><?= nl2br(htmlspecialchars($seance['criteres_reussite'] ?: '—')) ?></p>
                            </div>
                        </div>
                        <?php if ($seance['variables_didactiques']): ?>
                            <div class="fiche-field">
                                <label>Variables didactiques</label>
                                <p><?= nl2br(htmlspecialchars($seance['variables_didactiques'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comportements -->
                <?php if (!empty($seance['comportements_remediations'])): ?>
                    <div class="fiche-section mb-24">
                        <div class="fiche-section__title">🔄 Comportements & remédiations</div>
                        <div class="fiche-section__body" style="padding:0">
                            <div class="table-wrap" style="border:none;border-radius:0">
                                <table>
                                    <thead><tr><th>Comportement observé</th><th>Remédiation proposée</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($seance['comportements_remediations'] as $cr): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cr['comportement'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($cr['remediation'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Situations -->
                <div class="fiche-section fiche-section--ambre">
                    <div class="fiche-section__title" style="display:flex;align-items:center;justify-content:space-between">
                        <span>🎯 Situations (<?= count($situations) ?>)</span>
                        <a href="<?= $base ?>/situation/create?seance_id=<?= $seance['id'] ?>" class="btn btn--ambre btn--sm">+ Ajouter</a>
                    </div>
                    <div class="fiche-section__body">
                        <?php if (empty($situations)): ?>
                            <p class="text-muted text-sm">Aucune situation pour cette séance.</p>
                        <?php else: ?>
                            <div class="situations-list">
                                <?php foreach ($situations as $sit): ?>
                                    <div class="situation-item" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                        <div>
                                            <strong>Situation <?= $sit['numero'] ?> — <?= htmlspecialchars($sit['titre']) ?></strong>
                                            <?php if ($sit['duree']): ?><span class="badge badge--ambre" style="margin-left:8px"><?= $sit['duree'] ?> min</span><?php endif; ?>
                                            <?php if ($sit['objectif_moteur']): ?>
                                                <div class="text-sm text-muted mt-8">🎯 <?= htmlspecialchars($sit['objectif_moteur']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex;gap:6px">
                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/show?from_seq=<?= $fromSeqId ?? $seance['sequence_id'] ?? '' ?>" class="btn btn--ghost btn--sm">👁 Voir</a>
                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/edit?from_seq=<?= $fromSeqId ?? $seance['sequence_id'] ?? '' ?>" class="btn btn--ghost btn--sm">✏️</a>
                                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/pdf?from_seq=<?= $fromSeqId ?? $seance['sequence_id'] ?? '' ?>" class="btn btn--ghost btn--sm" target="_blank">📄</a>
                                            <form action="<?= $base ?>/situation/<?= $sit['id'] ?>/delete" method="post" style="display:inline">
                                                <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette situation ?">🗑</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- fin col gauche -->

            <!-- COL DROITE -->
            <div style="position:sticky;top:80px;display:flex;flex-direction:column;gap:12px">
                <div class="card">
                    <div class="card__header"><h3 class="card__titre">Actions</h3></div>
                    <div class="card__body" style="display:flex;flex-direction:column;gap:8px">
                        <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/edit?from_seq=<?= $fromSeqId ?? '' ?>" class="btn btn--outline" style="justify-content:center">✏️ Modifier</a>
                        <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/pdf" class="btn btn--ghost" style="justify-content:center" target="_blank">📄 Exporter PDF</a>
                        <a href="<?= $base ?>/situation/create?seance_id=<?= $seance['id'] ?>" class="btn btn--ambre" style="justify-content:center">+ Ajouter une situation</a>
                        <?php if (empty($seance['sequence_id'])): ?>
                            <hr style="border:none;border-top:1px solid var(--gris-300)">
                            <button class="btn btn--primary" style="justify-content:center" data-modal-open="modal-attach">📋 Ajouter à une séquence</button>
                        <?php else: ?>
                            <hr style="border:none;border-top:1px solid var(--gris-300)">
                            <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/detach" method="post">
                                <button type="submit" class="btn btn--ghost" style="width:100%;justify-content:center"
                                        data-confirm="Détacher cette séance de sa séquence ?">↩ Rendre autonome</button>
                            </form>
                        <?php endif; ?>
                        <hr style="border:none;border-top:1px solid var(--gris-300)">
                        <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/delete" method="post">
                            <button type="submit" class="btn btn--danger btn--sm" style="width:100%;justify-content:center"
                                    data-confirm="Supprimer cette séance et toutes ses situations ?">🗑 Supprimer</button>
                        </form>
                    </div>
                </div>
                <?php if (!empty($sequence)): ?>
                    <div class="card">
                        <div class="card__body">
                            <div class="text-sm text-muted mb-8">Séquence parente</div>
                            <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>" style="font-weight:600;font-size:.9rem">
                                <?= htmlspecialchars($sequence['titre']) ?>
                            </a>
                            <?php if ($sequence['matiere_label'] ?? false): ?>
                                <div style="margin-top:6px"><span class="badge badge--bleu"><?= htmlspecialchars($sequence['matiere_label']) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal : attacher à une séquence -->
<?php if (empty($seance['sequence_id']) && !empty($mesSequences)): ?>
    <div class="modal-overlay" id="modal-attach">
        <div class="modal">
            <div class="modal__header">
                <h3>Ajouter à une séquence</h3>
                <button class="btn btn--ghost btn--sm" data-modal-close>✕</button>
            </div>
            <form action="<?= $base ?>/seance/<?= $seance['id'] ?>/attach" method="post">
                <div class="modal__body">
                    <div class="form-group">
                        <label for="sequence_id">Choisir la séquence</label>
                        <select name="sequence_id" id="sequence_id" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($mesSequences as $seq): ?>
                                <option value="<?= $seq['id'] ?>"><?= htmlspecialchars($seq['titre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn--primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__.'/partials/section_collaborateurs.php'; ?>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>
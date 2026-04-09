<?php
$pageTitle = 'Situation ' . ($situation['numero'] ?? '') . ' — ' . htmlspecialchars($situation['titre']);
$activeNav = 'seq-list';
$fromSeqId = $fromSeqId ?? null;
include __DIR__ . '/../partials/layout_start.php';
?>
    <div class="container container--md">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= $base ?>/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <?php if (!empty($sequence)): ?>
                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a>
                <span class="breadcrumb__sep">›</span>
            <?php elseif ($fromSeqId): ?>
                <a href="<?= $base ?>/sequence/<?= $fromSeqId ?>">Retour séquence</a>
                <span class="breadcrumb__sep">›</span>
            <?php endif; ?>
            <?php if (!empty($seance)): ?>
                <?php $fsq = $fromSeqId ?? $sequence['id'] ?? ''; ?>
                <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/show<?= $fsq ? '?from_seq='.$fsq : '' ?>">Séance <?= $seance['numero'] ?></a>
                <span class="breadcrumb__sep">›</span>
            <?php endif; ?>
            <span>Situation <?= $situation['numero'] ?? 1 ?></span>
        </div>

        <!-- En-tête -->
        <div class="fiche-header" style="border-left:4px solid var(--ambre)">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div style="flex:1">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                        <span class="badge badge--ambre">Situation <?= $situation['numero'] ?? 1 ?></span>
                        <?php if ($situation['duree']): ?>
                            <span class="badge badge--bleu">⏱ <?= $situation['duree'] ?> min</span>
                        <?php endif; ?>
                    </div>
                    <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:6px">
                        <?= htmlspecialchars($situation['titre']) ?>
                    </h1>
                    <?php if (!empty($seance)): ?>
                        <p class="text-muted text-sm">Séance : <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/show"><?= htmlspecialchars($seance['titre']) ?></a></p>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
                    <a href="<?= $base ?>/situation/<?= $situation['id'] ?>/edit?from_seq=<?= $fromSeqId ?? '' ?>" class="btn btn--outline btn--sm">✏️ Modifier</a>
                    <a href="<?= $base ?>/situation/<?= $situation['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">
            <div>

                <!-- Informations générales -->
                <div class="fiche-section fiche-section--ambre mb-24">
                    <div class="fiche-section__title">📋 Informations générales</div>
                    <div class="fiche-section__body">
                        <div class="fiche-grid">
                            <div class="fiche-field">
                                <label>Champ d'apprentissage</label>
                                <p><?= htmlspecialchars($situation['champ_apprentissage'] ?: '—') ?></p>
                            </div>
                            <div class="fiche-field">
                                <label>Durée</label>
                                <p><?= $situation['duree'] ? $situation['duree'].' min' : '—' ?></p>
                            </div>
                        </div>
                        <div class="fiche-field">
                            <label>AFC (Attendu de Fin de Cycle)</label>
                            <p><?= nl2br(htmlspecialchars($situation['afc'] ?: '—')) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Objectifs -->
                <div class="fiche-section mb-24">
                    <div class="fiche-section__title">🎯 Objectifs</div>
                    <div class="fiche-section__body">
                        <div class="fiche-field">
                            <label>Objectif moteur</label>
                            <p><?= nl2br(htmlspecialchars($situation['objectif_moteur'] ?: '—')) ?></p>
                        </div>
                        <div class="fiche-grid">
                            <div class="fiche-field">
                                <label>Objectif socio-affectif</label>
                                <p><?= nl2br(htmlspecialchars($situation['objectif_socio_affectif'] ?: '—')) ?></p>
                            </div>
                            <div class="fiche-field">
                                <label>Objectif cognitif</label>
                                <p><?= nl2br(htmlspecialchars($situation['objectif_cognitif'] ?: '—')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dispositif -->
                <div class="fiche-section mb-24">
                    <div class="fiche-section__title">🏃 Dispositif</div>
                    <div class="fiche-section__body">
                        <div class="fiche-field"><label>But</label><p><?= nl2br(htmlspecialchars($situation['but'] ?: '—')) ?></p></div>
                        <div class="fiche-field"><label>Dispositif</label><p><?= nl2br(htmlspecialchars($situation['dispositif'] ?: '—')) ?></p></div>
                        <div class="fiche-grid">
                            <div class="fiche-field"><label>Organisation</label><p><?= nl2br(htmlspecialchars($situation['organisation'] ?: '—')) ?></p></div>
                            <div class="fiche-field"><label>Fonctionnement</label><p><?= nl2br(htmlspecialchars($situation['fonctionnement'] ?: '—')) ?></p></div>
                        </div>
                        <?php if ($situation['materiel']): ?>
                            <div class="fiche-field"><label>Matériel</label><p><?= nl2br(htmlspecialchars($situation['materiel'])) ?></p></div>
                        <?php endif; ?>
                        <?php if ($situation['consignes_base']): ?>
                            <div class="fiche-field"><label>Consignes de base</label><p><?= nl2br(htmlspecialchars($situation['consignes_base'])) ?></p></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Variables d'évolution -->
                <?php if (!empty($situation['variables_evolution'])): ?>
                    <div class="fiche-section mb-24">
                        <div class="fiche-section__title">🔀 Variables d'évolution</div>
                        <div class="fiche-section__body" style="padding:0">
                            <div class="table-wrap" style="border:none;border-radius:0">
                                <table>
                                    <thead><tr><th>Variable</th><th>Complexifier (+)</th><th>Simplifier (−)</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($situation['variables_evolution'] as $ve): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ve['variable'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($ve['plus'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($ve['moins'] ?? '') ?></td>
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
                    <div class="fiche-section__title">✅ Critères de réalisation & de réussite</div>
                    <div class="fiche-section__body">
                        <div class="fiche-grid">
                            <div class="fiche-field"><label>Critères de réalisation</label><p><?= nl2br(htmlspecialchars($situation['criteres_realisation'] ?: '—')) ?></p></div>
                            <div class="fiche-field"><label>Critères de réussite</label><p><?= nl2br(htmlspecialchars($situation['criteres_reussite'] ?: '—')) ?></p></div>
                        </div>
                    </div>
                </div>

                <!-- Comportements -->
                <?php if (!empty($situation['comportements_remediations'])): ?>
                    <div class="fiche-section">
                        <div class="fiche-section__title">🔄 Comportements & remédiations</div>
                        <div class="fiche-section__body" style="padding:0">
                            <div class="table-wrap" style="border:none;border-radius:0">
                                <table>
                                    <thead><tr><th>Comportement observé</th><th>Remédiation proposée</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($situation['comportements_remediations'] as $cr): ?>
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

            </div><!-- fin col gauche -->

            <!-- COL DROITE -->
            <div style="position:sticky;top:80px;display:flex;flex-direction:column;gap:12px">
                <div class="card">
                    <div class="card__header"><h3 class="card__titre">Actions</h3></div>
                    <div class="card__body" style="display:flex;flex-direction:column;gap:8px">
                        <a href="<?= $base ?>/situation/<?= $situation['id'] ?>/edit?from_seq=<?= $fromSeqId ?? '' ?>" class="btn btn--outline" style="justify-content:center">✏️ Modifier</a>
                        <a href="<?= $base ?>/situation/<?= $situation['id'] ?>/pdf" class="btn btn--ghost" style="justify-content:center" target="_blank">📄 Exporter PDF</a>
                        <hr style="border:none;border-top:1px solid var(--gris-300)">
                        <form action="<?= $base ?>/situation/<?= $situation['id'] ?>/delete" method="post">
                            <button type="submit" class="btn btn--danger btn--sm" style="width:100%;justify-content:center"
                                    data-confirm="Supprimer cette situation ?">🗑 Supprimer</button>
                        </form>
                    </div>
                </div>
                <?php if (!empty($seance)): ?>
                    <div class="card">
                        <div class="card__body">
                            <div class="text-sm text-muted mb-8">Séance parente</div>
                            <a href="<?= $base ?>/seance/<?= $seance['id'] ?>/show" style="font-weight:600;font-size:.9rem">
                                Séance <?= $seance['numero'] ?> — <?= htmlspecialchars($seance['titre']) ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!--
      PATCH resources/views/situation/show.php

      1. Dans la colonne DROITE (card Actions), ajouter AVANT la hr du bas :
    -->

<?php if (empty($situation['seance_id']) && !empty($mesSeances)): ?>
    <button class="btn btn--primary btn--sm" style="justify-content:center;width:100%"
            data-modal-open="modal-attach-seance">
        📋 Rattacher à une séance
    </button>
    <hr style="border:none;border-top:1px solid var(--gris-300)">
<?php elseif (empty($situation['seance_id'])): ?>
    <div style="background:var(--ambre-clair);border-radius:var(--rayon);padding:10px 12px;font-size:.82rem;color:var(--ambre)">
        🔓 Situation autonome — non rattachée à une séance
    </div>
<?php endif; ?>

    <!--
      2. Avant include layout_end, ajouter le modal :
    -->

<?php if (empty($situation['seance_id']) && !empty($mesSeances)): ?>
    <div class="modal-overlay" id="modal-attach-seance">
        <div class="modal" style="max-width:520px">
            <div class="modal__header">
                <h3>📋 Rattacher à une séance</h3>
                <button class="btn btn--ghost btn--sm" data-modal-close>✕</button>
            </div>
            <form action="<?= $base ?>/situation/<?= $situation['id'] ?>/attach-seance" method="post">
                <div class="modal__body">
                    <p class="text-muted text-sm" style="margin-bottom:16px">
                        Choisissez la séance à laquelle rattacher cette situation.<br>
                        Elle sera positionnée en dernière position.
                    </p>
                    <div class="form-group">
                        <label for="seance_id_modal">Séance <span class="required">*</span></label>
                        <select name="seance_id" id="seance_id_modal" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($mesSeances as $ms): ?>
                                <option value="<?= $ms['id'] ?>">
                                    <?= htmlspecialchars($ms['titre']) ?>
                                    <?php if (!empty($ms['sequence_titre'])): ?> (<?= htmlspecialchars(mb_strimwidth($ms['sequence_titre'], 0, 30, '…')) ?>)<?php endif; ?>
                                    <?php if (empty($ms['sequence_id'])): ?> — Autonome<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn--primary">Rattacher</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/../partials/layout_end.php'; ?>
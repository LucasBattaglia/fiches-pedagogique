<?php
$pageTitle = 'Référentiel des programmes';
$activeNav = 'programmes';
include __DIR__.'/partials/layout_start.php';
?>
    <div class="container">
        <div class="breadcrumb">
            <a href="/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <span>Programmes scolaires</span>
        </div>

        <div style="margin-bottom:32px">
            <h1 style="font-family:var(--font-titre);font-size:1.8rem;margin-bottom:8px">Référentiel des programmes</h1>
            <p class="text-muted">Programmes en vigueur à la rentrée 2025, par cycle et par matière.</p>
        </div>

        <?php foreach ($versions as $cycleLabel => $matieres): ?>
            <div class="fiche-section mb-24">
                <div class="fiche-section__title fiche-section--bleu">
                    <?= htmlspecialchars($cycleLabel) ?>
                </div>
                <div class="fiche-section__body" style="padding:0">

                    <?php foreach ($matieres as $matiereLabel => $pvs): ?>
                        <div class="prog-matiere-block">
                            <div class="prog-matiere-titre"><?= htmlspecialchars($matiereLabel) ?></div>

                            <!-- Tableau desktop -->
                            <div class="prog-table-wrap">
                                <table>
                                    <thead>
                                    <tr>
                                        <th>Classe</th>
                                        <th>Programme</th>
                                        <th>Rentrée</th>
                                        <th>Notes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pvs as $pv): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pv['classe_label'] ?? 'Tout le cycle') ?></td>
                                            <td><?= htmlspecialchars($pv['label']) ?></td>
                                            <td>
                  <span class="badge <?= $pv['annee_entree'] >= 2025 ? 'badge--vert' : 'badge--gris' ?>">
                    <?= $pv['annee_entree'] ?>
                    <?= $pv['annee_entree'] >= 2025 ? ' ✦' : '' ?>
                  </span>
                                            </td>
                                            <td class="text-muted text-sm"><?= htmlspecialchars($pv['notes'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cartes mobile -->
                            <div class="prog-cards-mobile">
                                <?php foreach ($pvs as $pv): ?>
                                    <div class="prog-card-mobile">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
              <span style="font-weight:600;font-size:.9rem;color:var(--gris-900)">
                <?= htmlspecialchars($pv['classe_label'] ?? 'Tout le cycle') ?>
              </span>
                                            <span class="badge <?= $pv['annee_entree'] >= 2025 ? 'badge--vert' : 'badge--gris' ?>">
                <?= $pv['annee_entree'] ?><?= $pv['annee_entree'] >= 2025 ? ' ✦ Nouveau' : '' ?>
              </span>
                                        </div>
                                        <div style="font-size:.85rem;color:var(--gris-700);margin-bottom:4px">
                                            <?= htmlspecialchars($pv['label']) ?>
                                        </div>
                                        <?php if (!empty($pv['notes'])): ?>
                                            <div style="font-size:.78rem;color:var(--gris-500)">
                                                <?= htmlspecialchars($pv['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="alert alert--info mt-24">
            💡 Les programmes marqués <strong>✦ Nouveau 2025</strong> correspondent aux textes publiés pour la rentrée scolaire 2025.
        </div>
    </div>
<?php include __DIR__.'/partials/layout_end.php'; ?>
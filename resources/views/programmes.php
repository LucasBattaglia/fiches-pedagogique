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
      <div class="table-wrap" style="border:none;border-radius:0">
        <table>
          <thead>
            <tr>
              <th>Matière / Domaine</th>
              <th>Classe</th>
              <th>Programme</th>
              <th>Rentrée</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matieres as $matiereLabel => $pvs): ?>
              <?php foreach ($pvs as $i => $pv): ?>
              <tr>
                <?php if ($i === 0): ?>
                <td rowspan="<?= count($pvs) ?>" style="font-weight:600;color:var(--gris-900);vertical-align:top;padding-top:12px">
                  <?= htmlspecialchars($matiereLabel) ?>
                </td>
                <?php endif; ?>
                <td><?= htmlspecialchars($pv['classe_label'] ?? 'Tout le cycle') ?></td>
                <td><?= htmlspecialchars($pv['label']) ?></td>
                <td>
                  <span class="badge <?= $pv['annee_entree'] >= 2025 ? 'badge--vert' : 'badge--gris' ?>">
                    <?= $pv['annee_entree'] ?>
                    <?= $pv['annee_entree'] >= 2025 ? ' ✦ Nouveau' : '' ?>
                  </span>
                </td>
                <td class="text-muted text-sm"><?= htmlspecialchars($pv['notes'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="alert alert--info mt-24">
    💡 Les programmes marqués <strong>Nouveau 2025</strong> correspondent aux textes publiés pour la rentrée scolaire 2025.
    Les autres programmes restent en vigueur sans modification.
  </div>
</div>
<?php include __DIR__.'/partials/layout_end.php'; ?>

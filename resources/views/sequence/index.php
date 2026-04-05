<?php
$pageTitle = 'Mes séquences';
$activeNav = 'seq-list';
include __DIR__.'/../partials/layout_start.php';
?>
<div class="container">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-family:var(--font-titre);font-size:1.75rem">Mes séquences</h1>
      <p class="text-muted"><?= count($sequences) ?> séquence(s)</p>
    </div>
    <a href="/sequence/create" class="btn btn--primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle séquence
    </a>
  </div>

  <?php if (empty($sequences)): ?>
  <div class="empty-state">
    <div class="empty-state__icon">📋</div>
    <h3>Aucune séquence pour l'instant</h3>
    <p>Créez votre première fiche de séquence pédagogique.</p>
    <a href="/sequence/create" class="btn btn--primary">Créer une séquence</a>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Titre</th>
          <th>Matière</th>
          <th>Classe</th>
          <th>Séances</th>
          <th>Programme</th>
          <th>Statut</th>
          <th>Modifiée</th>
          <th style="width:120px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sequences as $seq): ?>
        <tr>
          <td>
            <a href="/sequence/<?= $seq['id'] ?>" style="font-weight:600;color:var(--gris-900)">
              <?= htmlspecialchars($seq['titre']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($seq['matiere_label'] ?? '—') ?></td>
          <td><?= htmlspecialchars($seq['classe_label'] ?? $seq['cycle_label'] ?? '—') ?></td>
          <td style="text-align:center"><?= $seq['nb_seances_reelles'] ?? 0 ?></td>
          <td>
            <?php if (!empty($seq['annee_entree'])): ?>
              <span class="badge <?= $seq['annee_entree'] >= 2025 ? 'badge--vert' : 'badge--gris' ?>">
                <?= $seq['annee_entree'] ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($seq['is_public']): ?>
              <span class="badge badge--vert">Public</span>
            <?php else: ?>
              <span class="badge badge--gris">Privé</span>
            <?php endif; ?>
          </td>
          <td class="text-muted text-sm"><?= date('d/m/y', strtotime($seq['updated_at'])) ?></td>
          <td style="display:flex;gap:4px">
            <a href="/sequence/<?= $seq['id'] ?>" class="btn btn--ghost btn--sm" title="Voir">👁</a>
            <a href="/sequence/<?= $seq['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Modifier">✏️</a>
            <a href="/sequence/<?= $seq['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank" title="PDF">📄</a>
            <form action="/sequence/<?= $seq['id'] ?>/delete" method="post" style="display:inline">
              <button type="submit" class="btn btn--ghost btn--sm" title="Supprimer"
                data-confirm="Supprimer « <?= htmlspecialchars($seq['titre']) ?> » et toutes ses séances ?">🗑</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>

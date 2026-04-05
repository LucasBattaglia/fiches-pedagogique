<?php
$pageTitle = 'Explorer les fiches publiques';
$activeNav = 'explorer';
include __DIR__.'/partials/layout_start.php';
?>
<div class="container">
  <div class="breadcrumb">
    <a href="/dashboard">Tableau de bord</a>
    <span class="breadcrumb__sep">›</span>
    <span>Explorer</span>
  </div>

  <div style="margin-bottom:28px">
    <h1 style="font-family:var(--font-titre);font-size:1.8rem;margin-bottom:8px">Fiches publiques</h1>
    <p class="text-muted">Retrouvez et réutilisez les fiches partagées par la communauté enseignante.</p>
  </div>

  <!-- Filtres -->
  <form method="get" action="/explorer" style="background:white;border:1px solid var(--gris-300);border-radius:var(--rayon-lg);padding:20px;margin-bottom:28px;box-shadow:var(--ombre)">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
      <div class="form-group" style="margin:0">
        <label for="q">Rechercher</label>
        <input type="text" id="q" name="q" placeholder="Titre, objectif…" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
      </div>
      <div class="form-group" style="margin:0">
        <label for="cycle_id">Cycle</label>
        <select id="cycle_id" name="cycle_id">
          <option value="">Tous les cycles</option>
          <?php foreach ($cycles as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($filters['cycle_id']??'')==$c['id']?'selected':'' ?>>
              <?= htmlspecialchars($c['code']) ?> – <?= htmlspecialchars($c['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label for="matiere_id">Matière</label>
        <select id="matiere_id" name="matiere_id">
          <option value="">Toutes</option>
          <?php foreach ($matieres as $m): ?>
            <option value="<?= $m['id'] ?>" <?= ($filters['matiere_id']??'')==$m['id']?'selected':'' ?>>
              <?= htmlspecialchars($m['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn--primary">Filtrer</button>
        <a href="/explorer" class="btn btn--ghost">✕</a>
      </div>
    </div>
  </form>

  <!-- Résultats -->
  <?php if (empty($sequences)): ?>
  <div class="empty-state">
    <div class="empty-state__icon">🔍</div>
    <h3>Aucune fiche trouvée</h3>
    <p>Modifiez vos critères de recherche ou explorez sans filtre.</p>
    <a href="/explorer" class="btn btn--outline">Voir toutes les fiches</a>
  </div>
  <?php else: ?>
  <div style="margin-bottom:16px;color:var(--gris-500);font-size:.88rem">
    <?= count($sequences) ?> fiche(s) trouvée(s)
  </div>
  <div class="cards-grid">
    <?php foreach ($sequences as $seq): ?>
    <div class="card">
      <div class="card__header">
        <div style="flex:1;min-width:0">
          <div class="card__titre" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($seq['titre']) ?>
          </div>
          <div class="card__meta">
            <?php if ($seq['matiere_label']): ?>
              <span class="badge badge--bleu"><?= htmlspecialchars($seq['matiere_label']) ?></span>
            <?php endif; ?>
            <?php if ($seq['cycle_label']): ?>
              <span class="badge badge--gris"><?= htmlspecialchars($seq['cycle_label']) ?></span>
            <?php endif; ?>
            <?php if ($seq['classe_label']): ?>
              <span class="badge badge--ambre"><?= htmlspecialchars($seq['classe_label']) ?></span>
            <?php endif; ?>
            <?php if (!empty($seq['annee_entree']) && $seq['annee_entree'] >= 2025): ?>
              <span class="badge badge--vert">Programme 2025</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="card__body" style="padding-top:10px">
        <div class="text-sm text-muted" style="display:flex;align-items:center;gap:8px">
          <span>👤 <?= htmlspecialchars($seq['prenom'].' '.$seq['nom']) ?></span>
          <span>·</span>
          <span>📅 <?= date('d/m/Y', strtotime($seq['created_at'])) ?></span>
          <span>·</span>
          <span>📋 <?= $seq['nb_seances_reelles'] ?? 0 ?> séance(s)</span>
        </div>
      </div>
      <div class="card__footer" style="display:flex;gap:8px;justify-content:flex-end">
        <a href="/sequence/<?= $seq['id'] ?>" class="btn btn--outline btn--sm">Consulter</a>
        <?php if (\src\Service\AuthService::isLoggedIn()): ?>
          <form action="/sequence/<?= $seq['id'] ?>/fork" method="post" style="display:inline">
            <button type="submit" class="btn btn--ghost btn--sm" title="Dupliquer dans mes fiches">📋 Copier</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__.'/partials/layout_end.php'; ?>

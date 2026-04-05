<?php
$pageTitle = 'Tableau de bord';
$activeNav = 'dashboard';
include __DIR__.'/partials/layout_start.php';
use src\Service\AuthService;
$user = AuthService::currentUser();
?>
<div class="container">

  <!-- Bienvenue -->
  <div style="margin-bottom:32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-family:var(--font-titre);font-size:1.75rem;color:var(--gris-900)">
        Bonjour, <?= htmlspecialchars($user['prenom'] ?? 'Enseignant') ?> 👋
      </h1>
      <p class="text-muted">Voici un aperçu de vos fiches pédagogiques.</p>
    </div>
    <a href="/sequence/create" class="btn btn--primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle séquence
    </a>
  </div>

  <!-- Stats rapides -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:36px">
    <?php
    $stats = [
      ['label'=>'Mes séquences', 'value'=>count($sequences), 'icon'=>'📋', 'color'=>'bleu'],
      ['label'=>'Fiches publiques', 'value'=>count($publiques), 'icon'=>'🌐', 'color'=>'vert'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="card" style="text-align:center;padding:20px 12px">
      <div style="font-size:2rem;margin-bottom:8px"><?= $s['icon'] ?></div>
      <div style="font-size:2rem;font-weight:700;color:var(--<?= $s['color'] ?>-med, var(--bleu-med))"><?= $s['value'] ?></div>
      <div class="text-muted text-sm"><?= $s['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

    <!-- Mes dernières séquences -->
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="font-size:1.1rem;font-weight:600">Mes dernières séquences</h2>
        <a href="/sequence/list" class="btn btn--ghost btn--sm">Voir tout →</a>
      </div>

      <?php if (empty($sequences)): ?>
      <div class="empty-state" style="padding:40px 20px">
        <div class="empty-state__icon">📝</div>
        <h3>Aucune séquence</h3>
        <p>Commencez par créer votre première fiche de séquence.</p>
        <a href="/sequence/create" class="btn btn--primary btn--sm">Créer une séquence</a>
      </div>
      <?php else: ?>
      <div class="seances-list">
        <?php foreach ($sequences as $seq): ?>
        <a href="/sequence/<?= $seq['id'] ?>" style="text-decoration:none;display:block">
          <div class="seance-item">
            <div class="seance-item__num" style="background:var(--bleu-med);border-radius:var(--rayon);width:40px;height:40px;font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0">
              <?= htmlspecialchars(mb_substr($seq['matiere_label'] ?? '?', 0, 2)) ?>
            </div>
            <div class="seance-item__info" style="flex:1;min-width:0">
              <div class="seance-item__titre" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($seq['titre']) ?>
              </div>
              <div class="seance-item__meta">
                <?= htmlspecialchars($seq['cycle_label'] ?? '') ?>
                <?php if ($seq['classe_label']): ?> · <?= htmlspecialchars($seq['classe_label']) ?><?php endif; ?>
                · <?= $seq['nb_seances_reelles'] ?? 0 ?> séance(s)
              </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
              <?php if ($seq['is_public']): ?>
                <span class="badge badge--vert">Public</span>
              <?php else: ?>
                <span class="badge badge--gris">Privé</span>
              <?php endif; ?>
              <span class="text-sm text-muted"><?= date('d/m/y', strtotime($seq['updated_at'])) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Fiches publiques récentes -->
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="font-size:1.1rem;font-weight:600">Fiches publiques récentes</h2>
        <a href="/explorer" class="btn btn--ghost btn--sm">Explorer →</a>
      </div>

      <?php if (empty($publiques)): ?>
      <div class="empty-state" style="padding:40px 20px">
        <div class="empty-state__icon">🌐</div>
        <h3>Aucune fiche publique</h3>
        <p>Partagez vos fiches pour qu'elles apparaissent ici !</p>
      </div>
      <?php else: ?>
      <div class="cards-grid" style="grid-template-columns:1fr">
        <?php foreach ($publiques as $seq): ?>
        <div class="card">
          <div class="card__header">
            <div>
              <div class="card__titre"><?= htmlspecialchars($seq['titre']) ?></div>
              <div class="card__meta">
                <?php if ($seq['matiere_label']): ?>
                  <span class="badge badge--bleu"><?= htmlspecialchars($seq['matiere_label']) ?></span>
                <?php endif; ?>
                <?php if ($seq['classe_label']): ?>
                  <span class="badge badge--gris"><?= htmlspecialchars($seq['classe_label']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="card__footer" style="display:flex;align-items:center;justify-content:space-between">
            <span class="text-sm text-muted">Par <?= htmlspecialchars($seq['prenom'].' '.$seq['nom']) ?></span>
            <a href="/sequence/<?= $seq['id'] ?>" class="btn btn--outline btn--sm">Voir</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
<?php include __DIR__.'/partials/layout_end.php'; ?>

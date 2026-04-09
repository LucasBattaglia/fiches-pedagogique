<?php
$pageTitle = 'Mes séances';
$activeNav = 'seance-list';
include __DIR__ . '/../partials/layout_start.php';

// Séparer séances autonomes et liées à une séquence
$autonomes = array_values(array_filter($seances, fn($s) => empty($s['sequence_id'])));
$liees     = array_values(array_filter($seances, fn($s) => !empty($s['sequence_id'])));
?>
    <div class="container">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem">Mes séances</h1>
                <p class="text-muted"><?= count($seances) ?> séance(s) au total</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="<?= $base ?>/situation/create" class="btn btn--ambre btn--sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Nouvelle situation
                </a>
                <a href="<?= $base ?>/seance/create" class="btn btn--vert">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Nouvelle séance
                </a>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs" style="margin-bottom:24px">
            <button class="tab-btn active" data-tab="toutes">
                Toutes
                <span class="badge badge--gris" style="margin-left:6px"><?= count($seances) ?></span>
            </button>
            <button class="tab-btn" data-tab="autonomes">
                Séances autonomes
                <span class="badge badge--ambre" style="margin-left:6px"><?= count($autonomes) ?></span>
            </button>
            <button class="tab-btn" data-tab="liees">
                Liées à une séquence
                <span class="badge badge--bleu" style="margin-left:6px"><?= count($liees) ?></span>
            </button>
        </div>

        <!-- Toutes -->
        <div class="tab-panel active" id="tab-toutes">
            <?php if (empty($seances)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">📅</div>
                    <h3>Aucune séance</h3>
                    <p>Créez une séance autonome ou depuis une séquence.</p>
                    <a href="<?= $base ?>/seance/create" class="btn btn--vert">Créer une séance</a>
                </div>
            <?php else: ?>
                <?= renderSeanceTable($seances, $base) ?>
            <?php endif; ?>
        </div>

        <!-- Autonomes -->
        <div class="tab-panel" id="tab-autonomes">
            <?php if (empty($autonomes)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🔓</div>
                    <h3>Aucune séance autonome</h3>
                    <p>Les séances autonomes ne sont rattachées à aucune séquence. Vous pouvez les créer librement et les rattacher plus tard.</p>
                    <a href="<?= $base ?>/seance/create" class="btn btn--vert">Créer une séance autonome</a>
                </div>
            <?php else: ?>
                <div class="alert alert--info" style="margin-bottom:16px">
                    💡 Ces séances ne sont rattachées à aucune séquence. Vous pouvez les utiliser librement ou les ajouter à une séquence depuis leur page de détail.
                </div>
                <?= renderSeanceTable($autonomes, $base) ?>
            <?php endif; ?>
        </div>

        <!-- Liées -->
        <div class="tab-panel" id="tab-liees">
            <?php if (empty($liees)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🔗</div>
                    <h3>Aucune séance liée</h3>
                    <p>Créez des séances directement depuis une séquence.</p>
                    <a href="<?= $base ?>/sequence/list" class="btn btn--primary">Voir mes séquences</a>
                </div>
            <?php else: ?>
                <?= renderSeanceTable($liees, $base) ?>
            <?php endif; ?>
        </div>
    </div>

<?php
function renderSeanceTable(array $seances, string $base): string {
    if (empty($seances)) return '';
    ob_start(); ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Titre</th>
                <th>Séquence liée</th>
                <th style="width:80px">Durée</th>
                <th style="width:90px;text-align:center">Situations</th>
                <th style="width:160px">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($seances as $s): ?>
                <tr>
                    <td>
                        <a href="<?= $base ?>/seance/<?= $s['id'] ?>/show" style="font-weight:600;color:var(--gris-900)">
                            <?= htmlspecialchars($s['titre']) ?>
                        </a>
                        <?php if (empty($s['sequence_id'])): ?>
                            <span class="badge badge--ambre" style="margin-left:6px;font-size:.68rem">Autonome</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted text-sm">
                        <?php if (!empty($s['sequence_titre'])): ?>
                            <a href="<?= $base ?>/sequence/<?= $s['sequence_id'] ?>" style="color:var(--bleu-med)">
                                <?= htmlspecialchars($s['sequence_titre']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--gris-300)">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $s['duree'] ? $s['duree'].' min' : '—' ?></td>
                    <td style="text-align:center">
                        <span class="badge <?= ($s['nb_situations'] ?? 0) > 0 ? 'badge--ambre' : 'badge--gris' ?>">
                            <?= $s['nb_situations'] ?? 0 ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            <a href="<?= $base ?>/seance/<?= $s['id'] ?>/show" class="btn btn--ghost btn--sm" title="Voir">👁</a>
                            <a href="<?= $base ?>/seance/<?= $s['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Modifier">✏️</a>
                            <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank" title="PDF">📄</a>
                            <a href="<?= $base ?>/situation/create?seance_id=<?= $s['id'] ?>" class="btn btn--ghost btn--sm" title="Ajouter situation">🎯</a>
                            <form action="<?= $base ?>/seance/<?= $s['id'] ?>/delete" method="post" style="display:inline">
                                <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette séance ?">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php return ob_get_clean();
}
?>

    <script>
        // Gestion des onglets
        document.querySelectorAll('.tab-btn[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn[data-tab]').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab)?.classList.add('active');
            });
        });
    </script>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>
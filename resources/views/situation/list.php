<?php
$pageTitle = 'Mes situations';
$activeNav = 'situation-list';
include __DIR__ . '/../partials/layout_start.php';

$autonomes = array_values(array_filter($situations, fn($s) => empty($s['seance_id'])));
$liees     = array_values(array_filter($situations, fn($s) => !empty($s['seance_id'])));
?>
    <div class="container">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem">Mes situations</h1>
                <p class="text-muted"><?= count($situations) ?> situation(s) au total</p>
            </div>
            <a href="<?= $base ?>/situation/create" class="btn btn--ambre">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Nouvelle situation
            </a>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="toutes">
                Toutes
                <span class="badge badge--gris" style="margin-left:6px"><?= count($situations) ?></span>
            </button>
            <button class="tab-btn" data-tab="autonomes">
                Autonomes
                <span class="badge badge--ambre" style="margin-left:6px"><?= count($autonomes) ?></span>
            </button>
            <button class="tab-btn" data-tab="liees">
                Liées à une séance
                <span class="badge badge--bleu" style="margin-left:6px"><?= count($liees) ?></span>
            </button>
        </div>

        <div class="tab-panel active" id="tab-toutes">
            <?php if (empty($situations)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🎯</div>
                    <h3>Aucune situation</h3>
                    <p>Créez votre première fiche de situation pédagogique.</p>
                    <a href="<?= $base ?>/situation/create" class="btn btn--ambre">Créer une situation</a>
                </div>
            <?php else: ?>
                <?= renderSituationTable($situations, $base) ?>
            <?php endif; ?>
        </div>

        <div class="tab-panel" id="tab-autonomes">
            <?php if (empty($autonomes)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🔓</div>
                    <h3>Aucune situation autonome</h3>
                    <p>Les situations autonomes peuvent être créées sans séance parente et rattachées plus tard.</p>
                    <a href="<?= $base ?>/situation/create" class="btn btn--ambre">Créer une situation autonome</a>
                </div>
            <?php else: ?>
                <div class="alert alert--info" style="margin-bottom:16px">
                    💡 Ces situations ne sont rattachées à aucune séance. Vous pouvez les réutiliser dans différentes séances.
                </div>
                <?= renderSituationTable($autonomes, $base) ?>
            <?php endif; ?>
        </div>

        <div class="tab-panel" id="tab-liees">
            <?php if (empty($liees)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🔗</div>
                    <h3>Aucune situation liée</h3>
                    <p>Créez des situations depuis une séance pour les lier automatiquement.</p>
                </div>
            <?php else: ?>
                <?= renderSituationTable($liees, $base) ?>
            <?php endif; ?>
        </div>
    </div>

<?php
function renderSituationTable(array $situations, string $base): string {
    ob_start(); ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Titre</th>
                <th>Séance liée</th>
                <th style="width:80px">Durée</th>
                <th>Objectif moteur</th>
                <th style="width:140px">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($situations as $sit): ?>
                <tr>
                    <td>
                        <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/show" style="font-weight:600;color:var(--gris-900)">
                            <?= htmlspecialchars($sit['titre']) ?>
                        </a>
                        <?php if (empty($sit['seance_id'])): ?>
                            <span class="badge badge--ambre" style="margin-left:6px;font-size:.68rem">Autonome</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted text-sm">
                        <?php if (!empty($sit['seance_titre'])): ?>
                            <a href="<?= $base ?>/seance/<?= $sit['seance_id'] ?>/show" style="color:var(--bleu-med)">
                                Séance <?= $sit['seance_numero'] ?? '' ?> — <?= htmlspecialchars($sit['seance_titre']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--gris-300)">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $sit['duree'] ? $sit['duree'].' min' : '—' ?></td>
                    <td class="text-sm text-muted">
                        <?= htmlspecialchars(mb_strimwidth($sit['objectif_moteur'] ?? '', 0, 60, '…')) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/show" class="btn btn--ghost btn--sm" title="Voir">👁</a>
                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Modifier">✏️</a>
                            <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank" title="PDF">📄</a>
                            <form action="<?= $base ?>/situation/<?= $sit['id'] ?>/delete" method="post" style="display:inline">
                                <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette situation ?">🗑</button>
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
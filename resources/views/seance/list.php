<?php
$pageTitle = 'Mes séances';
$activeNav = 'seance-list';
include __DIR__ . '/../partials/layout_start.php';
?>
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem">Mes séances</h1>
                <p class="text-muted"><?= count($seances) ?> séance(s)</p>
            </div>
            <a href="<?= $base ?>/seance/create" class="btn btn--vert">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Nouvelle séance
            </a>
        </div>

        <?php if (empty($seances)): ?>
            <div class="empty-state">
                <div class="empty-state__icon">📅</div>
                <h3>Aucune séance</h3>
                <p>Créez une séance autonome ou depuis une séquence.</p>
                <a href="<?= $base ?>/seance/create" class="btn btn--vert">Créer une séance</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Séquence liée</th>
                        <th style="width:80px">Durée</th>
                        <th style="width:80px">Situations</th>
                        <th style="width:140px">Actions</th>
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
                                    <span class="badge badge--ambre" style="margin-left:6px">Autonome</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm">
                                <?php if (!empty($s['sequence_titre'])): ?>
                                    <a href="<?= $base ?>/sequence/<?= $s['sequence_id'] ?>"><?= htmlspecialchars($s['sequence_titre']) ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= $s['duree'] ? $s['duree'].' min' : '—' ?></td>
                            <td style="text-align:center"><?= $s['nb_situations'] ?? 0 ?></td>
                            <td style="display:flex;gap:4px">
                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/show" class="btn btn--ghost btn--sm" title="Voir">👁</a>
                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Modifier">✏️</a>
                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank" title="PDF">📄</a>
                                <form action="<?= $base ?>/seance/<?= $s['id'] ?>/delete" method="post" style="display:inline">
                                    <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer cette séance ?">🗑</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php include __DIR__ . '/../partials/layout_end.php'; ?>
<?php
$pageTitle = 'Gestion des Référentiels';
$activeNav = 'admin';
include __DIR__.'/../../partials/layout_start.php';
?>

    <div class="container">
        <div class="breadcrumb">
            <a href="/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <span>Configuration des programmes</span>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px">
            <div>
                <h1 style="font-family:var(--font-titre); font-size:1.8rem; margin:0">Programmes Officiels</h1>
                <p class="text-muted">Structure des domaines, compétences et objectifs par année.</p>
            </div>
            <button class="btn btn--primary" onclick="alert('Action: Nouveau programme')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Nouveau Programme
            </button>
        </div>

        <?php if (empty($versions)): ?>
            <div class="empty-state">
                <div class="empty-state__icon">📚</div>
                <h3>Aucun programme configuré</h3>
                <p>Commencez par ajouter une matière et une année de référence.</p>
            </div>
        <?php else: ?>
            <?php foreach ($versions as $annee => $matieres): ?>
                <div class="fiche-section mb-32">
                    <div class="fiche-section__title <?= $annee >= 2025 ? 'fiche-section--vert' : 'fiche-section--bleu' ?>">
                        Programme <?= htmlspecialchars((string)$annee) ?>
                    </div>

                    <div class="card__body" style="padding:0">
                        <?php foreach ($matieres as $matiereLabel => $pvs): ?>
                            <div class="matiere-group" style="padding: 16px; border-bottom: 1px solid var(--gris-100)">
                                <h3 style="font-size: 1.1rem; color: var(--bleu); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 1.2rem">📘</span> <?= htmlspecialchars($matiereLabel) ?>
                                </h3>

                                <div class="table-wrap" style="border: none; border-radius: 0; background: var(--gris-50); border-radius: var(--rayon)">
                                    <table style="background: transparent">
                                        <thead>
                                        <tr>
                                            <th style="width: 40%">Classe</th>
                                            <th style="text-align: center">Statut</th>
                                            <th style="text-align: right">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($pvs as $pv): ?>
                                            <tr>
                                                <td style="font-weight: 500">
                                                    <?= htmlspecialchars($pv['classe_label'] ?? $pv['cycle_label'] ?? 'Tout le cycle') ?>
                                                </td>
                                                <td style="text-align: center">
                                                    <?php if ($pv['en_vigueur']): ?>
                                                        <button class="btn btn--sm btn--primary" onclick="toggleVigueur(<?= $pv['id'] ?>, 0)" style="min-width: 120px">
                                                            ✅ En vigueur
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn--sm btn--outline" onclick="toggleVigueur(<?= $pv['id'] ?>, 1)" style="min-width: 120px">
                                                            🔘 Archive
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right">
                                                    <div style="display:flex; justify-content:flex-end; gap:8px">
                                                        <a href="/admin/programmes/version/<?= $pv['id'] ?>" class="btn btn--ghost btn--sm" title="Modifier le contenu">
                                                            ✏️ Editer
                                                        </a>
                                                        <button class="btn btn--ghost btn--sm text-danger" onclick="deleteVersion(<?= $pv['id'] ?>)" title="Supprimer">
                                                            🗑
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <style>
        .matiere-group:last-child { border-bottom: none; }
        .table-wrap th { background: transparent; color: var(--gris-500); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .table-wrap td { border-top: 1px solid rgba(0,0,0,0.03); }
    </style>

    <script>
        async function toggleVigueur(id, state) {
            if(confirm('Changer la visibilité de ce programme pour cette classe ?')) {
                window.location.href = `/admin/programmes/version/${id}/vigueur?state=${state}`;
            }
        }
        async function deleteVersion(id) {
            if(confirm('Supprimer définitivement ce programme pour cette classe ?')) {
                window.location.href = `/admin/programmes/version/${id}/delete`;
            }
        }
    </script>

<?php include __DIR__.'/../../partials/layout_end.php'; ?>
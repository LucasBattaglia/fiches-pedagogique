<?php
$pageTitle = "Configuration du programme";
include __DIR__.'/../../partials/layout_start.php';
?>

    <div class="container">
        <div class="breadcrumb">
            <a href="/admin/programmes">Gestion des programmes</a>
            <span class="breadcrumb__sep">›</span>
            <span>Contenu pédagogique</span>
        </div>

        <div class="card mb-24">
            <div class="card__header" style="background:var(--bleu-pale); display:flex; justify-content:space-between; align-items:center">
                <div>
                    <h2 class="card__titre" style="margin:0">
                        <?= htmlspecialchars($version['matiere_label']) ?>
                        <span class="text-muted" style="font-weight:400">— Programme <?= htmlspecialchars((string)$version['annee_entree']) ?></span>
                    </h2>
                    <div class="text-sm text-muted mt-4"><?= htmlspecialchars($version['classe_label'] ?? 'Cycle complet') ?></div>
                </div>
                <button class="btn btn--primary btn--sm" onclick="addItem(null, 1)">
                    + Ajouter un Domaine
                </button>
            </div>

            <div class="card__body">
                <div id="tree-editor">
                    <?php if (empty($tree)): ?>
                        <div class="text-center p-32 text-muted">
                            Ce programme est vide. Commencez par ajouter un domaine.
                        </div>
                    <?php else: ?>
                        <?php renderAdminTree($tree); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card__footer">
                <p class="text-sm text-muted">💡 Les modifications sur les textes et codes sont enregistrées automatiquement quand vous quittez le champ.</p>
            </div>
        </div>
    </div>

<?php
function renderAdminTree($items, $level = 1) {
    // Styles par niveau
    $colors = [1 => 'var(--bleu)', 2 => 'var(--ambre)', 3 => 'var(--vert)'];
    $bgLevels = [1 => 'var(--bleu-pale)', 2 => 'transparent', 3 => 'transparent'];
    $labels = [1 => 'Domaine', 2 => 'Compétence', 3 => 'Objectif'];

    foreach ($items as $item): ?>
        <div class="tree-node" style="margin-left:<?= ($level-1)*32 ?>px; margin-bottom:8px; border-left:3px solid <?= $colors[$level] ?>; background:<?= $bgLevels[$level] ?>; border-radius:4px">
            <div class="flex items-center gap-8 p-8 group">
                <input type="text" class="input-code" placeholder="Code"
                       value="<?= htmlspecialchars($item['code'] ?? '') ?>"
                       onblur="saveField(<?= $item['id'] ?>, 'code', this.value)"
                       style="width:70px; font-family:monospace; font-size:0.75rem; border:1px solid var(--gris-300); border-radius:4px; padding:2px 6px">

                <input type="text" class="input-label" placeholder="Libellé de <?= $labels[$level] ?>"
                       value="<?= htmlspecialchars($item['label']) ?>"
                       onblur="saveField(<?= $item['id'] ?>, 'label', this.value)"
                       style="flex:1; border:1px solid transparent; background:transparent; padding:4px 8px; font-weight:<?= $level==1?'600':'400' ?>; font-size:<?= $level==3?'0.9rem':'1rem' ?>">

                <div class="flex gap-4 opacity-0 group-hover-opacity-100 transition-opacity">
                    <?php if($level < 3): ?>
                        <button class="btn btn--ghost btn--sm" onclick="addItem(<?= $item['id'] ?>, <?= $level+1 ?>)" title="Ajouter un sous-élément">
                            ➕
                        </button>
                    <?php endif; ?>
                    <button class="btn btn--ghost btn--sm text-danger" onclick="deleteItem(<?= $item['id'] ?>)" title="Supprimer">
                        🗑
                    </button>
                </div>
            </div>

            <?php if (!empty($item['children'])): ?>
                <div class="tree-children">
                    <?php renderAdminTree($item['children'], $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach;
}
?>

    <style>
        .tree-node { transition: background 0.2s; }
        .input-label:focus { border-color: var(--bleu-med) !important; background: white !important; outline: none; }
        .group:hover .opacity-0 { opacity: 1; }
        .tree-children { padding-bottom: 4px; }
    </style>

    <script>
        // Sauvegarde automatique au "blur" (quand on clique ailleurs)
        async function saveField(id, field, value) {
            const data = { id: id };
            data[field] = value;
            try {
                await fetch('/api/admin/programme-items', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
            } catch (e) { alert('Erreur lors de la sauvegarde'); }
        }

        async function addItem(parentId, level) {
            const label = prompt(`Nom du nouveau ${level === 1 ? 'Domaine' : (level === 2 ? 'Compétence' : 'Objectif')} :`);
            if(!label) return;

            const res = await fetch('/api/admin/programme-items', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    version_id: <?= (int)$version['id'] ?>,
                    parent_id: parentId,
                    niveau: level,
                    label: label,
                    ordre: 0
                })
            });
            if (res.ok) location.reload();
        }

        async function deleteItem(id) {
            if(confirm('Supprimer cet élément et tout son contenu (sous-compétences, etc.) ?')) {
                const res = await fetch(`/api/admin/programme-items/${id}/delete`, { method: 'POST' });
                if (res.ok) location.reload();
            }
        }
    </script>

<?php include __DIR__.'/../../partials/layout_end.php'; ?>
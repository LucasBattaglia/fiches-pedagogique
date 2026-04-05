<?php
/**
 * resources/views/admin/programmes/index.php
 * Gestion des programmes — triés par Cycle › Année › Matière › Classe
 * Tout est éditable inline. Bouton "En vigueur / Archivé" par ligne.
 */
$pageTitle = 'Gestion des Programmes';
$activeNav = 'admin';
include __DIR__.'/../../partials/layout_start.php';
?>

    <div class="container admin-prog">

        <!-- ── En-tête page ─────────────────────────────────────────── -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px">
            <div>
                <div class="breadcrumb">
                    <a href="/dashboard">Tableau de bord</a>
                    <span class="breadcrumb__sep">›</span>
                    <span>Programmes officiels</span>
                </div>
                <h1 style="font-family:var(--font-titre);font-size:1.8rem;margin:0">Programmes officiels</h1>
                <p class="text-muted text-sm" style="margin-top:4px">
                    Triés par <strong>cycle › année › matière › classe</strong>.
                    Cliquez sur un champ pour l'éditer, puis cliquez ailleurs pour sauvegarder.
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn--ghost btn--sm" onclick="expandAll()">↓ Tout ouvrir</button>
                <button class="btn btn--ghost btn--sm" onclick="collapseAll()">↑ Tout fermer</button>
                <button class="btn btn--primary btn--sm" onclick="openAddModal()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Nouveau programme
                </button>
            </div>
        </div>

        <!-- ── Légende ──────────────────────────────────────────────── -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;font-size:.78rem;color:#6b7280;flex-wrap:wrap;background:white;border:1px solid var(--gris-300);border-radius:8px;padding:10px 16px">
    <span>
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a;margin-right:5px;vertical-align:middle"></span>
      <strong>En vigueur</strong> = visible dans les formulaires de séquence
    </span>
            <span style="color:#d1d5db">|</span>
            <span>
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#9ca3af;margin-right:5px;vertical-align:middle"></span>
      <strong>Archivé</strong> = masqué des formulaires, visible uniquement ici
    </span>
            <span style="color:#d1d5db">|</span>
            <span>✏️ Cliquez sur n'importe quel texte pour l'éditer directement</span>
        </div>

        <?php
        // ── Regroupement : Cycle > Année desc > Matière alpha > Versions ─
        $grouped = [];
        foreach ($versions as $v) {
            $cid   = $v['cycle_id'];
            $annee = (int)$v['annee_entree'];
            $mid   = $v['matiere_id'];

            if (!isset($grouped[$cid])) {
                $grouped[$cid] = [
                        'cycle_label' => $v['cycle_label'],
                        'cycle_code'  => $v['cycle_code'] ?? '',
                        'annees'      => [],
                ];
            }
            if (!isset($grouped[$cid]['annees'][$annee])) {
                $grouped[$cid]['annees'][$annee] = [];
            }
            if (!isset($grouped[$cid]['annees'][$annee][$mid])) {
                $grouped[$cid]['annees'][$annee][$mid] = [
                        'matiere_label' => $v['matiere_label'],
                        'matiere_code'  => $v['matiere_code'] ?? '',
                        'versions'      => [],
                ];
            }
            $grouped[$cid]['annees'][$annee][$mid]['versions'][] = $v;
        }

        // Trier
        ksort($grouped); // cycles par id
        foreach ($grouped as &$cycleData) {
            krsort($cycleData['annees']); // années décroissantes
            foreach ($cycleData['annees'] as &$matieres) {
                uasort($matieres, fn($a, $b) => strcmp($a['matiere_label'], $b['matiere_label']));
            }
        }
        unset($cycleData, $matieres);
        ?>

        <?php if (empty($grouped)): ?>
            <div class="empty-state" style="margin-top:40px">
                <div class="empty-state__icon">📚</div>
                <h3>Aucun programme configuré</h3>
                <p>Commencez par créer un premier programme.</p>
                <button class="btn btn--primary" onclick="openAddModal()">Nouveau programme</button>
            </div>
        <?php else: ?>

            <?php foreach ($grouped as $cycleId => $cycleData):
                // Compter toutes les versions de ce cycle
                $totalVersions = 0;
                foreach ($cycleData['annees'] as $matieres) {
                    foreach ($matieres as $m) { $totalVersions += count($m['versions']); }
                }
                ?>
                <div class="cycle-block" id="cycle-<?= $cycleId ?>">

                    <!-- En-tête cycle -->
                    <div class="cycle-header">
                        <h2><?= htmlspecialchars($cycleData['cycle_label']) ?></h2>
                        <span class="cycle-count-badge"><?= $totalVersions ?> programme(s)</span>
                    </div>

                    <?php foreach ($cycleData['annees'] as $annee => $matieres):
                        $isNew = $annee >= 2025;
                        $nbVersionsAnnee = 0;
                        foreach ($matieres as $m) { $nbVersionsAnnee += count($m['versions']); }
                        ?>
                        <div class="annee-block">

                            <!-- En-tête année (cliquable) -->
                            <div class="annee-header" onclick="toggleAnnee(this)">
          <span class="annee-pill <?= $isNew ? 'nouveau' : '' ?>">
            <span class="pill-dot"></span>
            Programme <?= (int)$annee ?>
          </span>
                                <?php if ($isNew): ?>
                                    <span class="badge-new">✦ Nouveau</span>
                                <?php endif; ?>
                                <span style="font-size:.78rem;color:#9ca3af;margin-left:4px"><?= $nbVersionsAnnee ?> version(s)</span>
                                <span class="annee-toggle">▼</span>
                            </div>

                            <!-- Corps de l'année -->
                            <div class="annee-body">
                                <?php foreach ($matieres as $matiereId => $matiereData): ?>
                                    <div class="matiere-section">

                                        <!-- Titre matière — éditable inline -->
                                        <div class="matiere-title-row">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0">
                                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                            </svg>

                                            <input
                                                    type="text"
                                                    class="matiere-edit-input"
                                                    value="<?= htmlspecialchars($matiereData['matiere_label']) ?>"
                                                    title="Cliquer pour modifier le nom de la matière"
                                                    onblur="saveMatiere(<?= $matiereId ?>, 'label', this.value, this)"
                                                    onkeydown="if(event.key==='Enter')this.blur()"
                                            >

                                            <span style="font-size:.74rem;color:#9ca3af;flex-shrink:0">Code :</span>
                                            <input
                                                    type="text"
                                                    class="code-input"
                                                    value="<?= htmlspecialchars($matiereData['matiere_code']) ?>"
                                                    title="Code de la matière"
                                                    onblur="saveMatiere(<?= $matiereId ?>, 'code', this.value, this)"
                                                    onkeydown="if(event.key==='Enter')this.blur()"
                                                    placeholder="CODE"
                                            >

                                            <button
                                                    class="btn-ico"
                                                    style="margin-left:4px"
                                                    title="Ajouter un programme pour cette matière et cette année"
                                                    onclick="openAddModal(<?= $cycleId ?>, <?= $matiereId ?>, <?= $annee ?>)"
                                            >➕</button>
                                        </div>

                                        <!-- Tableau des versions par classe -->
                                        <table class="classes-table">
                                            <thead>
                                            <tr>
                                                <th>Classe / Portée</th>
                                                <th>Intitulé du programme</th>
                                                <th>Rentrée</th>
                                                <th>Notes</th>
                                                <th style="text-align:center">Statut</th>
                                                <th style="text-align:right;padding-right:22px">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($matiereData['versions'] as $pv):
                                                $archived = !$pv['en_vigueur'];
                                                ?>
                                                <tr
                                                        id="pv-row-<?= $pv['id'] ?>"
                                                        class="<?= $archived ? 'row-archived' : '' ?>"
                                                >
                                                    <!-- Classe -->
                                                    <td>
                      <span style="font-weight:600;font-size:.84rem;color:<?= $archived ? '#9ca3af' : '#1e3a5f' ?>">
                        <?= htmlspecialchars($pv['classe_label'] ?? '— Tout le cycle —') ?>
                      </span>
                                                    </td>

                                                    <!-- Intitulé -->
                                                    <td>
                                                        <input
                                                                type="text"
                                                                class="inline-input"
                                                                value="<?= htmlspecialchars($pv['label']) ?>"
                                                                title="Intitulé du programme"
                                                                onblur="saveVersion(<?= $pv['id'] ?>, 'label', this.value, this)"
                                                                onkeydown="if(event.key==='Enter')this.blur()"
                                                        >
                                                    </td>

                                                    <!-- Année -->
                                                    <td>
                                                        <input
                                                                type="number"
                                                                class="inline-input inline-input--annee"
                                                                value="<?= (int)$pv['annee_entree'] ?>"
                                                                min="2000" max="2040"
                                                                title="Année de rentrée"
                                                                onblur="saveVersion(<?= $pv['id'] ?>, 'annee_entree', this.value, this)"
                                                                onkeydown="if(event.key==='Enter')this.blur()"
                                                        >
                                                    </td>

                                                    <!-- Notes -->
                                                    <td>
                                                        <input
                                                                type="text"
                                                                class="inline-input inline-input--notes"
                                                                value="<?= htmlspecialchars($pv['notes'] ?? '') ?>"
                                                                placeholder="Notes…"
                                                                title="Notes optionnelles"
                                                                onblur="saveVersion(<?= $pv['id'] ?>, 'notes', this.value, this)"
                                                                onkeydown="if(event.key==='Enter')this.blur()"
                                                        >
                                                    </td>

                                                    <!-- Statut toggle -->
                                                    <td style="text-align:center;white-space:nowrap">
                                                        <button
                                                                class="vigueur-btn <?= $archived ? 'inactive' : 'active' ?>"
                                                                id="vigueur-<?= $pv['id'] ?>"
                                                                onclick="toggleVigueur(<?= $pv['id'] ?>, <?= $archived ? 'true' : 'false' ?>)"
                                                                title="<?= $archived ? 'Cliquer pour remettre en vigueur' : 'Cliquer pour archiver' ?>"
                                                        >
                                                            <span class="vigueur-dot"></span>
                                                            <span id="vigueur-label-<?= $pv['id'] ?>"><?= $archived ? 'Archivé' : 'En vigueur' ?></span>
                                                        </button>
                                                    </td>

                                                    <!-- Actions -->
                                                    <td style="padding-right:22px">
                                                        <div class="action-cell">
                                                            <a href="/admin/programmes/version/<?= $pv['id'] ?>"
                                                               class="btn-ico"
                                                               title="Éditer le contenu pédagogique (compétences, objectifs…)">✏️</a>
                                                            <button
                                                                    class="btn-ico danger"
                                                                    title="Supprimer ce programme"
                                                                    onclick="deleteVersion(<?= $pv['id'] ?>, '<?= addslashes(htmlspecialchars($pv['label'])) ?>')">🗑</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <!-- Ajouter une version pour cette matière -->
                                        <div class="add-row-zone">
                                            <button
                                                    class="add-row-btn"
                                                    onclick="openAddModal(<?= $cycleId ?>, <?= $matiereId ?>, <?= $annee ?>)"
                                            >
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                                                Ajouter une version pour cette matière
                                            </button>
                                        </div>

                                    </div><!-- /.matiere-section -->
                                <?php endforeach; ?>
                            </div><!-- /.annee-body -->
                        </div><!-- /.annee-block -->
                    <?php endforeach; ?>
                </div><!-- /.cycle-block -->
            <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /.admin-prog -->

    <!-- ══════════════════════════════════════════════════════════
         Modal : Créer un nouveau programme
    ════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-add-prog">
        <div class="modal" style="max-width:580px">
            <div class="modal__header">
                <h3>➕ Nouveau programme</h3>
                <button class="btn btn--ghost btn--sm" onclick="closeModal()">✕</button>
            </div>
            <form id="form-add-prog" onsubmit="submitAdd(event)">
                <div class="modal__body">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="add-cycle">Cycle <span class="required">*</span></label>
                            <select name="cycle_id" id="add-cycle" required onchange="loadClassesForModal(this.value)">
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($cycles as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-classe">Classe (optionnel)</label>
                            <select name="classe_id" id="add-classe">
                                <option value="">— Tout le cycle —</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="add-matiere">Matière <span class="required">*</span></label>
                            <select name="matiere_id" id="add-matiere" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-annee">Année de rentrée <span class="required">*</span></label>
                            <input type="number" id="add-annee" name="annee_entree" min="2000" max="2040" value="2025" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-label">Intitulé du programme <span class="required">*</span></label>
                        <input type="text" id="add-label" name="label" required
                               placeholder="Ex : Français – CM1 (2025)">
                    </div>

                    <div class="form-group">
                        <label for="add-notes">Notes</label>
                        <input type="text" id="add-notes" name="notes"
                               placeholder="Notes optionnelles…">
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="add-vigueur" name="en_vigueur" value="1" checked>
                        <label for="add-vigueur">Mettre <strong>en vigueur</strong> immédiatement</label>
                    </div>

                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn--primary" id="btn-add-submit">Créer le programme</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast container -->
    <div id="admin-toast"></div>

    <script>
        // ════════════════════════════════════════════════════════════
        //  TOAST
        // ════════════════════════════════════════════════════════════
        function toast(msg, err = false) {
            const c = document.getElementById('admin-toast');
            const d = document.createElement('div');
            d.className = 'toast-item' + (err ? ' err' : '');
            d.textContent = msg;
            c.appendChild(d);
            setTimeout(() => d.remove(), 3000);
        }

        // ════════════════════════════════════════════════════════════
        //  AJAX helper
        // ════════════════════════════════════════════════════════════
        async function api(url, payload) {
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return await r.json();
            } catch (e) {
                toast('Erreur réseau : ' + e.message, true);
                return null;
            }
        }

        // ════════════════════════════════════════════════════════════
        //  SAVE — champ programme_versions
        // ════════════════════════════════════════════════════════════
        async function saveVersion(id, field, value, inputEl) {
            // Feedback visuel pendant la sauvegarde
            if (inputEl) inputEl.style.borderColor = '#fbbf24';
            const r = await api('/api/admin/programme-versions', { id, [field]: value });
            if (r?.ok) {
                if (inputEl) inputEl.style.borderColor = '#4ade80';
                setTimeout(() => { if (inputEl) inputEl.style.borderColor = 'transparent'; }, 1000);
                toast('✓ Sauvegardé');
            } else {
                if (inputEl) inputEl.style.borderColor = '#f87171';
            }
        }

        // ════════════════════════════════════════════════════════════
        //  SAVE — champ matière
        // ════════════════════════════════════════════════════════════
        async function saveMatiere(id, field, value, inputEl) {
            if (inputEl) inputEl.style.borderColor = '#fbbf24';
            const r = await api('/api/admin/matieres', { id, [field]: value });
            if (r?.ok) {
                if (inputEl) inputEl.style.borderColor = '#4ade80';
                setTimeout(() => { if (inputEl) inputEl.style.borderColor = 'transparent'; }, 1000);
                toast('✓ Matière mise à jour');
            } else {
                if (inputEl) inputEl.style.borderColor = '#f87171';
            }
        }

        // ════════════════════════════════════════════════════════════
        //  TOGGLE VIGUEUR
        // ════════════════════════════════════════════════════════════
        async function toggleVigueur(id, newState) {
            const r = await api('/api/admin/programme-versions', { id, en_vigueur: newState });
            if (!r?.ok) return;

            const btn   = document.getElementById('vigueur-' + id);
            const label = document.getElementById('vigueur-label-' + id);
            const row   = document.getElementById('pv-row-' + id);
            const actif = (newState === true || newState === 'true');

            btn.className = 'vigueur-btn ' + (actif ? 'active' : 'inactive');
            btn.title     = actif ? 'Cliquer pour archiver' : 'Cliquer pour remettre en vigueur';
            btn.setAttribute('onclick', `toggleVigueur(${id}, ${actif ? 'false' : 'true'})`);
            label.textContent = actif ? 'En vigueur' : 'Archivé';

            row.className = actif ? '' : 'row-archived';

            toast(actif ? '✓ Programme activé' : '✓ Programme archivé');
        }

        // ════════════════════════════════════════════════════════════
        //  SUPPRIMER version
        // ════════════════════════════════════════════════════════════
        async function deleteVersion(id, label) {
            if (!confirm(`Supprimer "${label}" ?\n\nCette action supprimera aussi tous les items pédagogiques liés (compétences, objectifs…).`)) return;
            const r = await api(`/api/admin/programme-versions/${id}/delete`, {});
            if (r?.ok) {
                document.getElementById('pv-row-' + id)?.remove();
                toast('✓ Programme supprimé');
            }
        }

        // ════════════════════════════════════════════════════════════
        //  ACCORDION
        // ════════════════════════════════════════════════════════════
        function toggleAnnee(header) {
            const body  = header.nextElementSibling;
            const arrow = header.querySelector('.annee-toggle');
            const col   = body.classList.toggle('collapsed');
            arrow.textContent = col ? '▶' : '▼';
        }
        function expandAll() {
            document.querySelectorAll('.annee-body').forEach(b => b.classList.remove('collapsed'));
            document.querySelectorAll('.annee-toggle').forEach(a => a.textContent = '▼');
        }
        function collapseAll() {
            document.querySelectorAll('.annee-body').forEach(b => b.classList.add('collapsed'));
            document.querySelectorAll('.annee-toggle').forEach(a => a.textContent = '▶');
        }

        // ════════════════════════════════════════════════════════════
        //  MODAL ajout programme
        // ════════════════════════════════════════════════════════════
        function openAddModal(cycleId, matiereId, annee) {
            document.getElementById('form-add-prog').reset();

            if (cycleId) {
                document.getElementById('add-cycle').value = cycleId;
                loadClassesForModal(cycleId);
            }
            if (matiereId) document.getElementById('add-matiere').value = matiereId;
            if (annee)     document.getElementById('add-annee').value   = annee;

            document.getElementById('add-vigueur').checked = true;
            document.getElementById('modal-add-prog').classList.add('open');
        }

        function closeModal() {
            document.getElementById('modal-add-prog').classList.remove('open');
        }

        async function loadClassesForModal(cycleId) {
            const sel = document.getElementById('add-classe');
            sel.innerHTML = '<option value="">— Tout le cycle —</option>';
            if (!cycleId) return;
            try {
                const data = await (await fetch('/api/classes?cycle_id=' + cycleId)).json();
                data.forEach(c => sel.appendChild(new Option(c.label, c.id)));
            } catch(e) {}
        }

        async function submitAdd(e) {
            e.preventDefault();
            const form = e.target;
            const btn  = document.getElementById('btn-add-submit');
            btn.textContent = 'Création…';
            btn.disabled    = true;

            const r = await api('/api/admin/programme-versions/create', {
                cycle_id:    form.cycle_id.value,
                classe_id:   form.classe_id.value || null,
                matiere_id:  form.matiere_id.value,
                annee_entree:form.annee_entree.value,
                label:       form.label.value,
                notes:       form.notes.value,
                en_vigueur:  form.en_vigueur.checked,
            });

            btn.textContent = 'Créer le programme';
            btn.disabled    = false;

            if (r?.ok) {
                toast('✓ Programme créé — rechargement…');
                closeModal();
                setTimeout(() => location.reload(), 900);
            }
        }

        // Fermer modal en cliquant sur l'overlay
        document.getElementById('modal-add-prog').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>

<?php include __DIR__.'/../../partials/layout_end.php'; ?>
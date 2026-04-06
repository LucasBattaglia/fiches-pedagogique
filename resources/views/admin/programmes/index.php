<?php
/**
 * resources/views/admin/programmes/index.php
 * Hiérarchie : Programme (année+cycle) > Matière > Classe
 */
$pageTitle = 'Gestion des Programmes';
$activeNav = 'admin';
include __DIR__.'/../../partials/layout_start.php';
?>

    <style>
        .ap { padding-bottom: 80px; }

        /* ── NIVEAU 1 : Programme ────────────────────────────────── */
        .prog-block {
            margin-bottom: 28px;
            border-radius: 14px;
            overflow: hidden;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            background: white;
        }
        .prog-header {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 22px;
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: white;
        }
        .prog-header-info { flex: 1; min-width: 0; }
        .prog-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem; font-weight: 700;
            display: flex; align-items: center; gap: 10px; margin: 0 0 4px;
        }
        .cycle-pill {
            font-size: .72rem; font-weight: 700;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 99px; padding: 2px 10px;
        }
        .new-pill {
            font-size: .7rem; font-weight: 700;
            background: rgba(34,197,94,.25);
            border: 1px solid rgba(34,197,94,.4);
            border-radius: 99px; padding: 2px 8px; color: #86efac;
        }
        .prog-meta { font-size: .77rem; color: rgba(255,255,255,.6); display: flex; gap: 10px; flex-wrap: wrap; }
        .prog-meta a { color: rgba(255,255,255,.75); text-decoration: underline; }
        .prog-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .bprog {
            display: inline-flex; align-items: center; gap: 5px;
            height: 30px; padding: 0 12px; border-radius: 7px;
            font-size: .78rem; font-weight: 600; cursor: pointer;
            border: 1px solid; font-family: inherit; transition: all .13s;
        }
        .bprog-ghost { background: rgba(255,255,255,.15); color: white; border-color: rgba(255,255,255,.3); }
        .bprog-ghost:hover { background: rgba(255,255,255,.28); }
        .bprog-danger { background: rgba(220,38,38,.25); color: #fca5a5; border-color: rgba(220,38,38,.35); }
        .bprog-danger:hover { background: rgba(220,38,38,.45); color: white; }

        /* ── NIVEAU 2 : Matière ──────────────────────────────────── */
        .mat-block { border-bottom: 1px solid #f0f0f0; }
        .mat-block:last-of-type { border-bottom: none; }
        .mat-header {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 22px 10px 28px;
            background: #f8faff; border-bottom: 1px solid #e0e7ff;
            cursor: pointer; user-select: none; transition: background .12s;
        }
        .mat-header:hover { background: #eef2ff; }
        .mat-icon {
            width: 24px; height: 24px; background: #dbeafe; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; flex-shrink: 0;
        }
        .mat-name { font-size: .9rem; font-weight: 700; color: #1e3a5f; flex: 1; }
        .mat-code { font-size: .72rem; font-weight: 700; font-family: monospace; background: #ede9fe; color: #6d28d9; border-radius: 5px; padding: 2px 8px; }
        .mat-count { font-size: .75rem; color: #9ca3af; }
        .mat-toggle { font-size: .78rem; color: #9ca3af; }
        .mat-acts { display: flex; gap: 5px; }
        .mat-body { display: block; }
        .mat-body.collapsed { display: none; }

        /* ── NIVEAU 3 : Tableau classes ──────────────────────────── */
        .cls-table { width: 100%; border-collapse: collapse; font-size: .855rem; }
        .cls-table thead th {
            background: #fafafa; padding: 7px 14px;
            text-align: left; font-size: .7rem; text-transform: uppercase;
            letter-spacing: .07em; color: #9ca3af; font-weight: 600;
            border-bottom: 1px solid #f0f0f0;
        }
        .cls-table thead th:first-child { padding-left: 52px; }
        .cls-table tbody tr { transition: background .1s; }
        .cls-table tbody tr:hover { background: #fafbfc; }
        .cls-table tbody td {
            padding: 9px 14px; border-bottom: 1px solid #f5f5f5; vertical-align: middle;
        }
        .cls-table tbody tr:last-child td { border-bottom: none; }
        .cls-table tbody td:first-child { padding-left: 52px; }
        .row-arch { opacity: .5; }
        .row-arch:hover { opacity: .78; }

        /* ── Inputs inline ───────────────────────────────────────── */
        .ii {
            border: 1.5px solid transparent; border-radius: 5px;
            padding: 4px 8px; font-size: .84rem; font-family: inherit;
            color: #374151; background: transparent; width: 100%;
            transition: all .15s;
        }
        .ii:focus { border-color: #2563eb; background: white; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .ii--sm { max-width: 80px; text-align: center; }
        .ii--md { max-width: 200px; }
        .ii--lg { max-width: 360px; }
        .ii--notes { max-width: 220px; color: #6b7280; font-size: .81rem; }
        .ii.saved  { border-color: #4ade80 !important; }
        .ii.saving { border-color: #fbbf24 !important; }
        .ii.error  { border-color: #f87171 !important; }

        /* ── Vigueur toggle ──────────────────────────────────────── */
        .vbtn {
            display: inline-flex; align-items: center; gap: 5px;
            border-radius: 99px; padding: 4px 12px;
            font-size: .76rem; font-weight: 700; cursor: pointer;
            border: 1.5px solid; transition: all .14s; font-family: inherit; white-space: nowrap;
        }
        .vbtn.on  { background:#dcfce7; color:#15803d; border-color:#86efac; }
        .vbtn.on:hover  { background:#bbf7d0; }
        .vbtn.off { background:#f3f4f6; color:#9ca3af; border-color:#e5e7eb; }
        .vbtn.off:hover { background:#e5e7eb; color:#6b7280; }
        .vdot { width:6px; height:6px; border-radius:50%; display:inline-block; flex-shrink:0; }
        .vbtn.on .vdot { background:#16a34a; }
        .vbtn.off .vdot { background:#9ca3af; }

        /* ── Icônes action ───────────────────────────────────────── */
        .bico {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 6px;
            border: 1.5px solid #e5e7eb; background: white;
            cursor: pointer; font-size: .8rem; color: #6b7280;
            transition: all .13s; text-decoration: none;
        }
        .bico:hover       { border-color:#2563eb; color:#2563eb; background:#eff6ff; text-decoration:none; }
        .bico.del:hover   { border-color:#dc2626; color:#dc2626; background:#fee2e2; }

        /* ── Zones "Ajouter" ─────────────────────────────────────── */
        .add-cls-zone {
            padding: 8px 22px 8px 52px;
            background: #fafbfc; border-top: 1px dashed #e5e7eb;
        }
        .add-mat-zone {
            padding: 10px 22px 10px 28px;
            background: #f8faff; border-top: 1px dashed #c7d2fe;
        }
        .alink {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .78rem; font-weight: 600; color: #2563eb;
            background: none; border: none; cursor: pointer;
            padding: 3px 6px; border-radius: 5px; font-family: inherit;
            transition: background .13s;
        }
        .alink:hover { background: #eff6ff; }

        /* ── Empty state matières ────────────────────────────────── */
        .mat-empty {
            padding: 28px; text-align: center;
            color: #9ca3af; font-size: .88rem;
        }

        /* ── Toast ───────────────────────────────────────────────── */
        #ap-toast { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:8px; pointer-events:none; }
        .ti { background:#1e3a5f; color:white; padding:10px 18px; border-radius:10px; font-size:.83rem; box-shadow:0 4px 16px rgba(0,0,0,.2); animation:tIn .2s ease; }
        .ti.err { background:#dc2626; }
        @keyframes tIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
    </style>

    <div class="container ap">

        <!-- ── EN-TÊTE ──────────────────────────────────────────── -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px">
            <div>
                <div class="breadcrumb">
                    <a href="/dashboard">Tableau de bord</a>
                    <span class="breadcrumb__sep">›</span>
                    <span>Programmes officiels</span>
                </div>
                <h1 style="font-family:var(--font-titre);font-size:1.8rem;margin:0">Programmes officiels</h1>
                <p class="text-muted text-sm" style="margin-top:4px">
                    Hiérarchie : <strong>Programme</strong> (année + cycle) › <strong>Matière</strong> › <strong>Classe</strong>
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn--ghost btn--sm" onclick="expandAll()">↓ Tout ouvrir</button>
                <button class="btn btn--ghost btn--sm" onclick="collapseAll()">↑ Tout fermer</button>
                <button class="btn btn--primary btn--sm" onclick="openModalProg()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Nouveau programme
                </button>
            </div>
        </div>

        <!-- ── LÉGENDE ──────────────────────────────────────────── -->
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;font-size:.78rem;color:#6b7280;background:white;border:1px solid var(--gris-300);border-radius:8px;padding:10px 16px;flex-wrap:wrap">
            <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#16a34a;margin-right:4px;vertical-align:middle"></span><strong>En vigueur</strong> = visible dans les formulaires</span>
            <span style="color:#d1d5db">|</span>
            <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#9ca3af;margin-right:4px;vertical-align:middle"></span><strong>Archivé</strong> = masqué, visible ici uniquement</span>
            <span style="color:#d1d5db">|</span>
            <span>✏️ Cliquez sur un champ texte pour l'éditer directement</span>
        </div>

        <?php if (empty($programmes)): ?>
            <div class="empty-state" style="margin-top:40px">
                <div class="empty-state__icon">📚</div>
                <h3>Aucun programme</h3>
                <p>Créez votre premier programme en cliquant sur le bouton ci-dessus.</p>
                <button class="btn btn--primary" onclick="openModalProg()">Créer un programme</button>
            </div>
        <?php else: ?>

            <?php foreach ($programmes as $prog): ?>
                <div class="prog-block" id="prog-<?= $prog['id'] ?>">

                    <!-- En-tête programme -->
                    <div class="prog-header">
                        <div class="prog-header-info">
                            <div class="prog-title">
                                Programme <?= (int)$prog['annee_entree'] ?>
                                <span class="cycle-pill"><?= htmlspecialchars($prog['cycle_label']) ?></span>
                                <?php if ((int)$prog['annee_entree'] >= 2025): ?>
                                    <span class="new-pill">✦ Nouveau</span>
                                <?php endif; ?>
                            </div>
                            <div class="prog-meta">
                                <span>📅 Rentrée <?= (int)$prog['annee_entree'] ?></span>
                                <span>·</span>
                                <span><?= count($prog['matieres']) ?> matière(s)</span>
                                <?php if (!empty($prog['source_url'])): ?>
                                    <span>·</span>
                                    <a href="<?= htmlspecialchars($prog['source_url']) ?>" target="_blank">Source officielle ↗</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="prog-actions">
                            <button class="bprog bprog-ghost" onclick="openModalMat(<?= $prog['id'] ?>)">
                                + Matière
                            </button>
                            <button class="bprog bprog-ghost"
                                    onclick="openModalEditProg(<?= $prog['id'] ?>, <?= (int)$prog['annee_entree'] ?>, <?= (int)$prog['cycle_id'] ?>, '<?= addslashes(htmlspecialchars($prog['source_url'] ?? '')) ?>')">
                                ✏️ Modifier
                            </button>
                            <button class="bprog bprog-danger" onclick="deleteProg(<?= $prog['id'] ?>, <?= (int)$prog['annee_entree'] ?>)">
                                🗑
                            </button>
                        </div>
                    </div>

                    <!-- Matières -->
                    <?php if (empty($prog['matieres'])): ?>
                        <div class="mat-empty">
                            Aucune matière. <button class="alink" onclick="openModalMat(<?= $prog['id'] ?>)">+ Ajouter une matière</button>
                        </div>
                    <?php else: ?>

                        <?php foreach ($prog['matieres'] as $mat): ?>
                            <div class="mat-block" id="mat-<?= $mat['pm_id'] ?>">

                                <!-- En-tête matière -->
                                <div class="mat-header" onclick="toggleMat(this)">
                                    <div class="mat-icon">📘</div>
                                    <span class="mat-name"><?= htmlspecialchars($mat['matiere_label']) ?></span>
                                    <?php if (!empty($mat['matiere_code'])): ?>
                                        <span class="mat-code"><?= htmlspecialchars($mat['matiere_code']) ?></span>
                                    <?php endif; ?>
                                    <span class="mat-count"><?= count($mat['classes']) ?> classe(s)</span>
                                    <div class="mat-acts" onclick="event.stopPropagation()">
                                        <button class="bico" title="Modifier"
                                                onclick="openModalEditMat(<?= $mat['pm_id'] ?>, <?= $mat['matiere_id'] ?>, '<?= addslashes(htmlspecialchars($mat['matiere_label'])) ?>', '<?= addslashes(htmlspecialchars($mat['matiere_code'])) ?>')">✏️</button>
                                        <button class="bico del" title="Supprimer cette matière et ses classes"
                                                onclick="deleteMat(<?= $mat['pm_id'] ?>, '<?= addslashes(htmlspecialchars($mat['matiere_label'])) ?>')">🗑</button>
                                    </div>
                                    <span class="mat-toggle">▼</span>
                                </div>

                                <!-- Corps matière -->
                                <div class="mat-body">

                                    <?php if (!empty($mat['classes'])): ?>
                                        <table class="cls-table">
                                            <thead>
                                            <tr>
                                                <th>Classe / Portée</th>
                                                <th>Intitulé</th>
                                                <th>Rentrée</th>
                                                <th>Notes</th>
                                                <th style="text-align:center">Statut</th>
                                                <th style="text-align:right;padding-right:22px">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($mat['classes'] as $pv):
                                                $arch = !(bool)$pv['en_vigueur'];
                                                ?>
                                                <tr id="pv-<?= $pv['id'] ?>" class="<?= $arch ? 'row-arch' : '' ?>">
                                                    <!-- Classe -->
                                                    <td>
                  <span style="font-weight:600;font-size:.84rem;color:<?= $arch ? '#9ca3af' : '#1e3a5f' ?>">
                    <?= htmlspecialchars($pv['classe_label'] ?? '— Tout le cycle —') ?>
                  </span>
                                                    </td>
                                                    <!-- Intitulé inline -->
                                                    <td>
                                                        <input type="text" class="ii ii--lg"
                                                               value="<?= htmlspecialchars($pv['label']) ?>"
                                                               onblur="savePv(<?= $pv['id'] ?>, 'label', this.value, this)"
                                                               onkeydown="if(event.key==='Enter')this.blur()">
                                                    </td>
                                                    <!-- Rentrée inline -->
                                                    <td>
                                                        <input type="number" class="ii ii--sm"
                                                               value="<?= (int)$pv['annee_entree'] ?>" min="2000" max="2040"
                                                               onblur="savePv(<?= $pv['id'] ?>, 'annee_entree', this.value, this)"
                                                               onkeydown="if(event.key==='Enter')this.blur()">
                                                    </td>
                                                    <!-- Notes inline -->
                                                    <td>
                                                        <input type="text" class="ii ii--notes"
                                                               value="<?= htmlspecialchars($pv['notes'] ?? '') ?>" placeholder="Notes…"
                                                               onblur="savePv(<?= $pv['id'] ?>, 'notes', this.value, this)"
                                                               onkeydown="if(event.key==='Enter')this.blur()">
                                                    </td>
                                                    <!-- Statut -->
                                                    <td style="text-align:center;white-space:nowrap">
                                                        <button class="vbtn <?= $arch ? 'off' : 'on' ?>" id="vbtn-<?= $pv['id'] ?>"
                                                                onclick="toggleVigueur(<?= $pv['id'] ?>, <?= $arch ? 'true' : 'false' ?>)">
                                                            <span class="vdot"></span>
                                                            <span id="vlbl-<?= $pv['id'] ?>"><?= $arch ? 'Archivé' : 'En vigueur' ?></span>
                                                        </button>
                                                    </td>
                                                    <!-- Actions -->
                                                    <td style="padding-right:22px">
                                                        <div style="display:flex;align-items:center;gap:5px;justify-content:flex-end">
                                                            <a href="/admin/programmes/version/<?= $pv['id'] ?>" class="bico" title="Éditer les compétences">✏️</a>
                                                            <button class="bico del" title="Supprimer"
                                                                    onclick="deletePv(<?= $pv['id'] ?>, '<?= addslashes(htmlspecialchars($pv['label'])) ?>')">🗑</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>

                                    <!-- + Ajouter une classe -->
                                    <div class="add-cls-zone">
                                        <button class="alink"
                                                onclick="openModalClasse(<?= $mat['pm_id'] ?>, <?= $prog['id'] ?>, <?= (int)$prog['annee_entree'] ?>, <?= (int)$prog['cycle_id'] ?>)">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                                            Ajouter une classe
                                        </button>
                                    </div>

                                </div><!-- /.mat-body -->
                            </div><!-- /.mat-block -->
                        <?php endforeach; ?>

                        <!-- + Ajouter une matière -->
                        <div class="add-mat-zone">
                            <button class="alink" onclick="openModalMat(<?= $prog['id'] ?>)">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                                Ajouter une matière à ce programme
                            </button>
                        </div>

                    <?php endif; ?>
                </div><!-- /.prog-block -->
            <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /.ap -->

    <!-- ═══════════════════════════════════════════════════════
         MODAL 1 : Programme (créer / modifier)
    ════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-prog">
        <div class="modal" style="max-width:460px">
            <div class="modal__header">
                <h3 id="mprog-title">➕ Nouveau programme</h3>
                <button class="btn btn--ghost btn--sm" onclick="closeModal('modal-prog')">✕</button>
            </div>
            <form onsubmit="submitProg(event)">
                <input type="hidden" id="mprog-id" value="">
                <div class="modal__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mprog-annee">Année de rentrée <span class="required">*</span></label>
                            <input type="number" id="mprog-annee" min="2000" max="2040" value="2025" required>
                        </div>
                        <div class="form-group">
                            <label for="mprog-cycle">Cycle <span class="required">*</span></label>
                            <select id="mprog-cycle" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($cycles as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mprog-source">URL source officielle</label>
                        <input type="url" id="mprog-source" placeholder="https://eduscol.education.fr/…">
                        <p class="form-hint">Lien vers le texte officiel du programme.</p>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" onclick="closeModal('modal-prog')">Annuler</button>
                    <button type="submit" class="btn btn--primary" id="mprog-submit">Créer le programme</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         MODAL 2 : Matière (créer / modifier)
    ════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-mat">
        <div class="modal" style="max-width:420px">
            <div class="modal__header">
                <h3 id="mmat-title">📘 Nouvelle matière</h3>
                <button class="btn btn--ghost btn--sm" onclick="closeModal('modal-mat')">✕</button>
            </div>
            <form onsubmit="submitMat(event)">
                <input type="hidden" id="mmat-prog-id" value="">
                <input type="hidden" id="mmat-pm-id" value="">
                <input type="hidden" id="mmat-matiere-id" value="">
                <div class="modal__body">
                    <div class="form-group">
                        <label for="mmat-label">Nom de la matière <span class="required">*</span></label>
                        <input type="text" id="mmat-label" required placeholder="Ex : Français">
                    </div>
                    <div class="form-group">
                        <label for="mmat-code">Code <span class="required">*</span></label>
                        <input type="text" id="mmat-code" required placeholder="Ex : FRANCAIS"
                               style="font-family:monospace;text-transform:uppercase">
                        <p class="form-hint">Majuscules, sans espaces ni accents. Doit être unique.</p>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" onclick="closeModal('modal-mat')">Annuler</button>
                    <button type="submit" class="btn btn--primary" id="mmat-submit">Créer la matière</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         MODAL 3 : Classe (créer)
    ════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-classe">
        <div class="modal" style="max-width:520px">
            <div class="modal__header">
                <h3>🏫 Ajouter une classe</h3>
                <button class="btn btn--ghost btn--sm" onclick="closeModal('modal-classe')">✕</button>
            </div>
            <form onsubmit="submitClasse(event)">
                <input type="hidden" id="mcls-pm-id" value="">
                <div class="modal__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mcls-classe">Classe</label>
                            <select id="mcls-classe">
                                <option value="">— Tout le cycle —</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= $cl['id'] ?>" data-cycle="<?= $cl['cycle_id'] ?>">
                                        <?= htmlspecialchars($cl['label']) ?> (<?= htmlspecialchars($cl['code']) ?>)
                                        — <?= htmlspecialchars($cl['cycle_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mcls-annee">Rentrée <span class="required">*</span></label>
                            <input type="number" id="mcls-annee" min="2000" max="2040" value="2025" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mcls-label">Intitulé du programme <span class="required">*</span></label>
                        <input type="text" id="mcls-label" required placeholder="Ex : Français – CM1 (2025)">
                    </div>
                    <div class="form-group">
                        <label for="mcls-notes">Notes</label>
                        <input type="text" id="mcls-notes" placeholder="Ex : Nouveau programme rentrée 2025">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="mcls-vigueur" checked>
                        <label for="mcls-vigueur">Mettre <strong>en vigueur</strong> immédiatement</label>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost" onclick="closeModal('modal-classe')">Annuler</button>
                    <button type="submit" class="btn btn--primary" id="mcls-submit">Ajouter la classe</button>
                </div>
            </form>
        </div>
    </div>

    <div id="ap-toast"></div>

    <script>
        // ════════════════════════════════════════════════════════
        //  UTILS
        // ════════════════════════════════════════════════════════
        function toast(msg, err=false) {
            const c = document.getElementById('ap-toast');
            const d = document.createElement('div');
            d.className = 'ti' + (err?' err':'');
            d.textContent = msg;
            c.appendChild(d);
            setTimeout(() => d.remove(), 3200);
        }

        async function api(url, data) {
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(data)
                });
                const text = await r.text();
                try { return JSON.parse(text); }
                catch(e) { console.error('Non-JSON:', text.substring(0,300)); toast('Erreur serveur (non-JSON)', true); return null; }
            } catch(e) { toast('Erreur réseau: '+e.message, true); return null; }
        }

        function openModal(id)  { document.getElementById(id).classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); }
        document.querySelectorAll('.modal-overlay').forEach(o => {
            o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
        });

        // ════════════════════════════════════════════════════════
        //  ACCORDION
        // ════════════════════════════════════════════════════════
        function toggleMat(h) {
            const b = h.nextElementSibling;
            const a = h.querySelector('.mat-toggle');
            a.textContent = b.classList.toggle('collapsed') ? '▶' : '▼';
        }
        function expandAll()  {
            document.querySelectorAll('.mat-body').forEach(b=>b.classList.remove('collapsed'));
            document.querySelectorAll('.mat-toggle').forEach(a=>a.textContent='▼');
        }
        function collapseAll() {
            document.querySelectorAll('.mat-body').forEach(b=>b.classList.add('collapsed'));
            document.querySelectorAll('.mat-toggle').forEach(a=>a.textContent='▶');
        }

        // ════════════════════════════════════════════════════════
        //  INLINE SAVE — programme_version
        // ════════════════════════════════════════════════════════
        async function savePv(id, field, value, el) {
            el.classList.add('saving');
            const r = await api('/api/admin/programme-versions', {id, [field]: value});
            el.classList.remove('saving');
            if(r?.ok) {
                el.classList.add('saved');
                setTimeout(()=>el.classList.remove('saved'), 1200);
                toast('✓ Sauvegardé');
            } else {
                el.classList.add('error');
                setTimeout(()=>el.classList.remove('error'), 2000);
                toast(r?.error||'Erreur', true);
            }
        }

        // ════════════════════════════════════════════════════════
        //  TOGGLE VIGUEUR
        // ════════════════════════════════════════════════════════
        async function toggleVigueur(id, newState) {
            const r = await api('/api/admin/programme-versions', {id, en_vigueur: newState});
            if(!r?.ok) { toast(r?.error||'Erreur', true); return; }
            const actif = (newState===true||newState==='true');
            document.getElementById('vbtn-'+id).className = 'vbtn '+(actif?'on':'off');
            document.getElementById('vbtn-'+id).setAttribute('onclick',`toggleVigueur(${id},${actif?'false':'true'})`);
            document.getElementById('vlbl-'+id).textContent = actif ? 'En vigueur' : 'Archivé';
            const row = document.getElementById('pv-'+id);
            if(row) row.className = actif ? '' : 'row-arch';
            toast(actif ? '✓ Activé' : '✓ Archivé');
        }

        // ════════════════════════════════════════════════════════
        //  SUPPRIMER une classe (version)
        // ════════════════════════════════════════════════════════
        async function deletePv(id, label) {
            if(!confirm(`Supprimer "${label}" ?\nLes compétences liées seront aussi supprimées.`)) return;
            const r = await api(`/api/admin/programme-versions/${id}/delete`, {});
            if(r?.ok) { document.getElementById('pv-'+id)?.remove(); toast('✓ Supprimé'); }
            else toast(r?.error||'Erreur', true);
        }

        // ════════════════════════════════════════════════════════
        //  MODAL PROGRAMME
        // ════════════════════════════════════════════════════════
        function openModalProg() {
            document.getElementById('mprog-title').textContent = '➕ Nouveau programme';
            document.getElementById('mprog-id').value     = '';
            document.getElementById('mprog-annee').value  = new Date().getFullYear();
            document.getElementById('mprog-cycle').value  = '';
            document.getElementById('mprog-source').value = '';
            document.getElementById('mprog-submit').textContent = 'Créer le programme';
            openModal('modal-prog');
        }
        function openModalEditProg(id, annee, cycleId, source) {
            document.getElementById('mprog-title').textContent = '✏️ Modifier le programme';
            document.getElementById('mprog-id').value     = id;
            document.getElementById('mprog-annee').value  = annee;
            document.getElementById('mprog-cycle').value  = cycleId;
            document.getElementById('mprog-source').value = source;
            document.getElementById('mprog-submit').textContent = 'Enregistrer';
            openModal('modal-prog');
        }
        async function submitProg(e) {
            e.preventDefault();
            const btn    = document.getElementById('mprog-submit');
            const editId = document.getElementById('mprog-id').value;
            btn.disabled = true; btn.textContent = editId ? 'Enregistrement…' : 'Création…';
            const data = {
                annee_entree: +document.getElementById('mprog-annee').value,
                cycle_id:     +document.getElementById('mprog-cycle').value,
                source_url:    document.getElementById('mprog-source').value || null,
            };
            const url = editId ? `/api/admin/programmes/${editId}/update` : '/api/admin/programmes/create';
            const r   = await api(url, data);
            btn.disabled = false; btn.textContent = editId ? 'Enregistrer' : 'Créer le programme';
            if(r?.ok) { toast(editId ? '✓ Programme modifié' : '✓ Programme créé'); closeModal('modal-prog'); setTimeout(()=>location.reload(),700); }
            else toast(r?.error||'Erreur', true);
        }
        async function deleteProg(id, annee) {
            if(!confirm(`Supprimer le programme ${annee} ?\nToutes les matières, classes et compétences associées seront supprimées.`)) return;
            const r = await api(`/api/admin/programmes/${id}/delete`, {});
            if(r?.ok) { document.getElementById('prog-'+id)?.remove(); toast('✓ Programme supprimé'); }
            else toast(r?.error||'Erreur', true);
        }

        // ════════════════════════════════════════════════════════
        //  MODAL MATIÈRE
        // ════════════════════════════════════════════════════════
        function openModalMat(progId) {
            document.getElementById('mmat-title').textContent    = '📘 Nouvelle matière';
            document.getElementById('mmat-prog-id').value        = progId;
            document.getElementById('mmat-pm-id').value          = '';
            document.getElementById('mmat-matiere-id').value     = '';
            document.getElementById('mmat-label').value          = '';
            document.getElementById('mmat-code').value           = '';
            document.getElementById('mmat-submit').textContent   = 'Créer la matière';
            openModal('modal-mat');
        }
        function openModalEditMat(pmId, matiereId, label, code) {
            document.getElementById('mmat-title').textContent    = '✏️ Modifier la matière';
            document.getElementById('mmat-pm-id').value          = pmId;
            document.getElementById('mmat-matiere-id').value     = matiereId;
            document.getElementById('mmat-label').value          = label;
            document.getElementById('mmat-code').value           = code;
            document.getElementById('mmat-submit').textContent   = 'Enregistrer';
            openModal('modal-mat');
        }
        async function submitMat(e) {
            e.preventDefault();
            const btn      = document.getElementById('mmat-submit');
            const pmId     = document.getElementById('mmat-pm-id').value;
            const matId    = document.getElementById('mmat-matiere-id').value;
            const progId   = document.getElementById('mmat-prog-id').value;
            btn.disabled   = true; btn.textContent = 'Enregistrement…';

            let r;
            if(pmId) {
                // Modification : on met à jour la matière (label + code)
                r = await api(`/api/admin/matieres/${matId}/update`, {
                    label: document.getElementById('mmat-label').value,
                    code:  document.getElementById('mmat-code').value.toUpperCase(),
                });
            } else {
                // Création : nouvelle matière + liaison au programme
                r = await api('/api/admin/matieres/create', {
                    programme_id: +progId,
                    label: document.getElementById('mmat-label').value,
                    code:  document.getElementById('mmat-code').value.toUpperCase(),
                });
            }

            btn.disabled = false; btn.textContent = pmId ? 'Enregistrer' : 'Créer la matière';
            if(r?.ok) { toast(pmId ? '✓ Matière modifiée' : '✓ Matière créée'); closeModal('modal-mat'); setTimeout(()=>location.reload(),700); }
            else toast(r?.error||'Erreur', true);
        }
        async function deleteMat(pmId, label) {
            if(!confirm(`Supprimer la matière "${label}" de ce programme ?\nSes classes et compétences seront supprimées.`)) return;
            const r = await api(`/api/admin/programme-matieres/${pmId}/delete`, {});
            if(r?.ok) { document.getElementById('mat-'+pmId)?.remove(); toast('✓ Matière supprimée'); }
            else toast(r?.error||'Erreur', true);
        }

        // ════════════════════════════════════════════════════════
        //  MODAL CLASSE
        // ════════════════════════════════════════════════════════
        function openModalClasse(pmId, progId, annee, cycleId) {
            document.getElementById('mcls-pm-id').value  = pmId;
            document.getElementById('mcls-classe').value = '';
            document.getElementById('mcls-annee').value  = annee;
            document.getElementById('mcls-label').value  = '';
            document.getElementById('mcls-notes').value  = '';
            document.getElementById('mcls-vigueur').checked = true;

            // Filtrer les classes par cycle
            document.querySelectorAll('#mcls-classe option').forEach(opt => {
                if(!opt.value) return; // option vide
                opt.hidden = opt.dataset.cycle && +opt.dataset.cycle !== cycleId;
            });

            openModal('modal-classe');
        }
        async function submitClasse(e) {
            e.preventDefault();
            const btn = document.getElementById('mcls-submit');
            btn.disabled = true; btn.textContent = 'Ajout…';
            const r = await api('/api/admin/programme-versions/create', {
                programme_matiere_id: +document.getElementById('mcls-pm-id').value,
                classe_id:   document.getElementById('mcls-classe').value || null,
                annee_entree:+document.getElementById('mcls-annee').value,
                label:        document.getElementById('mcls-label').value,
                notes:        document.getElementById('mcls-notes').value,
                en_vigueur:   document.getElementById('mcls-vigueur').checked,
            });
            btn.disabled = false; btn.textContent = 'Ajouter la classe';
            if(r?.ok) { toast('✓ Classe ajoutée'); closeModal('modal-classe'); setTimeout(()=>location.reload(),700); }
            else toast(r?.error||'Erreur', true);
        }
    </script>

<?php include __DIR__.'/../../partials/layout_end.php'; ?>
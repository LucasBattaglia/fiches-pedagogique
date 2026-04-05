/**
 * app.js — Fiches Pédagogiques
 * Gère : cascade Cycle→Classe→Matière→Programme→Compétences
 *        tables dynamiques (déroulement, comportements, variables)
 *        confirmations suppression, flash auto-dismiss
 */

// ── Détection de la base URL (virtual host ou sous-dossier) ──────────
// On lit l'attribut data-base sur <body> posé par PHP, sinon ''
const BASE = document.body.dataset.base || '';

// ── Utilitaires ──────────────────────────────────────────────────────
function qs(sel, ctx = document)  { return ctx.querySelector(sel); }
function qsa(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

function apiUrl(path) {
  return BASE + '/' + path.replace(/^\//, '');
}

// ── Flash auto-dismiss ───────────────────────────────────────────────
qsa('[data-dismiss]').forEach(el => {
  const ms = parseInt(el.dataset.dismiss) || 4000;
  setTimeout(() => el.remove(), ms);
});

// ── Confirm suppression ──────────────────────────────────────────────
qsa('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Confirmer la suppression ?')) e.preventDefault();
  });
});

// ────────────────────────────────────────────────────────────────────
//  TABLES DYNAMIQUES
//  Fonctionne pour n'importe quelle table avec :
//    - un bouton ayant data-add-table="ID_DE_LA_TABLE"
//    - des <tr> avec le bouton .btn-remove-row dedans
// ────────────────────────────────────────────────────────────────────

function addRemoveListener(btn) {
  btn.addEventListener('click', () => btn.closest('tr').remove());
}

// Attacher les boutons supprimer sur les lignes existantes au chargement
qsa('.btn-remove-row').forEach(addRemoveListener);

// Boutons "Ajouter une ligne" — data-add-table="id_table" data-template="nom_template"
qsa('[data-add-table]').forEach(btn => {
  btn.addEventListener('click', () => {
    const tableId  = btn.dataset.addTable;
    const template = btn.dataset.template;
    const tbody    = qs('#' + tableId + ' tbody');
    if (!tbody) return;
    const idx  = tbody.rows.length;
    const tr   = document.createElement('tr');
    tr.innerHTML = getTemplate(template, idx);
    tbody.appendChild(tr);
    tr.querySelector('.btn-remove-row') && addRemoveListener(tr.querySelector('.btn-remove-row'));
    tr.querySelector('input, textarea')?.focus();
  });
});

function getTemplate(name, idx) {
  const templates = {

    // Table déroulement séance
    'deroulement': `
      <td><input type="number" name="deroulement[${idx}][duree]" placeholder="min" style="width:60px" class="form-input-sm"></td>
      <td><textarea name="deroulement[${idx}][enseignant]" rows="2" placeholder="Ce que fait l'enseignant…" style="width:100%"></textarea></td>
      <td><textarea name="deroulement[${idx}][eleves]" rows="2" placeholder="Ce que font les élèves…" style="width:100%"></textarea></td>
      <td style="text-align:center"><button type="button" class="btn btn--ghost btn--sm btn-remove-row" title="Supprimer">✕</button></td>
    `,

    // Table comportements / remédiations (séquence, séance, situation)
    'comportements_sequence': `
      <td><input type="text" name="comportements_sequence[${idx}][comportement]" placeholder="Comportement observé…" style="width:100%"></td>
      <td><input type="text" name="comportements_sequence[${idx}][remediation]" placeholder="Remédiation proposée…" style="width:100%"></td>
      <td style="text-align:center"><button type="button" class="btn btn--ghost btn--sm btn-remove-row" title="Supprimer">✕</button></td>
    `,
    'comportements_seance': `
      <td><input type="text" name="comportements_seance[${idx}][comportement]" placeholder="Comportement observé…" style="width:100%"></td>
      <td><input type="text" name="comportements_seance[${idx}][remediation]" placeholder="Remédiation proposée…" style="width:100%"></td>
      <td style="text-align:center"><button type="button" class="btn btn--ghost btn--sm btn-remove-row" title="Supprimer">✕</button></td>
    `,
    'comportements_situation': `
      <td><input type="text" name="comportements_situation[${idx}][comportement]" placeholder="Comportement observé…" style="width:100%"></td>
      <td><input type="text" name="comportements_situation[${idx}][remediation]" placeholder="Remédiation proposée…" style="width:100%"></td>
      <td style="text-align:center"><button type="button" class="btn btn--ghost btn--sm btn-remove-row" title="Supprimer">✕</button></td>
    `,

    // Table variables d'évolution (situation)
    'variables_evolution': `
      <td><input type="text" name="variables_evolution[${idx}][variable]" placeholder="Ex : Distance" style="width:100%"></td>
      <td><input type="text" name="variables_evolution[${idx}][plus]" placeholder="Complexifier…" style="width:100%"></td>
      <td><input type="text" name="variables_evolution[${idx}][moins]" placeholder="Simplifier…" style="width:100%"></td>
      <td style="text-align:center"><button type="button" class="btn btn--ghost btn--sm btn-remove-row" title="Supprimer">✕</button></td>
    `,
  };
  return templates[name] || '';
}

// ────────────────────────────────────────────────────────────────────
//  CASCADE : Cycle → Classe → Matière → Version programme → Compétences
//  IDs HTML : cycle_id, classe_id, matiere_id, programme_version_id
//  Conteneur compétences : #programme-items-container
// ────────────────────────────────────────────────────────────────────

const selCycle   = qs('#cycle_id');
const selClasse  = qs('#classe_id');
const selMatiere = qs('#matiere_id');
const selVersion = qs('#programme_version_id');
const itemsBox   = qs('#programme-items-container');

// Valeurs pré-sélectionnées (mode édition)
const preClasse  = selClasse?.dataset.selected  || selClasse?.value  || '';
const preMatiere = selMatiere?.dataset.selected || selMatiere?.value || '';
const preVersion = selVersion?.dataset.selected || selVersion?.value || '';
const preItems   = JSON.parse(itemsBox?.dataset.selected || '[]');

// 1) Cycle change → recharger les classes puis les matières
async function onCycleChange() {
  if (!selClasse) return;
  const cid = selCycle?.value;
  selClasse.innerHTML = '<option value="">— Toutes les classes —</option>';
  if (!cid) {
    selMatiere.innerHTML = '<option value="">— Sélectionner un cycle —</option>';
    clearItems();
    return;
  }

  selClasse.disabled = true;
  try {
    const res  = await fetch(apiUrl('api/classes?cycle_id=' + cid));
    const data = await res.json();
    data.forEach(c => {
      const opt = new Option(c.label, c.id, false, String(c.id) === String(preClasse));
      selClasse.appendChild(opt);
    });
  } catch(e) {
    console.error('Erreur chargement classes :', e);
  } finally {
    selClasse.disabled = false;
  }
  // Recharger les matières pour cycle sélectionné (sans filtre classe)
  await onClasseChange();
}

// 1b) Classe change → recharger les matières filtrées par cycle + classe
async function onClasseChange() {
  if (!selMatiere) return;
  const cid  = selCycle?.value;
  const clid = selClasse?.value;

  selMatiere.innerHTML = '<option value="">Chargement…</option>';
  selMatiere.disabled = true;
  selVersion.innerHTML = '<option value="">— Sélectionnez cycle et matière d\'abord —</option>';
  clearItems();

  if (!cid) {
    selMatiere.innerHTML = '<option value="">— Sélectionner un cycle —</option>';
    selMatiere.disabled = false;
    return;
  }

  try {
    const url  = apiUrl(`api/matieres?cycle_id=${cid}&classe_id=${clid || ''}`);
    const res  = await fetch(url);
    const data = await res.json();

    selMatiere.innerHTML = '<option value="">— Sélectionner —</option>';
    data.forEach(m => {
      const opt = new Option(m.label, m.id, false, String(m.id) === String(preMatiere));
      selMatiere.appendChild(opt);
    });

    // Si un seul résultat → sélection auto
    if (data.length === 1) selMatiere.value = data[0].id;

    // Recharger les versions si une matière est déjà sélectionnée
    if (selMatiere.value) await onVersionChange();

  } catch(e) {
    selMatiere.innerHTML = '<option value="">Erreur de chargement</option>';
    console.error('Erreur chargement matières :', e);
  } finally {
    selMatiere.disabled = false;
  }
}

// 2) Matière change → recharger les versions de programme
async function onVersionChange() {
  if (!selVersion) return;
  const cid  = selCycle?.value;
  const clid = selClasse?.value;
  const mid  = selMatiere?.value;

  selVersion.innerHTML = '<option value="">— Sélectionner cycle et matière d\'abord —</option>';
  clearItems();
  if (!cid || !mid) return;

  selVersion.innerHTML = '<option value="">Chargement…</option>';
  selVersion.disabled = true;

  try {
    const url  = apiUrl(`api/programme-versions?cycle_id=${cid}&classe_id=${clid || ''}&matiere_id=${mid}`);
    const res  = await fetch(url);
    const data = await res.json();

    selVersion.innerHTML = '<option value="">— Sélectionner —</option>';
    data.forEach(v => {
      const label = `${v.label}  (rentrée ${v.annee_entree})`;
      const opt   = new Option(label, v.id, false, String(v.id) === String(preVersion));
      selVersion.appendChild(opt);
    });

    // Si un seul résultat → sélection auto
    if (data.length === 1) selVersion.value = data[0].id;

    // Charger les compétences si une version est sélectionnée
    if (selVersion.value) await loadItems(selVersion.value);

  } catch(e) {
    selVersion.innerHTML = '<option value="">Erreur de chargement</option>';
    console.error('Erreur chargement versions :', e);
  } finally {
    selVersion.disabled = false;
  }
}

// 3) Version change → charger les compétences
async function loadItems(versionId) {
  if (!itemsBox) return;
  if (!versionId) { clearItems(); return; }

  itemsBox.innerHTML = '<p class="text-muted text-sm" style="padding:8px">Chargement des compétences…</p>';

  try {
    const res   = await fetch(apiUrl('api/programme-items?version_id=' + versionId));
    const items = await res.json();

    if (!items.length) {
      itemsBox.innerHTML = '<p class="text-muted text-sm" style="padding:8px">Aucune compétence disponible pour ce programme.</p>';
      return;
    }

    itemsBox.innerHTML = renderTree(items, preItems);

    // Cocher/décocher → highlight + propagation aux enfants
    itemsBox.querySelectorAll('input[type=checkbox]').forEach(cb => {
      cb.addEventListener('change', () => {
        cb.closest('.prog-tree__item')?.classList.toggle('selected', cb.checked);
        // Propager aux enfants imbriqués dans le div suivant
        const next = cb.closest('.prog-tree__item')?.nextElementSibling;
        if (next && next.classList.contains('prog-tree__sub')) {
          next.querySelectorAll('input[type=checkbox]').forEach(child => {
            child.checked = cb.checked;
            child.closest('.prog-tree__item')?.classList.toggle('selected', cb.checked);
          });
        }
      });
    });

  } catch(e) {
    itemsBox.innerHTML = '<p class="text-muted text-sm" style="padding:8px;color:var(--rouge)">Erreur de chargement des compétences.</p>';
    console.error('Erreur chargement compétences :', e);
  }
}

function clearItems() {
  if (itemsBox) itemsBox.innerHTML = '<p class="text-muted text-sm" style="padding:8px">Sélectionnez un cycle, une matière et un programme pour voir les compétences.</p>';
}

// ── Normalise parent_id : le JSON renvoie parfois string, parfois null ──
function normId(v) {
  return (v === null || v === '' || v === undefined) ? null : parseInt(v);
}

// ── Rendu de l'arbre compétences ─────────────────────────────────────
// Niveau 0 = domaine (titre repliable, pas de checkbox)
// Niveau 1+ = compétence/critère (checkbox cochable)
function renderTree(items, selected) {
  const roots = items.filter(i => normId(i.parent_id) === null);
  if (!roots.length) return '<p class="text-muted text-sm" style="padding:8px">Aucune compétence disponible.</p>';
  return '<div class="prog-tree">' + roots.map(item => renderItem(items, item, selected)).join('') + '</div>';
}

function renderItem(allItems, item, selected) {
  const id       = parseInt(item.id);
  const children = allItems.filter(i => normId(i.parent_id) === id);
  const niveau   = parseInt(item.niveau ?? 0);
  const isChecked = selected.includes(id);

  // Niveau 0 : domaine, affiché comme titre repliable sans checkbox
  if (niveau === 0) {
    const childrenHtml = children.length
        ? '<div class="prog-tree__sub" style="margin-left:8px">' + children.map(c => renderItem(allItems, c, selected)).join('') + '</div>'
        : '';
    return `
      <details class="prog-tree__domain" open>
        <summary style="list-style:none;display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:4px;font-size:.83rem;font-weight:600;color:var(--gris-700);background:var(--gris-100);margin-bottom:3px;cursor:pointer;outline:none">
          ${item.code ? `<span style="color:var(--gris-500);font-size:.75rem">${escHtml(item.code)}</span>` : ''}
          ${escHtml(item.label)}
        </summary>
        ${childrenHtml}
      </details>
    `;
  }

  // Niveau 1+ : compétence / critère avec checkbox, indentée selon profondeur
  const indent = (niveau - 1) * 16;
  const childrenHtml = children.length
      ? '<div class="prog-tree__sub" style="margin-left:16px">' + children.map(c => renderItem(allItems, c, selected)).join('') + '</div>'
      : '';

  return `
    <div class="prog-tree__item${isChecked ? ' selected' : ''}" style="margin-left:${indent}px">
      <input type="checkbox" name="programme_items[]" value="${id}" id="pi_${id}" ${isChecked ? 'checked' : ''}>
      <label for="pi_${id}" style="cursor:pointer;flex:1;font-size:.85rem">
        ${item.code ? `<span style="color:var(--gris-500);font-size:.75rem;margin-right:6px">${escHtml(item.code)}</span>` : ''}
        ${escHtml(item.label)}
      </label>
    </div>
    ${childrenHtml}
  `;
}

function escHtml(s) {
  return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Branchement des événements ───────────────────────────────────────
if (selCycle)   selCycle.addEventListener('change',   onCycleChange);
if (selClasse)  selClasse.addEventListener('change',  onClasseChange);
if (selMatiere) selMatiere.addEventListener('change', onVersionChange);
if (selVersion) selVersion.addEventListener('change', () => loadItems(selVersion.value));

// ── Initialisation au chargement de la page ──────────────────────────
(async function init() {
  // Si un cycle est déjà sélectionné (mode édition), charger les classes
  if (selCycle?.value) {
    await onCycleChange();
    // Restaurer la classe sélectionnée
    if (preClasse && selClasse) selClasse.value = preClasse;
    // Recharger les matières avec cycle+classe
    await onClasseChange();
    // Restaurer la matière sélectionnée
    if (preMatiere && selMatiere) selMatiere.value = preMatiere;
    // Recharger les versions
    if (selMatiere?.value) {
      await onVersionChange();
      // Restaurer la version
      if (preVersion && selVersion) selVersion.value = preVersion;
      // Charger les compétences
      if (selVersion?.value) await loadItems(selVersion.value);
    }
  }
})();

// ── Modals ───────────────────────────────────────────────────────────
qsa('[data-modal-open]').forEach(btn => {
  btn.addEventListener('click', () => qs('#' + btn.dataset.modalOpen)?.classList.add('open'));
});
qsa('[data-modal-close]').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('open'));
});
qsa('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
});
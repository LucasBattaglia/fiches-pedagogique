<?php
$pageTitle = !empty($sequence['id']) ? 'Modifier la séquence' : 'Nouvelle séquence';
$activeNav = 'seq-list';
$isEdit    = !empty($sequence['id']);
include __DIR__.'/../partials/layout_start.php';
?>

    <div class="container container--md">

        <div class="breadcrumb">
            <a href="<?= $base ?>/sequence/list">Mes séquences</a>
            <?php if ($isEdit): ?>
                <span class="breadcrumb__sep">›</span>
                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a>
            <?php endif; ?>
            <span class="breadcrumb__sep">›</span>
            <span><?= $isEdit ? 'Modifier' : 'Nouvelle séquence' ?></span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $msgs): foreach ((array)$msgs as $msg): ?>
                <div class="alert alert--<?= htmlspecialchars($type) ?>" data-dismiss="4000"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; endforeach; unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <form action="<?= $base ?>/<?= $isEdit ? 'sequence/'.$sequence['id'].'/update' : 'sequence/create' ?>" method="post" id="sequence-form">

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

                <!-- COL GAUCHE -->
                <div>

                    <!-- Infos générales -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Informations générales</h3></div>
                        <div class="card__body">

                            <div class="form-group">
                                <label for="titre">Titre de la séquence <span class="required">*</span></label>
                                <input type="text" id="titre" name="titre" required
                                       value="<?= htmlspecialchars($sequence['titre'] ?? '') ?>"
                                       placeholder="Ex : Lecture compréhension – Le Petit Prince">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cycle_id">Cycle <span class="required">*</span></label>
                                    <select id="cycle_id" name="cycle_id" required>
                                        <option value="">— Sélectionner —</option>
                                        <?php foreach ($cycles as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= ($sequence['cycle_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="classe_id">Classe</label>
                                    <select id="classe_id" name="classe_id">
                                        <option value="">— Toutes —</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="matiere_id">Matière / Domaine <span class="required">*</span></label>
                                    <select id="matiere_id" name="matiere_id" required
                                            data-selected="<?= htmlspecialchars($sequence['matiere_id'] ?? '') ?>">
                                        <option value="">— Sélectionner un cycle —</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="nb_seances">Nombre de séances</label>
                                    <input type="number" id="nb_seances" name="nb_seances" min="1" max="30"
                                           value="<?= $sequence['nb_seances'] ?? 1 ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="domaine">Domaine / Champ</label>
                                <input type="text" id="domaine" name="domaine"
                                       value="<?= htmlspecialchars($sequence['domaine'] ?? '') ?>"
                                       placeholder="Ex : Lire, écrire, comprendre…">
                            </div>

                        </div>
                    </div>

                    <!-- Programme -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Programme officiel</h3></div>
                        <div class="card__body">

                            <div class="form-group">
                                <label for="programme_version_id">Version du programme</label>
                                <select id="programme_version_id" name="programme_version_id">
                                    <option value="">— Sélectionnez cycle et matière d'abord —</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Compétences du programme visées</label>
                                <div id="programme-items-container"
                                     data-selected="<?= htmlspecialchars(json_encode($sequence['programme_items'] ?? [])) ?>">
                                    <p class="text-muted text-sm">Sélectionnez un programme ci-dessus pour voir les compétences.</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Objectifs -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Objectifs & Tâche finale</h3></div>
                        <div class="card__body">
                            <div class="form-group">
                                <label for="tache_finale">Tâche finale</label>
                                <textarea id="tache_finale" name="tache_finale" rows="3"
                                          placeholder="Ce que les élèves doivent être capables de faire à la fin de la séquence…"><?= htmlspecialchars($sequence['tache_finale'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="objectifs_generaux">Objectifs généraux de la séquence</label>
                                <textarea id="objectifs_generaux" name="objectifs_generaux" rows="4"
                                          placeholder="Compétences et connaissances visées…"><?= htmlspecialchars($sequence['objectifs_generaux'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="materiel">Matériel</label>
                                <textarea id="materiel" name="materiel" rows="2"><?= htmlspecialchars($sequence['materiel'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Comportements & remédiations -->
                    <div class="card mb-24">
                        <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                            <h3 class="card__titre">Comportements possibles & remédiations</h3>
                            <button type="button" class="btn btn--outline btn--sm" data-add-table="table-comportements-sequence" data-template="comportements_sequence">+ Ligne</button>
                        </div>
                        <div class="card__body" style="padding:0">
                            <table id="table-comportements-sequence" class="dyn-table" style="width:100%">
                                <thead>
                                <tr><th>Comportement observé</th><th>Remédiation proposée</th><th style="width:40px"></th></tr>
                                </thead>
                                <tbody>
                                <?php
                                $crs = is_array($sequence['comportements_remediations'] ?? null)
                                        ? $sequence['comportements_remediations']
                                        : (json_decode($sequence['comportements_remediations'] ?? '[]', true) ?? []);
                                foreach ($crs as $i => $cr): ?>
                                    <tr>
                                        <td><input type="text" name="comportements_sequence[<?= $i ?>][comportement]" value="<?= htmlspecialchars($cr['comportement'] ?? '') ?>"></td>
                                        <td><input type="text" name="comportements_sequence[<?= $i ?>][remediation]" value="<?= htmlspecialchars($cr['remediation'] ?? '') ?>"></td>
                                        <td><button type="button" class="btn btn--ghost btn--sm btn-remove-row">✕</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- fin col gauche -->

                <!-- COL DROITE -->
                <div>
                    <div class="card" style="position:sticky;top:80px">
                        <div class="card__header"><h3 class="card__titre">Actions</h3></div>
                        <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
                            <button type="submit" class="btn btn--primary" style="justify-content:center">
                                <?= $isEdit ? '💾 Enregistrer les modifications' : '✅ Créer la séquence' ?>
                            </button>
                            <a href="<?= $base ?>/sequence/list" class="btn btn--ghost" style="justify-content:center">Annuler</a>
                            <?php if ($isEdit): ?>
                                <hr style="border:none;border-top:1px solid var(--gris-300)">
                                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>/pdf" class="btn btn--outline btn--sm" target="_blank" style="justify-content:center">
                                    📄 Télécharger PDF
                                </a>
                                <form action="<?= $base ?>/sequence/<?= $sequence['id'] ?>/delete" method="post">
                                    <button type="submit" class="btn btn--danger btn--sm" style="width:100%;justify-content:center"
                                            data-confirm="Supprimer cette séquence et toutes ses séances ?">🗑 Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Publication -->
                        <div class="card__footer">
                            <div class="form-check">
                                <input type="checkbox" name="is_public" id="is_public" value="1"
                                        <?= !empty($sequence['is_public']) ? 'checked' : '' ?>>
                                <label for="is_public">🌐 Rendre cette fiche publique</label>
                            </div>
                            <p class="form-hint">Les fiches publiques sont visibles par tous dans l'explorateur.</p>
                        </div>
                    </div>
                </div>

            </div><!-- fin grid -->
        </form>

    </div>

<?php include __DIR__.'/../partials/layout_end.php'; ?>
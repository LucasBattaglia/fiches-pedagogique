<?php
$pageTitle = !empty($seance['id']) ? 'Modifier la séance' : 'Nouvelle séance';
$activeNav = 'seance-list';
$isEdit    = !empty($seance['id']);
$s         = $seance ?? [];
// $sequence peut être null (séance autonome)
$fromSeqId = $fromSeqId ?? null;
include __DIR__.'/../partials/layout_start.php';
?>
    <div class="container container--md">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= $base ?>/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <?php if (!empty($sequence)): ?>
                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a>
                <span class="breadcrumb__sep">›</span>
            <?php else: ?>
                <a href="<?= $base ?>/seance/list">Mes séances</a>
                <span class="breadcrumb__sep">›</span>
            <?php endif; ?>
            <span><?= $isEdit ? 'Modifier séance '.($s['numero'] ?? '') : 'Nouvelle séance' ?></span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $msgs): foreach ((array)$msgs as $msg): ?>
                <div class="alert alert--<?= htmlspecialchars($type) ?>" data-dismiss="4000"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; endforeach; unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
            <div>
                <h1 style="font-family:var(--font-titre);font-size:1.5rem">
                    <?= $isEdit ? 'Séance N°'.($s['numero'] ?? '').' — '.htmlspecialchars($s['titre']) : 'Nouvelle séance' ?>
                </h1>
                <p class="text-muted text-sm">Fiche de préparation de séance
                    <?php if (empty($sequence)): ?>
                        <span class="badge badge--ambre" style="margin-left:8px">Séance autonome</span>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($isEdit): ?>
                <div style="display:flex;gap:8px">
                    <a href="<?= $base ?>/situation/create?seance_id=<?= $s['id'] ?>" class="btn btn--ambre btn--sm">+ Situation</a>
                    <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
                </div>
            <?php endif; ?>
        </div>

        <form action="<?= $base ?>/<?= $isEdit ? 'seance/'.$s['id'].'/update' : 'seance/create' ?>" method="post" id="seance-form">
            <input type="hidden" name="sequence_id" value="<?= htmlspecialchars((string)($sequence['id'] ?? $sequence_id ?? '')) ?>">

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

                <!-- COLONNE GAUCHE -->
                <div>
                    <!-- Informations générales -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Informations générales</h3></div>
                        <div class="card__body">

                            <div class="form-group">
                                <label for="titre">Titre de la séance <span class="required">*</span></label>
                                <input type="text" id="titre" name="titre" required
                                       value="<?= htmlspecialchars($s['titre'] ?? '') ?>"
                                       placeholder="Ex : Lecture d'un texte narratif">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="champ_apprentissage">Champ d'apprentissage</label>
                                    <input type="text" id="champ_apprentissage" name="champ_apprentissage"
                                           value="<?= htmlspecialchars($s['champ_apprentissage'] ?? '') ?>"
                                           placeholder="Ex : Lire à voix haute">
                                </div>
                                <div class="form-group">
                                    <label for="duree">Durée (minutes)</label>
                                    <input type="number" id="duree" name="duree" min="1" max="300"
                                           value="<?= htmlspecialchars((string)($s['duree'] ?? '')) ?>" placeholder="45">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="competence_visee">Compétence visée</label>
                                <textarea id="competence_visee" name="competence_visee" rows="2"><?= htmlspecialchars($s['competence_visee'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="afc">Attendu de Fin de Cycle (AFC)</label>
                                <textarea id="afc" name="afc" rows="2"><?= htmlspecialchars($s['afc'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="objectif_general">Objectif général</label>
                                <textarea id="objectif_general" name="objectif_general" rows="2"
                                          placeholder="Objectif de la séquence parente…"><?= htmlspecialchars($s['objectif_general'] ?? $sequence['objectifs_generaux'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="objectif_intermediaire">Objectif intermédiaire <span class="required">*</span></label>
                                <textarea id="objectif_intermediaire" name="objectif_intermediaire" rows="2" required
                                          placeholder="Objectif spécifique à cette séance…"><?= htmlspecialchars($s['objectif_intermediaire'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="materiel">Matériel</label>
                                <textarea id="materiel" name="materiel" rows="2"><?= htmlspecialchars($s['materiel'] ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Déroulement -->
                    <div class="card mb-24">
                        <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                            <h3 class="card__titre">Déroulement</h3>
                            <button type="button" class="btn btn--outline btn--sm" data-add-table="table-deroulement" data-template="deroulement">+ Ligne</button>
                        </div>
                        <div class="card__body" style="padding:0">
                            <div style="overflow-x:auto">
                                <table id="table-deroulement" class="dyn-table" style="width:100%;min-width:500px">
                                    <thead>
                                    <tr>
                                        <th style="width:80px">Durée (min)</th>
                                        <th style="width:40%">Enseignant(e)</th>
                                        <th>Élèves</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $derr = is_array($s['deroulement'] ?? null) ? $s['deroulement'] : (json_decode($s['deroulement'] ?? '[]', true) ?? []);
                                    foreach ($derr as $i => $row): ?>
                                        <tr>
                                            <td><input type="number" name="deroulement[<?=$i?>][duree]" value="<?= htmlspecialchars((string)($row['duree']??'')) ?>" placeholder="min" style="width:60px"></td>
                                            <td><textarea name="deroulement[<?=$i?>][enseignant]" rows="2"><?= htmlspecialchars($row['enseignant']??'') ?></textarea></td>
                                            <td><textarea name="deroulement[<?=$i?>][eleves]" rows="2"><?= htmlspecialchars($row['eleves']??'') ?></textarea></td>
                                            <td><button type="button" class="btn btn--ghost btn--sm btn-remove-row">✕</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Critères -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Critères & Variables didactiques</h3></div>
                        <div class="card__body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="criteres_realisation">Critères de réalisation</label>
                                    <textarea id="criteres_realisation" name="criteres_realisation" rows="3"><?= htmlspecialchars($s['criteres_realisation'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="criteres_reussite">Critères de réussite</label>
                                    <textarea id="criteres_reussite" name="criteres_reussite" rows="3"><?= htmlspecialchars($s['criteres_reussite'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="variables_didactiques">Variables didactiques</label>
                                <textarea id="variables_didactiques" name="variables_didactiques" rows="3"><?= htmlspecialchars($s['variables_didactiques'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Comportements -->
                    <div class="card mb-24">
                        <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                            <h3 class="card__titre">Comportements & remédiations</h3>
                            <button type="button" class="btn btn--outline btn--sm" data-add-table="table-comportements-seance" data-template="comportements_seance">+ Ligne</button>
                        </div>
                        <div class="card__body" style="padding:0">
                            <table id="table-comportements-seance" class="dyn-table" style="width:100%">
                                <thead>
                                <tr><th>Comportement observé</th><th>Remédiation proposée</th><th style="width:40px"></th></tr>
                                </thead>
                                <tbody>
                                <?php
                                $cr = is_array($s['comportements_remediations'] ?? null) ? $s['comportements_remediations'] : (json_decode($s['comportements_remediations'] ?? '[]', true) ?? []);
                                foreach ($cr as $i => $row): ?>
                                    <tr>
                                        <td><input type="text" name="comportements_seance[<?=$i?>][comportement]" value="<?= htmlspecialchars($row['comportement']??'') ?>"></td>
                                        <td><input type="text" name="comportements_seance[<?=$i?>][remediation]" value="<?= htmlspecialchars($row['remediation']??'') ?>"></td>
                                        <td><button type="button" class="btn btn--ghost btn--sm btn-remove-row">✕</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- fin col gauche -->

                <!-- COLONNE DROITE -->
                <div>
                    <div class="card" style="position:sticky;top:80px">
                        <div class="card__header"><h3 class="card__titre">Actions</h3></div>
                        <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
                            <button type="submit" class="btn btn--primary" style="justify-content:center">
                                💾 <?= $isEdit ? 'Enregistrer les modifications' : 'Créer la séance' ?>
                            </button>
                            <?php if (!empty($sequence)): ?>
                                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>" class="btn btn--ghost" style="justify-content:center">← Retour séquence</a>
                            <?php else: ?>
                                <a href="<?= $base ?>/seance/list" class="btn btn--ghost" style="justify-content:center">← Mes séances</a>
                            <?php endif; ?>
                            <?php if ($isEdit): ?>
                                <hr style="border:none;border-top:1px solid var(--gris-300)">
                                <a href="<?= $base ?>/seance/<?= $s['id'] ?>/pdf" class="btn btn--outline btn--sm" target="_blank" style="justify-content:center">
                                    📄 Télécharger PDF
                                </a>
                                <form action="<?= $base ?>/seance/<?= $s['id'] ?>/delete" method="post">
                                    <button type="submit" class="btn btn--danger btn--sm" style="width:100%;justify-content:center"
                                            data-confirm="Supprimer cette séance et toutes ses situations ?">🗑 Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($sequence)): ?>
                            <div class="card__footer">
                                <div class="text-sm text-muted" style="margin-bottom:6px">Séquence parente</div>
                                <a href="<?= $base ?>/sequence/<?= $sequence['id'] ?>" style="font-size:.85rem;font-weight:600">
                                    <?= htmlspecialchars($sequence['titre']) ?>
                                </a>
                                <?php if (!empty($sequence['matiere_label'])): ?>
                                    <div><span class="badge badge--bleu" style="margin-top:6px"><?= htmlspecialchars($sequence['matiere_label']) ?></span></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </form>

        <!-- Situations (mode édition) -->
        <?php if ($isEdit): ?>
            <?php $situations = \src\DAO\SituationDAO::getInstance()->findBySeance($s['id']); ?>
            <div class="card mt-32">
                <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                    <h3 class="card__titre">🎯 Situations (<?= count($situations) ?>)</h3>
                    <a href="<?= $base ?>/situation/create?seance_id=<?= $s['id'] ?>" class="btn btn--ambre btn--sm">+ Ajouter</a>
                </div>
                <div class="card__body">
                    <?php if (empty($situations)): ?>
                        <p class="text-muted text-sm">Aucune situation pour cette séance.</p>
                    <?php else: ?>
                        <div class="situations-list">
                            <?php foreach ($situations as $sit): ?>
                                <div class="situation-item" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                    <div>
                                        <strong>Situation <?= $sit['numero'] ?> — <?= htmlspecialchars($sit['titre']) ?></strong>
                                        <?php if ($sit['duree']): ?><span class="badge badge--ambre" style="margin-left:8px"><?= $sit['duree'] ?> min</span><?php endif; ?>
                                    </div>
                                    <div style="display:flex;gap:6px">
                                        <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/show" class="btn btn--ghost btn--sm">👁</a>
                                        <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/edit" class="btn btn--ghost btn--sm">✏️</a>
                                        <a href="<?= $base ?>/situation/<?= $sit['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄</a>
                                        <form action="<?= $base ?>/situation/<?= $sit['id'] ?>/delete" method="post" style="display:inline">
                                            <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Supprimer ?">🗑</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>
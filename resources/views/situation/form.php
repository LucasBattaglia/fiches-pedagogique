<?php
$pageTitle = !empty($situation['id']) ? 'Modifier la situation' : 'Nouvelle situation';
$activeNav = 'seq-list';
$isEdit    = !empty($situation['id']);
$sit       = $situation ?? [];
$seance    = $seance ?? \src\DAO\SeanceDAO::getInstance()->findById((int)($sit['seance_id'] ?? $seance_id ?? 0));
$sequence  = $seance ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : [];
$fromSeqId = $fromSeqId ?? null;
include __DIR__.'/../partials/layout_start.php';
?>
    <div class="container container--md">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/dashboard">Tableau de bord</a>
            <span class="breadcrumb__sep">›</span>
            <?php if ($sequence): ?>
                <a href="/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['titre']) ?></a>
                <span class="breadcrumb__sep">›</span>
                <a href="/seance/<?= $seance['id'] ?>/edit">Séance <?= $seance['numero'] ?></a>
                <span class="breadcrumb__sep">›</span>
            <?php endif; ?>
            <span><?= $isEdit ? 'Modifier situation '.$sit['numero'] : 'Nouvelle situation' ?></span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $msgs): foreach ((array)$msgs as $msg): ?>
                <div class="alert alert--<?= htmlspecialchars($type) ?>" data-dismiss="4000"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; endforeach; unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
            <div>
                <h1 style="font-family:var(--font-titre);font-size:1.5rem">
                    <?= $isEdit ? 'Situation N°'.$sit['numero'].' — '.htmlspecialchars($sit['titre']) : 'Nouvelle situation' ?>
                </h1>
                <p class="text-muted text-sm">Fiche de préparation de situation</p>
            </div>
            <?php if ($isEdit): ?>
                <a href="/situation/<?= $sit['id'] ?>/pdf" class="btn btn--ghost btn--sm" target="_blank">📄 PDF</a>
            <?php endif; ?>
        </div>

        <form action="<?= $isEdit ? '/situation/'.$sit['id'].'/update' : '/situation/create' ?>" method="post" id="situation-form">
            <input type="hidden" name="seance_id" value="<?= $seance['id'] ?? $seance_id ?? '' ?>">

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

                <!-- COLONNE GAUCHE -->
                <div>

                    <!-- Informations générales -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Informations générales</h3></div>
                        <div class="card__body">

                            <div class="form-group">
                                <label for="titre">Titre de la situation <span class="required">*</span></label>
                                <input type="text" id="titre" name="titre" required
                                       value="<?= htmlspecialchars($sit['titre'] ?? '') ?>"
                                       placeholder="Ex : Jeu de la balle brûlante">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="champ_apprentissage">Champ d'apprentissage</label>
                                    <input type="text" id="champ_apprentissage" name="champ_apprentissage"
                                           value="<?= htmlspecialchars($sit['champ_apprentissage'] ?? $seance['champ_apprentissage'] ?? '') ?>"
                                           placeholder="Ex : Activités athlétiques">
                                </div>
                                <div class="form-group">
                                    <label for="duree">Durée (minutes)</label>
                                    <input type="number" id="duree" name="duree" min="1" max="120"
                                           value="<?= htmlspecialchars((string)($sit['duree'] ?? '')) ?>" placeholder="15">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="afc">Attendu de Fin de Cycle (AFC)</label>
                                <textarea id="afc" name="afc" rows="2"
                                          placeholder="Attendu en lien avec le programme…"><?= htmlspecialchars($sit['afc'] ?? $seance['afc'] ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Objectifs -->
                    <div class="card mb-24" style="border-left:4px solid var(--ambre)">
                        <div class="card__header" style="background:var(--ambre-clair)"><h3 class="card__titre">Objectifs</h3></div>
                        <div class="card__body">
                            <div class="form-group">
                                <label for="objectif_moteur">Objectif moteur</label>
                                <textarea id="objectif_moteur" name="objectif_moteur" rows="2"
                                          placeholder="Ce que l'élève doit réaliser physiquement…"><?= htmlspecialchars($sit['objectif_moteur'] ?? '') ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="objectif_socio_affectif">Objectif socio-affectif</label>
                                    <textarea id="objectif_socio_affectif" name="objectif_socio_affectif" rows="2"
                                              placeholder="Dimension relationnelle, coopération…"><?= htmlspecialchars($sit['objectif_socio_affectif'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="objectif_cognitif">Objectif cognitif</label>
                                    <textarea id="objectif_cognitif" name="objectif_cognitif" rows="2"
                                              placeholder="Stratégie, compréhension des règles…"><?= htmlspecialchars($sit['objectif_cognitif'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- But, dispositif, organisation -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Dispositif</h3></div>
                        <div class="card__body">
                            <div class="form-group">
                                <label for="but">But de la situation</label>
                                <input type="text" id="but" name="but"
                                       value="<?= htmlspecialchars($sit['but'] ?? '') ?>"
                                       placeholder="Ce que l'élève doit faire pour gagner / réussir…">
                            </div>
                            <div class="form-group">
                                <label for="dispositif">Dispositif (espace, équipes…)</label>
                                <textarea id="dispositif" name="dispositif" rows="2"
                                          placeholder="Nombre de joueurs, espace de jeu, équipes…"><?= htmlspecialchars($sit['dispositif'] ?? '') ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="organisation">Organisation</label>
                                    <textarea id="organisation" name="organisation" rows="2"
                                              placeholder="Rôles, groupes, rotation…"><?= htmlspecialchars($sit['organisation'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="fonctionnement">Fonctionnement</label>
                                    <textarea id="fonctionnement" name="fonctionnement" rows="2"
                                              placeholder="Règles de la situation…"><?= htmlspecialchars($sit['fonctionnement'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="materiel">Matériel</label>
                                <input type="text" id="materiel" name="materiel"
                                       value="<?= htmlspecialchars($sit['materiel'] ?? '') ?>"
                                       placeholder="Balles, plots, cordes, feuilles…">
                            </div>
                            <div class="form-group">
                                <label for="consignes_base">Consignes de base</label>
                                <textarea id="consignes_base" name="consignes_base" rows="2"
                                          placeholder="Consignes données aux élèves au départ…"><?= htmlspecialchars($sit['consignes_base'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Variables d'évolution -->
                    <div class="card mb-24">
                        <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                            <h3 class="card__titre">Variables d'évolution</h3>
                            <button type="button" class="btn btn--outline btn--sm" data-add-table="table-variables" data-template="variables_evolution">+ Variable</button>
                        </div>
                        <div class="card__body" style="padding:0">
                            <table id="table-variables" class="dyn-table" style="width:100%">
                                <thead>
                                <tr>
                                    <th>Variable</th>
                                    <th>Complexifier (+)</th>
                                    <th>Simplifier (–)</th>
                                    <th style="width:40px"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $ve = is_array($sit['variables_evolution'] ?? null) ? $sit['variables_evolution'] : (json_decode($sit['variables_evolution'] ?? '[]', true) ?? []);
                                foreach ($ve as $i => $row): ?>
                                    <tr>
                                        <td><input type="text" name="variables_evolution[<?=$i?>][variable]" value="<?= htmlspecialchars($row['variable']??'') ?>" placeholder="Ex : Distance"></td>
                                        <td><input type="text" name="variables_evolution[<?=$i?>][plus]" value="<?= htmlspecialchars($row['plus']??'') ?>" placeholder="Augmenter la distance"></td>
                                        <td><input type="text" name="variables_evolution[<?=$i?>][moins]" value="<?= htmlspecialchars($row['moins']??'') ?>" placeholder="Réduire la distance"></td>
                                        <td><button type="button" class="btn btn--ghost btn--sm btn-remove-row">✕</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Critères -->
                    <div class="card mb-24">
                        <div class="card__header"><h3 class="card__titre">Critères de réalisation & de réussite</h3></div>
                        <div class="card__body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="criteres_realisation">Critères de réalisation</label>
                                    <textarea id="criteres_realisation" name="criteres_realisation" rows="3"
                                              placeholder="Ce que l'élève doit faire pour réussir…"><?= htmlspecialchars($sit['criteres_realisation'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="criteres_reussite">Critères de réussite</label>
                                    <textarea id="criteres_reussite" name="criteres_reussite" rows="3"
                                              placeholder="Indicateurs observables de réussite…"><?= htmlspecialchars($sit['criteres_reussite'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comportements & remédiations -->
                    <div class="card mb-24">
                        <div class="card__header" style="display:flex;align-items:center;justify-content:space-between">
                            <h3 class="card__titre">Comportements & remédiations</h3>
                            <button type="button" class="btn btn--outline btn--sm" data-add-table="table-comportements-situation" data-template="comportements_situation">+ Ligne</button>
                        </div>
                        <div class="card__body" style="padding:0">
                            <table id="table-comportements-situation" class="dyn-table" style="width:100%">
                                <thead>
                                <tr><th>Comportement observé</th><th>Remédiation proposée</th><th style="width:40px"></th></tr>
                                </thead>
                                <tbody>
                                <?php
                                $cr = is_array($sit['comportements_remediations'] ?? null) ? $sit['comportements_remediations'] : (json_decode($sit['comportements_remediations'] ?? '[]', true) ?? []);
                                foreach ($cr as $i => $row): ?>
                                    <tr>
                                        <td><input type="text" name="comportements_situation[<?=$i?>][comportement]" value="<?= htmlspecialchars($row['comportement']??'') ?>"></td>
                                        <td><input type="text" name="comportements_situation[<?=$i?>][remediation]" value="<?= htmlspecialchars($row['remediation']??'') ?>"></td>
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
                            <button type="submit" class="btn btn--ambre" style="justify-content:center">
                                💾 <?= $isEdit ? 'Enregistrer' : 'Créer la situation' ?>
                            </button>
                            <?php if ($seance): ?>
                                <a href="/seance/<?= $seance['id'] ?>/edit" class="btn btn--ghost" style="justify-content:center">Retour à la séance</a>
                            <?php endif; ?>
                            <?php if ($isEdit): ?>
                                <hr style="border:none;border-top:1px solid var(--gris-300)">
                                <a href="/situation/<?= $sit['id'] ?>/pdf" class="btn btn--outline btn--sm" target="_blank" style="justify-content:center">
                                    📄 Télécharger PDF
                                </a>
                                <form action="/situation/<?= $sit['id'] ?>/delete" method="post">
                                    <button type="submit" class="btn btn--danger btn--sm" style="width:100%;justify-content:center"
                                            data-confirm="Supprimer cette situation ?">🗑 Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if ($seance): ?>
                            <div class="card__footer">
                                <div class="text-sm text-muted mb-8">Séance parente</div>
                                <div style="font-size:.85rem;font-weight:600">Séance <?= $seance['numero'] ?> — <?= htmlspecialchars($seance['titre']) ?></div>
                                <?php if ($sequence): ?>
                                    <div class="text-sm text-muted mt-8">
                                        <a href="/sequence/<?= $sequence['id'] ?>"><?= htmlspecialchars(mb_strimwidth($sequence['titre'], 0, 40, '…')) ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- fin grid -->
        </form>
    </div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>
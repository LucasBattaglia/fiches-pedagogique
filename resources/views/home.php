<?php $pageTitle = 'Accueil'; $activeNav = ''; include __DIR__.'/partials/layout_start.php'; ?>

<!-- HERO -->
<div class="hero">
  <div class="container hero__content">
    <h1>Préparez vos fiches pédagogiques<br>en toute simplicité</h1>
    <p>Un outil dédié aux enseignants pour créer, organiser et partager des fiches de séquence, séance et situation, alignées avec les programmes officiels 2025.</p>
    <div class="hero__actions">
      <a href="/auth/register" class="btn btn--white btn--lg">Commencer gratuitement</a>
      <a href="/explorer" class="btn btn--outline btn--lg" style="border-color:rgba(255,255,255,.5);color:white">
        Explorer les fiches
      </a>
    </div>
  </div>
</div>

<!-- FEATURES -->
<div class="container" style="padding-top:64px;padding-bottom:64px">
  <div style="text-align:center;margin-bottom:48px">
    <h2 style="font-family:var(--font-titre);font-size:2rem;margin-bottom:12px">Tout ce dont vous avez besoin</h2>
    <p class="text-muted" style="font-size:1.05rem;max-width:560px;margin:0 auto">
      Des outils conçus par des enseignants, pour des enseignants.
    </p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px">
    <?php
    $features = [
      ['icon'=>'📋','title'=>'Fiches de séquence','desc'=>'Structurez vos séquences avec objectifs, tâche finale, tableau des séances et remédiations.'],
      ['icon'=>'📅','title'=>'Fiches de séance','desc'=>'Détaillez chaque séance : déroulement, critères de réalisation, variables didactiques.'],
      ['icon'=>'🎯','title'=>'Fiches de situation','desc'=>'Créez des fiches de situation imbriquées dans vos séances, avec variables d\'évolution.'],
      ['icon'=>'📄','title'=>'Export PDF','desc'=>'Générez des PDF professionnels de vos fiches complètes, prêts à imprimer.'],
      ['icon'=>'🎓','title'=>'Programmes 2025','desc'=>'Sélectionnez les compétences directement depuis les programmes officiels de la rentrée 2025.'],
      ['icon'=>'🌐','title'=>'Partage communautaire','desc'=>'Partagez vos fiches et inspirez-vous de celles des autres enseignants.'],
    ];
    foreach ($features as $f):
    ?>
    <div class="card fade-in">
      <div class="card__body" style="text-align:center;padding:28px 20px">
        <div style="font-size:2.2rem;margin-bottom:14px"><?= $f['icon'] ?></div>
        <h3 style="font-family:var(--font-titre);font-size:1.1rem;margin-bottom:8px"><?= $f['title'] ?></h3>
        <p class="text-muted text-sm"><?= $f['desc'] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- CTA -->
<div style="background:var(--bleu-pale);padding:56px 0;text-align:center">
  <div class="container">
    <h2 style="font-family:var(--font-titre);font-size:1.8rem;margin-bottom:12px">Prêt à commencer ?</h2>
    <p class="text-muted" style="margin-bottom:28px">Gratuit, sans publicité, accessible depuis n'importe quel appareil.</p>
    <a href="/auth/register" class="btn btn--primary btn--lg">Créer un compte gratuit</a>
    <span style="margin:0 12px;color:var(--gris-300)">ou</span>
    <a href="/auth/login" class="btn btn--outline btn--lg">Se connecter</a>
  </div>
</div>

<?php include __DIR__.'/partials/layout_end.php'; ?>

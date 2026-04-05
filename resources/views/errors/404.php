<?php $pageTitle = 'Page introuvable'; $activeNav = ''; include __DIR__.'/../partials/layout_start.php'; ?>
<div class="container container--sm" style="padding-top:80px;text-align:center">
  <div style="font-size:5rem;margin-bottom:16px">📄</div>
  <h1 style="font-family:var(--font-titre);font-size:2.5rem;margin-bottom:12px;color:var(--gris-900)">404</h1>
  <p style="font-size:1.1rem;color:var(--gris-500);margin-bottom:32px">Cette page n'existe pas ou a été déplacée.</p>
  <a href="/dashboard" class="btn btn--primary btn--lg">Retour au tableau de bord</a>
</div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>

<?php $pageTitle = 'Connexion'; $activeNav = ''; include __DIR__.'/../partials/layout_start.php'; ?>
<div class="container container--sm" style="padding-top:48px">
  <div class="card" style="max-width:440px;margin:0 auto">
    <div class="card__body">
      <div style="text-align:center;margin-bottom:24px">
        <div style="font-size:2.5rem;margin-bottom:8px">📚</div>
        <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:4px">Connexion</h1>
        <p class="text-muted text-sm">Accédez à vos fiches pédagogiques</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert--error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="<?= $base ?>/auth/login" method="post">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? $base.'/dashboard') ?>">
        <div class="form-group">
          <label for="email">Email <span class="required">*</span></label>
          <input type="email" id="email" name="email" required autofocus
                 value="<?= htmlspecialchars($email ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="password">Mot de passe <span class="required">*</span></label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;margin-top:4px">
          Se connecter
        </button>
      </form>

      <?php if (!empty($googleUrl)): ?>
      <div style="text-align:center;margin:20px 0;color:var(--gris-300);font-size:.85rem">— ou —</div>
      <a href="<?= htmlspecialchars($googleUrl) ?>" class="btn btn--outline" style="width:100%;justify-content:center;gap:8px">
        <svg width="18" height="18" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continuer avec Google
      </a>
      <?php endif; ?>

      <p class="text-sm text-muted" style="text-align:center;margin-top:20px">
        Pas encore de compte ? <a href="<?= $base ?>/auth/register">S'inscrire</a>
      </p>
    </div>
  </div>
</div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>

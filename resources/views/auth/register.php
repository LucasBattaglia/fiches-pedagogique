<?php $pageTitle = 'Inscription'; $activeNav = ''; include __DIR__.'/../partials/layout_start.php'; ?>
<div class="container container--sm" style="padding-top:48px">
  <div class="card" style="max-width:440px;margin:0 auto">
    <div class="card__body">
      <div style="text-align:center;margin-bottom:24px">
        <div style="font-size:2.5rem;margin-bottom:8px">📚</div>
        <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:4px">Créer un compte</h1>
        <p class="text-muted text-sm">Rejoignez la communauté des enseignants</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert--error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="<?= $base ?>/auth/register" method="post">
        <div class="form-row">
          <div class="form-group">
            <label for="prenom">Prénom <span class="required">*</span></label>
            <input type="text" id="prenom" name="prenom" required autofocus value="<?= htmlspecialchars($prenom ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($nom ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email <span class="required">*</span></label>
          <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="password">Mot de passe <span class="required">*</span></label>
          <input type="password" id="password" name="password" required minlength="8">
          <p class="form-hint">8 caractères minimum</p>
        </div>
        <div class="form-group">
          <label for="password_confirm">Confirmer <span class="required">*</span></label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
        </div>
        <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;margin-top:4px">
          Créer mon compte
        </button>
      </form>

      <p class="text-sm text-muted" style="text-align:center;margin-top:20px">
        Déjà un compte ? <a href="<?= $base ?>/auth/login">Se connecter</a>
      </p>
    </div>
  </div>
</div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>

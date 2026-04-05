<?php
$pageTitle = 'Mon profil';
$activeNav = 'profil';
include __DIR__.'/partials/layout_start.php';
?>
<div class="container container--sm">
  <div class="breadcrumb">
    <a href="/dashboard">Tableau de bord</a>
    <span class="breadcrumb__sep">›</span>
    <span>Mon profil</span>
  </div>

  <?php if (!empty($_SESSION['flash']['success'])): ?>
    <?php foreach ((array)$_SESSION['flash']['success'] as $msg): ?>
      <div class="alert alert--success" data-dismiss="4000">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endforeach; unset($_SESSION['flash']['success']); ?>
  <?php endif; ?>

  <!-- Infos profil -->
  <div class="card mb-24">
    <div class="card__header">
      <h2 class="card__titre">Informations personnelles</h2>
    </div>
    <div class="card__body">
      <form action="/profil" method="post">
        <div class="form-row">
          <div class="form-group">
            <label for="prenom">Prénom <span class="required">*</span></label>
            <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Email</label>
          <div class="field-ro"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          <p class="form-hint">L'email ne peut pas être modifié.</p>
        </div>
        <?php if (!empty($user['oauth_provider'])): ?>
        <div class="alert alert--info" style="margin-bottom:16px">
          🔗 Compte lié via <strong><?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?></strong>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn--primary">Enregistrer les modifications</button>
      </form>
    </div>
  </div>

  <!-- Changer mot de passe -->
  <?php if (empty($user['oauth_provider']) || $user['oauth_provider'] === null): ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__titre">Changer le mot de passe</h2>
    </div>
    <div class="card__body">
      <?php if (!empty($_SESSION['flash']['error'])): ?>
        <?php foreach ((array)$_SESSION['flash']['error'] as $msg): ?>
          <div class="alert alert--error">❌ <?= htmlspecialchars($msg) ?></div>
        <?php endforeach; unset($_SESSION['flash']['error']); ?>
      <?php endif; ?>
      <form action="/profil/password" method="post">
        <div class="form-group">
          <label for="old_password">Mot de passe actuel <span class="required">*</span></label>
          <input type="password" id="old_password" name="old_password" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="new_password">Nouveau mot de passe <span class="required">*</span></label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmer <span class="required">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
          </div>
        </div>
        <button type="submit" class="btn btn--outline">Changer le mot de passe</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Danger zone -->
  <div style="margin-top:32px;padding:16px;border:1px solid var(--rouge-clair);border-radius:var(--rayon-lg);background:var(--rouge-clair)">
    <h3 style="color:var(--rouge);margin-bottom:8px;font-size:.95rem">Zone de danger</h3>
    <p style="font-size:.85rem;color:#7f1d1d;margin-bottom:12px">La déconnexion mettra fin à votre session en cours.</p>
    <a href="/auth/logout" class="btn btn--danger btn--sm">Se déconnecter</a>
  </div>
</div>
<?php include __DIR__.'/partials/layout_end.php'; ?>

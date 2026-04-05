<?php use src\Service\AuthService; $isLogged = AuthService::isLoggedIn(); global $_BASE; $base = $_BASE ?? ''; ?>
<?php if ($isLogged): ?>
    </div><!-- fin page-body -->
  </div><!-- fin main-content -->
</div><!-- fin flex wrapper connecté -->
<?php else: ?>
  </div><!-- fin flex:1 -->
  <footer style="background:white;border-top:1px solid #e5e7eb;padding:20px;text-align:center;font-size:.8rem;color:#6b7280">
    Fiches Pédagogiques &copy; <?= date('Y') ?> — Outil libre pour les enseignants
  </footer>
</div><!-- fin layout non connecté -->
<?php endif; ?>

<script src="<?= $base ?>/static/js/app.js" defer></script>
</body>
</html>

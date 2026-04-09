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

<?php if ($isLogged): ?>
    <nav class="mobile-nav">
        <a href="<?= $base ?>/dashboard" class="mobile-nav__item <?= $activeNav==='dashboard' ? 'mobile-nav__item--active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Accueil
        </a>
        <a href="<?= $base ?>/sequence/list" class="mobile-nav__item <?= $activeNav==='seq-list' ? 'mobile-nav__item--active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Séquences
        </a>
        <a href="<?= $base ?>/programmes" class="mobile-nav__item <?= $activeNav==='programmes' ? 'mobile-nav__item--active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
            Programmes
        </a>
        <a href="<?= $base ?>/explorer" class="mobile-nav__item <?= $activeNav==='explorer' ? 'mobile-nav__item--active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Explorer
        </a>
        <a href="<?= $base ?>/profil" class="mobile-nav__item <?= $activeNav==='profil' ? 'mobile-nav__item--active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil
        </a>
    </nav>
<?php endif; ?>

</body>
</html>

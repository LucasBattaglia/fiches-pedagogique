<?php
use src\Service\AuthService;

// Base URL pour les liens dans les vues
global $_BASE;
$base = $_BASE ?? ''; // Vide avec VirtualHost, /fiches-pedagogiques avec WAMP sous-dossier

$user      = AuthService::currentUser();
$isLogged  = AuthService::isLoggedIn();
$pageTitle = $pageTitle ?? 'Fiches Pédagogiques';
$activeNav = $activeNav ?? '';
?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($pageTitle) ?> – Fiches Pédagogiques</title>
        <link rel="stylesheet" href="<?= $base ?>/static/css/app.css">
        <?= $extraHead ?? '' ?>
    </head>
<body data-base="<?= htmlspecialchars($base) ?>">

<?php if ($isLogged): ?>
    <!-- ═══ LAYOUT CONNECTÉ : sidebar + main ═══ -->
    <div style="display:flex;min-height:100vh">

    <!-- Sidebar -->
    <aside style="width:240px;flex-shrink:0;background:#1e3a5f;color:white;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto">

        <!-- Logo -->
        <div style="padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,.1)">
            <div style="font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;color:white;display:flex;align-items:center;gap:8px">
                <span style="background:#2563eb;border-radius:6px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:.9rem">📚</span>
                Fiches Péda
            </div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.5);margin-top:4px">Outil de préparation</div>
        </div>

        <!-- Nav -->
        <nav style="flex:1;padding:12px 8px">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.35);padding:12px 10px 6px">Mes fiches</div>

            <a href="<?= $base ?>/dashboard"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='dashboard'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='dashboard'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px;transition:all .15s">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Tableau de bord
            </a>

            <a href="<?= $base ?>/sequence/create"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='seq-new'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='seq-new'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nouvelle séquence
            </a>

            <a href="<?= $base ?>/sequence/list"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='seq-list'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='seq-list'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Mes séquences
            </a>

            <a href="<?= $base ?>/seance/list"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='seance-list'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='seance-list'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Mes séances
            </a>

            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.35);padding:16px 10px 6px">Explorer</div>

            <a href="<?= $base ?>/explorer"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='explorer'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='explorer'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Fiches publiques
            </a>

            <a href="<?= $base ?>/programmes"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='programmes'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='programmes'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                Programmes
            </a>

            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.35);padding:16px 10px 6px">Compte</div>

            <a href="<?= $base ?>/profil"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='profil'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='profil'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Mon profil
            </a>

            <?php if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())){ ?>
            <a href="<?= $base ?>/admin/programmes"
               style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:7px;color:<?= $activeNav==='profil'?'white':'rgba(255,255,255,.7)' ?>;background:<?= $activeNav==='profil'?'rgba(255,255,255,.12)':'transparent' ?>;text-decoration:none;font-size:.88rem;margin-bottom:2px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Gestion des programmes
            </a>
            <?php } ?>
        </nav>

        <!-- Footer utilisateur -->
        <div style="padding:12px;border-top:1px solid rgba(255,255,255,.1)">
            <a href="<?= $base ?>/auth/logout" style="display:flex;align-items:center;gap:10px;text-decoration:none;padding:8px;border-radius:7px;transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='transparent'">
                <div style="width:32px;height:32px;border-radius:50%;background:#2563eb;display:flex;align-items:center;justify-content:center;color:white;font-size:.8rem;font-weight:700;flex-shrink:0;overflow:hidden">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                        <?= strtoupper(mb_substr($user['prenom'] ?? 'U', 0, 1)) . strtoupper(mb_substr($user['nom'] ?? '', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div style="min-width:0">
                    <div style="font-size:.82rem;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </div>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.45)">Déconnexion</div>
                </div>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <div style="flex:1;min-width:0;display:flex;flex-direction:column">

    <!-- Topbar -->
    <div style="background:white;border-bottom:1px solid #e5e7eb;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 3px rgba(0,0,0,.06)">
        <span style="font-size:.95rem;font-weight:600;color:#374151"><?= htmlspecialchars($pageTitle) ?></span>
        <div style="display:flex;gap:8px">
            <a href="<?= $base ?>/sequence/create" class="btn btn--primary btn--sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Nouvelle séquence
            </a>
        </div>
    </div>

    <!-- Page body -->
    <div style="flex:1;padding:28px 28px 64px">
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php foreach ($_SESSION['flash'] as $type => $msgs): ?>
            <?php foreach ((array)$msgs as $msg): ?>
                <div class="alert alert--<?= htmlspecialchars($type) ?>" data-dismiss="4000">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

<?php else: ?>
    <!-- ═══ LAYOUT NON CONNECTÉ : page simple ═══ -->
    <div style="min-height:100vh;display:flex;flex-direction:column">
    <nav style="background:white;border-bottom:1px solid #e5e7eb;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,.06)">
        <a href="<?= $base ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none;font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1e40af">
            <span style="background:#2563eb;border-radius:7px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;color:white;font-size:.9rem">📚</span>
            Fiches Pédagogiques
        </a>
        <div style="display:flex;gap:8px">
            <a href="<?= $base ?>/explorer" class="btn btn--ghost btn--sm">Explorer</a>
            <a href="<?= $base ?>/auth/login" class="btn btn--outline btn--sm">Se connecter</a>
            <a href="<?= $base ?>/auth/register" class="btn btn--primary btn--sm">S'inscrire</a>
        </div>
    </nav>
    <div style="flex:1">
<?php endif; ?>
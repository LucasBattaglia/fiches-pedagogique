<?php
$pageTitle = 'Invitation à collaborer';
$activeNav = '';
include __DIR__.'/../partials/layout_start.php';
?>
    <div class="container container--sm" style="padding-top:64px">
        <div class="card" style="max-width:480px;margin:0 auto;text-align:center">
            <div class="card__body" style="padding:40px 32px">

                <div style="font-size:3rem;margin-bottom:16px">🎯</div>
                <h1 style="font-family:var(--font-titre);font-size:1.5rem;margin-bottom:8px">
                    Invitation à collaborer
                </h1>

                <?php if ($invitation): ?>
                    <p class="text-muted" style="margin-bottom:24px">
                        <strong><?= htmlspecialchars(($invitation['inviteur_prenom']??'').' '.($invitation['inviteur_nom']??'')) ?></strong>
                        vous invite à collaborer sur la situation :
                    </p>
                    <div style="background:var(--ambre-clair);border:1px solid #fde68a;border-radius:var(--rayon-lg);padding:16px 20px;margin-bottom:28px">
                        <div style="font-family:var(--font-titre);font-size:1.1rem;color:var(--ambre)">
                            <?= htmlspecialchars($invitation['situation_titre']) ?>
                        </div>
                        <div class="text-sm text-muted" style="margin-top:4px">Rôle : Éditeur</div>
                    </div>

                    <?php if ($isLogged): ?>
                        <form action="<?= $base ?>/situation/invitation/<?= htmlspecialchars($token) ?>/accepter" method="post">
                            <button type="submit" class="btn btn--ambre" style="width:100%;justify-content:center;margin-bottom:12px">
                                ✅ Accepter et rejoindre
                            </button>
                        </form>
                        <a href="<?= $base ?>/dashboard" class="btn btn--ghost" style="width:100%;justify-content:center">Refuser</a>
                    <?php else: ?>
                        <div class="alert alert--info" style="text-align:left;margin-bottom:20px">
                            Vous devez être connecté pour accepter cette invitation.
                        </div>
                        <a href="<?= $base ?>/auth/login?redirect=<?= urlencode($base.'/situation/invitation/'.$token) ?>"
                           class="btn btn--primary" style="width:100%;justify-content:center;margin-bottom:12px">
                            Se connecter pour accepter
                        </a>
                        <a href="<?= $base ?>/auth/register?redirect=<?= urlencode($base.'/situation/invitation/'.$token) ?>"
                           class="btn btn--outline" style="width:100%;justify-content:center">
                            Créer un compte
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert--error" style="text-align:left">
                        ❌ Lien invalide, expiré ou déjà utilisé.
                    </div>
                    <a href="<?= $base ?>/dashboard" class="btn btn--ghost" style="margin-top:16px">Retour</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__.'/../partials/layout_end.php'; ?>
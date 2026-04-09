<?php
/**
 * resources/views/sequence/invitation.php
 * Page d'acceptation d'une invitation à rejoindre une séquence.
 *
 * Variables :
 *  - $invitation : array  — données de l'invitation (sequence_titre, inviter_nom, etc.)
 *  - $token      : string — token de l'invitation
 *  - $isLogged   : bool   — utilisateur connecté ou non
 */
$pageTitle = 'Invitation — Co-enseignement';
$activeNav = '';
include __DIR__ . '/../partials/layout_start.php';
?>

<div class="container container--sm" style="padding-top:56px;padding-bottom:80px">

    <!-- Carte centrale -->
    <div class="card" style="max-width:520px;margin:0 auto;overflow:hidden">

        <!-- En-tête coloré -->
        <div style="background:linear-gradient(135deg,var(--bleu) 0%,var(--bleu-med) 100%);padding:32px 28px;color:white;text-align:center">
            <div style="font-size:3rem;margin-bottom:12px">🤝</div>
            <h1 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:8px;color:white">
                Invitation au co-enseignement
            </h1>
            <p style="opacity:.85;font-size:.9rem">
                <?= htmlspecialchars(($invitation['inviter_prenom'] ?? '') . ' ' . ($invitation['inviter_nom'] ?? 'Un enseignant')) ?>
                vous invite à collaborer sur une séquence.
            </p>
        </div>

        <div class="card__body" style="padding:28px">

            <!-- Détails de la séquence -->
            <div style="background:var(--bleu-pale);border-radius:var(--rayon);padding:16px 20px;margin-bottom:24px;border-left:4px solid var(--bleu-med)">
                <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--bleu-med);margin-bottom:6px">Séquence partagée</div>
                <div style="font-family:var(--font-titre);font-size:1.15rem;color:var(--gris-900);font-weight:600">
                    <?= htmlspecialchars($invitation['sequence_titre']) ?>
                </div>
            </div>

            <!-- Ce que vous pourrez faire -->
            <div style="margin-bottom:24px">
                <div style="font-size:.82rem;font-weight:600;color:var(--gris-700);margin-bottom:10px">En tant que collaborateur, vous pourrez :</div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
                    <li style="display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--gris-700)">
                        <span style="color:var(--vert);font-size:1rem">✅</span> Voir et modifier toutes les séances et situations
                    </li>
                    <li style="display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--gris-700)">
                        <span style="color:var(--vert);font-size:1rem">✅</span> Ajouter de nouvelles séances et situations
                    </li>
                    <li style="display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--gris-700)">
                        <span style="color:var(--vert);font-size:1rem">✅</span> Exporter la séquence en PDF
                    </li>
                    <li style="display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--gris-700)">
                        <span style="color:var(--rouge);font-size:1rem">❌</span> Supprimer la séquence (réservé au propriétaire)
                    </li>
                    <li style="display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--gris-700)">
                        <span style="color:var(--rouge);font-size:1rem">❌</span> Gérer les autres collaborateurs (réservé au propriétaire)
                    </li>
                </ul>
            </div>

            <?php if ($isLogged): ?>
                <!-- Utilisateur connecté → accepter directement -->
                <form action="<?= $base ?>/sequence/invitation/<?= htmlspecialchars($token) ?>/accepter" method="post">
                    <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;font-size:1rem;padding:12px">
                        🤝 Rejoindre la séquence
                    </button>
                </form>
                <p class="text-sm text-muted" style="text-align:center;margin-top:12px">
                    Vous rejoindrez la séquence avec votre compte actuel.
                </p>

            <?php else: ?>
                <!-- Non connecté → connexion ou inscription -->
                <div class="alert alert--info" style="margin-bottom:20px;font-size:.84rem">
                    💡 Vous devez avoir un compte pour rejoindre cette séquence. L'invitation sera automatiquement acceptée après connexion.
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="<?= $base ?>/auth/login?invite=<?= htmlspecialchars($token) ?>"
                       class="btn btn--primary" style="flex:1;justify-content:center">
                        Se connecter
                    </a>
                    <a href="<?= $base ?>/auth/register?invite=<?= htmlspecialchars($token) ?>"
                       class="btn btn--outline" style="flex:1;justify-content:center">
                        Créer un compte
                    </a>
                </div>
            <?php endif; ?>

        </div><!-- /.card__body -->
    </div><!-- /.card -->

    <p class="text-sm text-muted" style="text-align:center;margin-top:24px">
        Vous avez reçu ce lien par erreur ? Ignorez simplement cette page.
    </p>

</div>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>

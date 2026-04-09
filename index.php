<?php
// index.php - Routeur principal

declare(strict_types=1);

// ── Chargement .env ───────────────────────────────────────────
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#') continue;
        if (strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k);
        $_v = trim($_v);
        putenv("$_k=$_v");
        $_ENV[$_k] = $_v;
        $_SERVER[$_k] = $_v;
    }
}
unset($_envFile, $_line, $_k, $_v);

session_set_cookie_params(['samesite' => 'Lax', 'httponly' => true]);
session_start();

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';
\src\DAO\ConnectionPool::init($config['db']);

// ── Helpers ───────────────────────────────────────────────────
function view(string $template, array $vars = []): void
{
    extract($vars);
    require __DIR__ . '/resources/views/' . ltrim($template, '/') . '.php';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function currentUserId(): ?int
{
    return \src\Service\AuthService::currentUserId();
}

function requireLogin(): void
{
    \src\Service\AuthService::requireLogin($_SERVER['REQUEST_URI'] ?? '/');
}

function nullInt(mixed $v): ?int
{
    return ($v === null || $v === '') ? null : (int)$v;
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Routing ───────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$uri = rtrim($uri, '/') ?: '/';

$_BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($_BASE === '.' || $_BASE === '\\') $_BASE = '';
if ($_BASE !== '' && str_starts_with($uri, $_BASE)) {
    $uri = substr($uri, strlen($_BASE)) ?: '/';
}
$uri = $uri ?: '/';

function url(string $path = ''): string
{
    global $_BASE;
    return $_BASE . '/' . ltrim($path, '/');
}

// Fichiers statiques
if (preg_match('#^/static/#', $uri)) {
    $path = __DIR__ . '/resources' . $uri;
    if (is_file($path)) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png',
            'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'woff2' => 'font/woff2'];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($path);
        exit;
    }
}

// ============================================================
//  ROUTES
//  RÈGLE CRITIQUE : les routes exactes (===) AVANT les preg_match
// ============================================================
match (true) {

    // ── ACCUEIL ──────────────────────────────────────────────
    $uri === '/' && $method === 'GET' => (function () {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        view('home');
    })(),

    // ── DASHBOARD ────────────────────────────────────────────
    $uri === '/dashboard' && $method === 'GET' => (function () {
        requireLogin();
        $seqs = \src\DAO\SequenceDAO::getInstance()->findByUser(currentUserId(), 10);
        $pub = \src\DAO\SequenceDAO::getInstance()->findPublic([], 6);
        view('dashboard', ['sequences' => $seqs, 'publiques' => $pub]);
    })(),

    // ── API publiques ─────────────────────────────────────────
    $uri === '/api/classes' && $method === 'GET' => (function () {
        $cid = (int)($_GET['cycle_id'] ?? 0);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getClassesByCycle($cid));
    })(),

    $uri === '/api/matieres' && $method === 'GET' => (function () {
        $cid = ($_GET['cycle_id'] ?? '') !== '' ? (int)$_GET['cycle_id'] : null;
        $clid = ($_GET['classe_id'] ?? '') !== '' ? (int)$_GET['classe_id'] : null;
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getMatieresByCycleClasse($cid, $clid));
    })(),

    $uri === '/api/programme-versions' && $method === 'GET' => (function () {
        $cid = (int)($_GET['cycle_id'] ?? 0);
        $clid = ($_GET['classe_id'] ?? '') !== '' ? (int)$_GET['classe_id'] : null;
        $mid = (int)($_GET['matiere_id'] ?? 0);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getVersions($cid, $clid, $mid));
    })(),

    $uri === '/api/programme-items' && $method === 'GET' => (function () {
        $vid = (int)($_GET['version_id'] ?? 0);
        if (!$vid) jsonResponse([]);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getItemsFlat($vid));
    })(),

    // ── AUTH ──────────────────────────────────────────────────
    $uri === '/auth/login' && $method === 'GET' => (function () use ($config) {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        $googleUrl = !empty($config['oauth']['google']['client_id'])
            ? \src\Service\AuthService::getGoogleAuthUrl($config['oauth']['google']) : null;
        view('auth/login', ['googleUrl' => $googleUrl, 'errors' => []]);
    })(),

    $uri === '/auth/login' && $method === 'POST' => (function () use ($config) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
        if (strlen($password) < 6) $errors['password'] = 'Mot de passe trop court.';
        if (!$errors) {
            $user = \src\DAO\UserDAO::getInstance()->login($email, $password);
            if ($user) {
                \src\Service\AuthService::login($user);
                redirect($_POST['redirect'] ?? '/dashboard');
            }
            $errors['global'] = 'Email ou mot de passe incorrect.';
        }
        $googleUrl = !empty($config['oauth']['google']['client_id'])
            ? \src\Service\AuthService::getGoogleAuthUrl($config['oauth']['google']) : null;
        view('auth/login', ['errors' => $errors, 'googleUrl' => $googleUrl]);
    })(),

    $uri === '/auth/register' && $method === 'GET' => (function () {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        view('auth/register', ['errors' => []]);
    })(),

    $uri === '/auth/register' && $method === 'POST' => (function () {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
        if (strlen($password) < 8) $errors['password'] = 'Mot de passe trop court (8 car. min).';
        if (empty($nom)) $errors['nom'] = 'Nom requis.';
        if (empty($prenom)) $errors['prenom'] = 'Prénom requis.';
        if (!$errors && \src\DAO\UserDAO::getInstance()->emailExists($email)) $errors['email'] = 'Email déjà utilisé.';
        if (!$errors) {
            $id = \src\DAO\UserDAO::getInstance()->create($email, $password, $nom, $prenom);
            \src\Service\AuthService::login(\src\DAO\UserDAO::getInstance()->findById($id));
            flash('success', 'Bienvenue ' . $prenom . ' !');
            redirect(url('dashboard'));
        }
        view('auth/register', ['errors' => $errors]);
    })(),

    $uri === '/auth/google/callback' && $method === 'GET' => (function () use ($config) {
        $code = $_GET['code'] ?? '';
        if (!$code) redirect(url('auth/login'));
        $user = \src\Service\AuthService::handleGoogleCallback($config['oauth']['google'], $code);
        if (!$user) {
            flash('error', 'Erreur Google OAuth.');
            redirect(url('auth/login'));
        }
        \src\Service\AuthService::login($user);
        redirect(url('dashboard'));
    })(),

    $uri === '/auth/logout' => (function () {
        \src\Service\AuthService::logout();
        redirect(url(''));
    })(),
    $uri === '/auth/google/init' => (function () {
        requireLogin();
        (new \src\Service\GoogleAuthService())->redirect();
    })(),
    $uri === '/auth/google/token-callback' => (function () {
        (new \src\Service\GoogleAuthService())->callback();
    })(),

    // ── SÉQUENCES ─────────────────────────────────────────────
    $uri === '/sequence/list' && $method === 'GET' => (function () {
        requireLogin();
        view('sequence/index', ['sequences' => \src\DAO\SequenceDAO::getInstance()->findByUser(currentUserId())]);
    })(),

    $uri === '/sequence/create' && $method === 'GET' => (function () {
        requireLogin();
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('sequence/form', ['sequence' => [], 'cycles' => $dao->getCycles(), 'matieres' => $dao->getMatieres()]);
    })(),

    $uri === '/sequence/create' && $method === 'POST' => (function () {
        requireLogin();
        $data = $_POST;
        $data['cycle_id'] = nullInt($_POST['cycle_id'] ?? null);
        $data['classe_id'] = nullInt($_POST['classe_id'] ?? null);
        $data['matiere_id'] = nullInt($_POST['matiere_id'] ?? null);
        $data['programme_version_id'] = nullInt($_POST['programme_version_id'] ?? null);
        $data['nb_seances'] = nullInt($_POST['nb_seances'] ?? 1) ?? 1;
        $data['programme_items'] = array_map('intval', $_POST['programme_items'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_sequence'] ?? []);
        try {
            $id = \src\DAO\SequenceDAO::getInstance()->create(currentUserId(), $data);
            flash('success', 'Séquence créée !');
            redirect(url('sequence/') . $id);
        } catch (\Exception $e) {
            flash('error', 'Erreur : ' . $e->getMessage());
            redirect(url('sequence/create'));
        }
    })(),

    preg_match('#^/sequence/(\d+)$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $id = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        $uid = currentUserId();
        if (!$seq['is_public'] && $seq['user_id'] !== $uid) {
            http_response_code(403);
            view('errors/403');
            return;
        }
        $seances = \src\DAO\SeanceDAO::getInstance()->findBySequence($id);
        $sitsBySeance = [];
        foreach ($seances as $s) $sitsBySeance[$s['id']] = \src\DAO\SituationDAO::getInstance()->findBySeance($s['id']);
        $progItems = [];
        if (!empty($seq['programme_items'])) {
            $allItems = \src\DAO\ProgrammeDAO::getInstance()->getItemsFlat($seq['programme_version_id'] ?? 0);
            $progItems = array_filter($allItems, fn($i) => in_array($i['id'], $seq['programme_items']));
        }
        $seancesDisponibles = [];
        if ($uid) {
            try {
                $tous = \src\DAO\SeanceDAO::getInstance()->findAll(500);
                $deja = array_column($seances, 'id');
                try {
                    $st2 = \src\DAO\ConnectionPool::getConnection()->prepare('SELECT seance_id FROM sequence_seances WHERE sequence_id=:sid');
                    $st2->execute(['sid' => $id]);
                    $deja = array_unique(array_merge($deja, array_column($st2->fetchAll(), 'seance_id')));
                } catch (\Exception $e) {
                }
                $seancesDisponibles = array_values(array_filter($tous, fn($s2) => !in_array($s2['id'], $deja)));
            } catch (\Exception $e) {
            }
        }
        view('sequence/show', ['sequence' => $seq, 'seances' => $seances, 'situationsBySeance' => $sitsBySeance,
            'programmeItems' => $progItems, 'isOwner' => $uid && $seq['user_id'] === $uid, 'seancesDisponibles' => $seancesDisponibles]);
    })(),

    preg_match('#^/sequence/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        requireLogin();
        $id = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        $uid = currentUserId();

        $canEdit = $seq
            && (
                $seq['user_id'] === $uid
                || \src\DAO\CollaborateurDAO::getInstance()->canEdit($id, $uid)
            );

        if (!$canEdit) {
            http_response_code(403);
            view('errors/403');
            return;
        }
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('sequence/form', ['sequence' => $seq, 'cycles' => $dao->getCycles(), 'matieres' => $dao->getMatieres()]);
    })(),

    preg_match('#^/sequence/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $id = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        $uid = currentUserId();

        $canEdit = $seq
            && (
                $seq['user_id'] === $uid
                || \src\DAO\CollaborateurDAO::getInstance()->canEdit($id, $uid)
            );

        if (!$canEdit) {
            http_response_code(403);
            return;
        }
        $data = $_POST;
        $data['cycle_id'] = nullInt($_POST['cycle_id'] ?? null);
        $data['classe_id'] = nullInt($_POST['classe_id'] ?? null);
        $data['matiere_id'] = nullInt($_POST['matiere_id'] ?? null);
        $data['programme_version_id'] = nullInt($_POST['programme_version_id'] ?? null);
        $data['nb_seances'] = nullInt($_POST['nb_seances'] ?? 1) ?? 1;
        $data['programme_items'] = array_map('intval', $_POST['programme_items'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_sequence'] ?? []);
        \src\DAO\SequenceDAO::getInstance()->update($id, $data);
        flash('success', 'Séquence mise à jour.');
        redirect(url('sequence/') . $id);
    })(),

    preg_match('#^/sequence/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $id  = (int)$m[1];
        $uid = currentUserId();

        // Seul le propriétaire peut supprimer
        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($id, $uid)) {
            http_response_code(403);
            return;
        }

        // Gérer le transfert de propriété si des collaborateurs existent
        $newOwnerId = \src\DAO\CollaborateurDAO::getInstance()->handleOwnerDeletion($id, $uid);

        if ($newOwnerId) {
            // La séquence a été transférée, pas supprimée
            flash('success', 'La séquence a été transférée au premier collaborateur. Elle n\'a pas été supprimée.');
            redirect(url('dashboard'));
        } else {
            // Suppression normale (aucun collaborateur)
            \src\DAO\SequenceDAO::getInstance()->delete($id, $uid);
            flash('success', 'Séquence supprimée.');
            redirect(url('sequence/list'));
        }
    })(),

    preg_match('#^/sequence/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $id = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq) {
            http_response_code(404);
            return;
        }
        $uid = currentUserId();
        $canSee = $seq['is_public']
       || $seq['user_id'] === $uid
       || ($uid && \src\DAO\CollaborateurDAO::getInstance()->canEdit($id, $uid));

if (!$canSee) {
            http_response_code(403);
            return;
        }
        $seances = \src\DAO\SeanceDAO::getInstance()->findBySequence($id);
        $pdf = (new \src\Service\PdfService())->exportSequence($seq, $seances);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sequence-' . $id . '.pdf"');
        echo $pdf;
    })(),

    preg_match('#^/sequence/(\d+)/fork$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $newId = \src\DAO\SequenceDAO::getInstance()->fork((int)$m[1], currentUserId());
        flash('success', 'Séquence dupliquée !');
        redirect(url('sequence/') . $newId);
    })(),

    // ── SÉANCES ───────────────────────────────────────────────
    $uri === '/seance/list' && $method === 'GET' => (function () {
        requireLogin();
        $st = \src\DAO\ConnectionPool::getConnection()->prepare('SELECT s.*,seq.titre as sequence_titre,(SELECT COUNT(*) FROM situations WHERE seance_id=s.id) as nb_situations FROM seances s LEFT JOIN sequences seq ON s.sequence_id=seq.id ORDER BY s.updated_at DESC LIMIT 200');
        $st->execute();
        $seances = $st->fetchAll();
        foreach ($seances as &$r) {
            $r['deroulement'] = json_decode($r['deroulement'] ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        view('seance/list', ['seances' => $seances]);
    })(),

    $uri === '/seance/create' && $method === 'GET' => (function () {
        requireLogin();
        $seqId = ($_GET['sequence_id'] ?? '') !== '' ? (int)$_GET['sequence_id'] : null;
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('seance/form', ['seance' => [], 'sequence_id' => $seqId, 'sequence' => $seqId ? \src\DAO\SequenceDAO::getInstance()->findById($seqId) : null, 'cycles' => $dao->getCycles(), 'matieres' => $dao->getMatieres()]);
    })(),

    $uri === '/seance/create' && $method === 'POST' => (function () {
        requireLogin();
        $seqId = ($_POST['sequence_id'] ?? '') !== '' ? (int)$_POST['sequence_id'] : null;
        if ($seqId && !\src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }
        $data = $_POST;
        $data['duree'] = nullInt($_POST['duree'] ?? null);
        $data['deroulement'] = array_values($_POST['deroulement'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_seance'] ?? []);
        $id = \src\DAO\SeanceDAO::getInstance()->create($seqId, $data);
        flash('success', 'Séance créée !');
        if ($seqId) redirect(url('sequence/') . $seqId); else redirect(url('seance/') . $id . '/show');
    })(),

    preg_match('#^/seance/(\d+)/show$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $sequence = $fromSeqId ? \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId) : ($seance['sequence_id'] ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null);
        $situations = \src\DAO\SituationDAO::getInstance()->findBySeance((int)$m[1]);
        $mesSequences = [];
        if (\src\Service\AuthService::isLoggedIn() && empty($seance['sequence_id'])) $mesSequences = \src\DAO\SequenceDAO::getInstance()->findByUser(currentUserId());
        view('seance/show', ['seance' => $seance, 'sequence' => $sequence, 'fromSeqId' => $fromSeqId, 'situations' => $situations, 'mesSequences' => $mesSequences]);
    })(),

    preg_match('#^/seance/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) {
            http_response_code(404);
            return;
        }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seqContext = $fromSeqId ? \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId) : ($seance['sequence_id'] ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null);
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('seance/form', ['seance' => $seance, 'sequence_id' => $seqContext['id'] ?? $seance['sequence_id'], 'sequence' => $seqContext, 'fromSeqId' => $fromSeqId, 'cycles' => $dao->getCycles(), 'matieres' => $dao->getMatieres()]);
    })(),

    preg_match('#^/seance/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) {
            http_response_code(404);
            return;
        }
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) {
            http_response_code(403);
            return;
        }
        $data = $_POST;
        $data['duree'] = nullInt($_POST['duree'] ?? null);
        $data['deroulement'] = array_values($_POST['deroulement'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_seance'] ?? []);
        \src\DAO\SeanceDAO::getInstance()->update((int)$m[1], $data);
        flash('success', 'Séance mise à jour.');
        redirect(url('sequence/') . $seance['sequence_id']);
    })(),

    preg_match('#^/seance/(\d+)/attach$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)($_POST['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            \src\DAO\SeanceDAO::getInstance()->linkToSequence((int)$m[1], $seqId);
            flash('success', 'Séance ajoutée !');
            redirect(url('sequence/') . $seqId);
        } else {
            flash('error', 'Impossible.');
            redirect(url('seance/') . $m[1] . '/show');
        }
    })(),

    preg_match('#^/sequence/(\d+)/add-seance$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];
        $seanceId = (int)($_POST['seance_id'] ?? 0);
        if (!\src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }
        if ($seanceId) {
            try {
                \src\DAO\ConnectionPool::getConnection()->exec('CREATE TABLE IF NOT EXISTS sequence_seances(sequence_id INTEGER NOT NULL REFERENCES sequences(id) ON DELETE CASCADE,seance_id INTEGER NOT NULL REFERENCES seances(id) ON DELETE CASCADE,numero INTEGER NOT NULL DEFAULT 1,PRIMARY KEY(sequence_id,seance_id))');
                $c = \src\DAO\ConnectionPool::getConnection()->query('SELECT COUNT(*) FROM sequence_seances')->fetchColumn();
                if ((int)$c === 0) \src\DAO\ConnectionPool::getConnection()->exec('INSERT INTO sequence_seances(sequence_id,seance_id,numero) SELECT sequence_id,id,numero FROM seances WHERE sequence_id IS NOT NULL ON CONFLICT DO NOTHING');
            } catch (\Exception $e) {
            }
            \src\DAO\SeanceDAO::getInstance()->linkToSequence($seanceId, $seqId);
            flash('success', 'Séance ajoutée !');
        } else flash('error', 'Sélectionnez une séance.');
        redirect(url('sequence/') . $seqId);
    })(),

    preg_match('#^/seance/(\d+)/detach$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        \src\DAO\SeanceDAO::getInstance()->detachFromSequence((int)$m[1]);
        flash('success', 'Séance rendue autonome.');
        redirect(url('seance/') . $m[1] . '/show');
    })(),

    preg_match('#^/seance/(\d+)/position$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)($_POST['sequence_id'] ?? 0);
        $pos = max(1, (int)($_POST['position'] ?? 1));
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) \src\DAO\SeanceDAO::getInstance()->setPositionInSequence((int)$m[1], $seqId, $pos);
        redirect(url('sequence/') . $seqId);
    })(),

    preg_match('#^/seance/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        $seqId = $seance['sequence_id'] ?? null;
        if ($seance) {
            if ($seqId) {
                $seq = \src\DAO\SequenceDAO::getInstance()->findById($seqId);
                if ($seq && $seq['user_id'] === currentUserId()) \src\DAO\SeanceDAO::getInstance()->delete((int)$m[1]);
            } else \src\DAO\SeanceDAO::getInstance()->delete((int)$m[1]);
        }
        flash('success', 'Séance supprimée.');
        if ($seqId) redirect(url('sequence/') . $seqId); else redirect(url('seance/list'));
    })(),

    preg_match('#^/seance/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) {
            http_response_code(404);
            return;
        }
        $pdf = (new \src\Service\PdfService())->exportSeance($seance, \src\DAO\SituationDAO::getInstance()->findBySeance((int)$m[1]));
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="seance-' . $m[1] . '.pdf"');
        echo $pdf;
    })(),

    $uri === '/seance/reorder' && $method === 'POST' => (function () {
        requireLogin();
        $body = json_decode(file_get_contents('php://input'), true);
        $seqId = (int)($body['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) \src\DAO\SeanceDAO::getInstance()->reorder($seqId, $body['order'] ?? []);
        jsonResponse(['ok' => true]);
    })(),

    // ── SITUATIONS ────────────────────────────────────────────
    $uri === '/situation/create' && $method === 'GET' => (function () {
        requireLogin();
        view('situation/form', ['situation' => [], 'seance_id' => (int)($_GET['seance_id'] ?? 0)]);
    })(),

    $uri === '/situation/create' && $method === 'POST' => (function () {
        requireLogin();
        $seanceId = (int)($_POST['seance_id'] ?? 0);
        $seance = \src\DAO\SeanceDAO::getInstance()->findById($seanceId);
        if (!$seance) {
            http_response_code(404);
            return;
        }
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) {
            http_response_code(403);
            return;
        }
        $data = $_POST;
        $data['duree'] = nullInt($_POST['duree'] ?? null);
        $data['variables_evolution'] = array_values($_POST['variables_evolution'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_situation'] ?? []);
        \src\DAO\SituationDAO::getInstance()->create($seanceId, $data);
        flash('success', 'Situation créée !');
        redirect(url('sequence/') . $seq['id']);
    })(),

    preg_match('#^/situation/(\d+)/show$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seance = $sit['seance_id'] ? \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']) : null;
        $sequence = $fromSeqId ? \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId) : (($seance && $seance['sequence_id']) ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null);
        view('situation/show', ['situation' => $sit, 'seance' => $seance, 'sequence' => $sequence, 'fromSeqId' => $fromSeqId]);
    })(),

    preg_match('#^/situation/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        requireLogin();
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) {
            http_response_code(404);
            return;
        }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seance = $sit['seance_id'] ? \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']) : null;
        view('situation/form', ['situation' => $sit, 'seance_id' => $sit['seance_id'], 'seance' => $seance, 'fromSeqId' => $fromSeqId]);
    })(),

    preg_match('#^/situation/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) {
            http_response_code(404);
            return;
        }
        $seance = \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']);
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) {
            http_response_code(403);
            return;
        }
        $data = $_POST;
        $data['duree'] = nullInt($_POST['duree'] ?? null);
        $data['variables_evolution'] = array_values($_POST['variables_evolution'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_situation'] ?? []);
        \src\DAO\SituationDAO::getInstance()->update((int)$m[1], $data);
        flash('success', 'Situation mise à jour.');
        redirect(url('sequence/') . $seq['id']);
    })(),

    preg_match('#^/situation/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        $seanceId = null;
        if ($sit) {
            $seanceId = $sit['seance_id'];
            $seance = $seanceId ? \src\DAO\SeanceDAO::getInstance()->findById($seanceId) : null;
            $can = false;
            if ($seance && $seance['sequence_id']) {
                $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
                $can = $seq && $seq['user_id'] === currentUserId();
            } else $can = true;
            if ($can) \src\DAO\SituationDAO::getInstance()->delete((int)$m[1]);
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['ok' => true]);
        flash('success', 'Situation supprimée.');
        if ($seanceId) redirect(url('seance/') . $seanceId . '/show'); else redirect(url('dashboard'));
    })(),

    preg_match('#^/situation/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) {
            http_response_code(404);
            return;
        }
        $pdf = (new \src\Service\PdfService())->exportSituation($sit);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="situation-' . $m[1] . '.pdf"');
        echo $pdf;
    })(),

    // ── PAGES PUBLIQUES ───────────────────────────────────────
    $uri === '/explorer' && $method === 'GET' => (function () {
        $filters = ['cycle_id' => $_GET['cycle_id'] ?? '', 'classe_id' => $_GET['classe_id'] ?? '', 'matiere_id' => $_GET['matiere_id'] ?? '', 'search' => $_GET['q'] ?? ''];
        $sequences = \src\DAO\SequenceDAO::getInstance()->findPublic(array_filter($filters));
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('explorer', ['sequences' => $sequences, 'cycles' => $dao->getCycles(), 'matieres' => $dao->getMatieres(), 'filters' => $filters]);
    })(),

    $uri === '/programmes' && $method === 'GET' => (function () {
        view('programmes', ['versions' => \src\DAO\ProgrammeDAO::getInstance()->getAllVersionsGrouped()]);
    })(),

    $uri === '/profil' && $method === 'GET' => (function () {
        requireLogin();
        view('profil', ['user' => \src\DAO\UserDAO::getInstance()->findById(currentUserId())]);
    })(),

    $uri === '/profil' && $method === 'POST' => (function () {
        requireLogin();
        \src\DAO\UserDAO::getInstance()->updateProfile(currentUserId(), trim($_POST['nom'] ?? ''), trim($_POST['prenom'] ?? ''));
        flash('success', 'Profil mis à jour.');
        redirect(url('profil'));
    })(),

    $uri === '/profil/password' && $method === 'POST' => (function () {
        requireLogin();
        $uid = currentUserId();
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if ($new !== $conf) {
            flash('error', 'Mots de passe différents.');
            redirect(url('profil'));
        }
        if (strlen($new) < 8) {
            flash('error', 'Trop court (8 car. min).');
            redirect(url('profil'));
        }
        flash(\src\DAO\UserDAO::getInstance()->changePassword($uid, $old, $new) ? 'success' : 'error', \src\DAO\UserDAO::getInstance()->changePassword($uid, $old, $new) ? 'Mot de passe mis à jour.' : 'Mot de passe actuel incorrect.');
        redirect(url('profil'));
    })(),

    $uri === '/api/seances/reorder' && $method === 'POST' => (function () {
        requireLogin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $seqId = (int)($_GET['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) \src\DAO\SeanceDAO::getInstance()->reorder($seqId, $body['ids'] ?? []);
        jsonResponse(['ok' => true]);
    })(),

    $uri === '/pdf/check' && $method === 'GET' => (function () {
        requireLogin();
        $errors = \src\Service\PdfService::checkDependencies();
        echo empty($errors) ? '<div style="padding:20px;background:#dcfce7;color:#14532d">✅ OK</div>' : '<div style="padding:20px;background:#fee2e2;color:#7f1d1d">❌ ' . implode('<br>', array_map('htmlspecialchars', $errors)) . '</div>';
    })(),

    // ════════════════════════════════════════════════════════════
    //  ADMIN PROGRAMMES — routes dans l'ordre EXACT > REGEX
    //
    //  Hiérarchie des routes :
    //  1. Routes exactes (===) : /create, /update, /delete en dernier
    //  2. Routes regex (preg_match) après les routes exactes du même préfixe
    // ════════════════════════════════════════════════════════════

    // Page admin
    $uri === '/admin/programmes' && $method === 'GET' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $dao = \src\DAO\ProgrammeDAO::getInstance();
            view('admin/programmes/index', [
                'programmes' => $dao->getProgrammesAdmin(),
                'cycles' => $dao->getCycles(),
                'classes' => $dao->getAllClasses(),
            ]);
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // Éditeur arbre items
    preg_match('#^/admin/programmes/version/(\d+)$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        requireLogin();
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            view('admin/programmes/edit_tree', ['version' => $dao->getVersionById((int)$m[1]), 'tree' => $dao->getItemsTree((int)$m[1])]);
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // ── API PROGRAMMES ── exact avant regex ───────────────────
    $uri === '/api/admin/programmes/create' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['annee_entree']) || empty($data['cycle_id'])) {
                jsonResponse(['ok' => false, 'error' => 'annee_entree et cycle_id sont obligatoires'], 400);
            }
            try {
                $id = \src\DAO\ProgrammeDAO::getInstance()->createProgramme(
                    (int)$data['annee_entree'], (int)$data['cycle_id'], $data['source_url'] ?? null
                );
                jsonResponse(['ok' => true, 'id' => $id]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/programmes/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['annee_entree']) || empty($data['cycle_id'])) {
                jsonResponse(['ok' => false, 'error' => 'annee_entree et cycle_id obligatoires'], 400);
            }
            try {
                \src\DAO\ProgrammeDAO::getInstance()->updateProgramme(
                    (int)$m[1], (int)$data['annee_entree'], (int)$data['cycle_id'], $data['source_url'] ?? null
                );
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/programmes/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            try {
                \src\DAO\ProgrammeDAO::getInstance()->deleteProgramme((int)$m[1]);
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // ── API MATIÈRES ── exact avant regex ─────────────────────
    $uri === '/api/admin/matieres/create' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['label']) || empty($data['code'])) {
                jsonResponse(['ok' => false, 'error' => 'label et code obligatoires'], 400);
            }
            try {
                $matiereId = \src\DAO\ProgrammeDAO::getInstance()->createMatiere($data['label'], $data['code']);
                // Lier au programme si programme_id fourni
                $pmId = null;
                if (!empty($data['programme_id'])) {
                    $pmId = \src\DAO\ProgrammeDAO::getInstance()->addMatiereToProgamme((int)$data['programme_id'], $matiereId);
                }
                jsonResponse(['ok' => true, 'matiere_id' => $matiereId, 'programme_matiere_id' => $pmId]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/matieres/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            try {
                \src\DAO\ProgrammeDAO::getInstance()->updateMatiere((int)$m[1], $data['label'] ?? '', $data['code'] ?? '');
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/matieres/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            try {
                \src\DAO\ProgrammeDAO::getInstance()->deleteMatiere((int)$m[1]);
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // PATCH inline matière
    $uri === '/api/admin/matieres' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['id'])) {
                jsonResponse(['ok' => false, 'error' => 'id manquant'], 400);
            }
            $id = (int)$data['id'];
            unset($data['id']);
            \src\DAO\ProgrammeDAO::getInstance()->patchMatiere($id, $data);
            jsonResponse(['ok' => true]);
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // ── API PROGRAMME_MATIERES ── exact avant regex ────────────
    preg_match('#^/api/admin/programme-matieres/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            try {
                \src\DAO\ProgrammeDAO::getInstance()->removeMatiereFromProgramme((int)$m[1]);
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // ── API PROGRAMME_VERSIONS ── exact avant regex ───────────
    $uri === '/api/admin/programme-versions/create' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['label']) || empty($data['annee_entree'])) {
                jsonResponse(['ok' => false, 'error' => 'label et annee_entree obligatoires'], 400);
            }
            if (empty($data['programme_matiere_id']) && (empty($data['programme_id']) || empty($data['matiere_id']))) {
                jsonResponse(['ok' => false, 'error' => 'programme_matiere_id ou (programme_id + matiere_id) requis'], 400);
            }
            try {
                $id = \src\DAO\ProgrammeDAO::getInstance()->createVersion($data);
                jsonResponse(['ok' => true, 'id' => $id]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // PATCH inline version
    $uri === '/api/admin/programme-versions' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['id'])) {
                jsonResponse(['ok' => false, 'error' => 'id manquant'], 400);
            }
            $id = (int)$data['id'];
            unset($data['id']);
            try {
                \src\DAO\ProgrammeDAO::getInstance()->patchVersion($id, $data);
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/programme-versions/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            try {
                \src\DAO\ProgrammeDAO::getInstance()->deleteVersion((int)$m[1]);
                jsonResponse(['ok' => true]);
            } catch (\Exception $e) {
                jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    // ── API ITEMS PROGRAMME ───────────────────────────────────
    $uri === '/api/admin/programme-items' && $method === 'POST' => (function () {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = \src\DAO\ProgrammeDAO::getInstance()->saveItem($data);
            jsonResponse(['ok' => true, 'id' => $id]);
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),

    preg_match('#^/api/admin/programme-items/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        if (\src\DAO\UserDAO::getInstance()->isAdmin(currentUserId())) {
            \src\DAO\ProgrammeDAO::getInstance()->deleteItem((int)$m[1]);
            jsonResponse(['ok' => true]);
        } else {
            http_response_code(403);
            view('errors/403');
        }

    })(),


    /**
     * ROUTES CO-ENSEIGNEMENT — à insérer dans index.php
     * dans le bloc match(true), après les routes existantes de séquences
     * et AVANT le default (404).
     *
     * Dépendance : src\DAO\CollaborateurDAO
     */

// ── INVITATION : afficher la page d'accueil de l'invitation ─────────
// GET /sequence/invitation/{token}
    preg_match('#^/sequence/invitation/([a-f0-9]{64})$#', $uri, $m) && $method === 'GET' => (function () use ($m) {
        $token = $m[1];
        $invitation = \src\DAO\CollaborateurDAO::getInstance()->findByToken($token);

        if (!$invitation) {
            flash('error', 'Ce lien d\'invitation est invalide ou a déjà été utilisé.');
            redirect(url(''));
        }

        view('sequence/invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'isLogged' => \src\Service\AuthService::isLoggedIn(),
        ]);
    })(),

// ── INVITATION : accepter (utilisateur connecté) ─────────────────────
// POST /sequence/invitation/{token}/accepter
    preg_match('#^/sequence/invitation/([a-f0-9]{64})/accepter$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        $token = $m[1];
        requireLogin();

        $ok = \src\DAO\CollaborateurDAO::getInstance()->acceptInvitation($token, currentUserId());

        if ($ok) {
            // Récupérer l'ID de la séquence pour rediriger
            $invitation = \src\DAO\CollaborateurDAO::getInstance()->findByToken($token);
            $seqId = $invitation['sequence_id'] ?? null;

            // Si le token est déjà consommé, chercher via l'entrée acceptée
            if (!$seqId) {
                $st = \src\DAO\ConnectionPool::getConnection()->prepare(
                    'SELECT sequence_id FROM sequence_collaborateurs WHERE user_id = :uid ORDER BY accepted_at DESC LIMIT 1'
                );
                $st->execute(['uid' => currentUserId()]);
                $seqId = $st->fetchColumn();
            }

            flash('success', 'Bienvenue dans l\'équipe ! Vous êtes maintenant collaborateur de cette séquence.');
            redirect($seqId ? url('sequence/') . $seqId : url('dashboard'));
        } else {
            flash('error', 'Impossible de rejoindre : le lien est invalide ou expiré.');
            redirect(url(''));
        }
    })(),

// ── INVITATION : générer un lien ─────────────────────────────────────
// POST /sequence/{id}/invitation/generer
    preg_match('#^/sequence/(\d+)/invitation/generer$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];

        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }

        // S'assurer que l'entrée propriétaire existe (migration)
        \src\DAO\CollaborateurDAO::getInstance()->ensureOwnerEntry($seqId, currentUserId());

        $token = \src\DAO\CollaborateurDAO::getInstance()->createInvitation($seqId, currentUserId());

        flash('success', 'Lien d\'invitation généré.');
        redirect(url('sequence/') . $seqId . '?invite_token=' . $token . '#section-collaborateurs');
    })(),

// ── INVITATION : régénérer (invalide l'ancien) ───────────────────────
// POST /sequence/{id}/invitation/regenerer
    preg_match('#^/sequence/(\d+)/invitation/regenerer$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];

        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }

        $token = \src\DAO\CollaborateurDAO::getInstance()->createInvitation($seqId, currentUserId());

        flash('success', 'Nouveau lien d\'invitation généré. L\'ancien est désormais invalide.');
        redirect(url('sequence/') . $seqId . '?invite_token=' . $token . '#section-collaborateurs');
    })(),

// ── COLLABORATEUR : retirer un membre ────────────────────────────────
// POST /sequence/{id}/collaborateur/{user_id}/retirer
    preg_match('#^/sequence/(\d+)/collaborateur/(\d+)/retirer$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];
        $userId = (int)$m[2];

        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }

        \src\DAO\CollaborateurDAO::getInstance()->removeCollaborateur($seqId, $userId);
        flash('success', 'Collaborateur retiré de la séquence.');
        redirect(url('sequence/') . $seqId . '#section-collaborateurs');
    })(),

// ── COLLABORATEUR : révoquer une invitation en attente ───────────────
// POST /sequence/{id}/collaborateur/{collab_row_id}/revoquer
    preg_match('#^/sequence/(\d+)/collaborateur/(\d+)/revoquer$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];
        $collabRowId = (int)$m[2];

        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }

        \src\DAO\CollaborateurDAO::getInstance()->revokeInvitation($collabRowId);
        flash('success', 'Invitation annulée.');
        redirect(url('sequence/') . $seqId . '#section-collaborateurs');
    })(),

// ── COLLABORATEUR : transférer la propriété ──────────────────────────
// POST /sequence/{id}/collaborateur/{user_id}/transferer
    preg_match('#^/sequence/(\d+)/collaborateur/(\d+)/transferer$#', $uri, $m) && $method === 'POST' => (function () use ($m) {
        requireLogin();
        $seqId = (int)$m[1];
        $newOwnerId = (int)$m[2];

        if (!\src\DAO\CollaborateurDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403);
            return;
        }

        try {
            $ok = \src\DAO\CollaborateurDAO::getInstance()->transferOwnership($seqId, $newOwnerId, currentUserId());
            if ($ok) {
                flash('success', 'Propriété transférée avec succès. Vous êtes maintenant collaborateur.');
            } else {
                flash('error', 'Transfert impossible : cet utilisateur n\'est pas encore collaborateur accepté.');
            }
        } catch (\Throwable $e) {
            flash('error', 'Erreur lors du transfert : ' . $e->getMessage());
        }

        redirect(url('sequence/') . $seqId . '#section-collaborateurs');
    })(),

    // ── 404 ───────────────────────────────────────────────────
    default => (function () {
        http_response_code(404);
        view('errors/404');
    })(),
};
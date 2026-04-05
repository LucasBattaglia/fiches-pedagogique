<?php
// index.php - Routeur principal

declare(strict_types=1);

// ── Chargement .env (DOIT être en tout premier) ──────────
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#') continue;
        if (strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        putenv("$_k=$_v");
        $_ENV[$_k]    = $_v;
        $_SERVER[$_k] = $_v;
    }
}
unset($_envFile, $_line, $_k, $_v);
// ─────────────────────────────────────────────────────────

session_set_cookie_params(['samesite' => 'Lax', 'httponly' => true]);
session_start();

require_once __DIR__ . '/vendor/autoload.php';

// ---- Config ----
$config = require __DIR__ . '/config/config.php';
\src\DAO\ConnectionPool::init($config['db']);

// ---- Helpers ----
function view(string $template, array $vars = []): void {
    extract($vars);
    require __DIR__ . '/resources/views/' . ltrim($template, '/') . '.php';
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][$type][] = $message;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function currentUserId(): ?int {
    return \src\Service\AuthService::currentUserId();
}

function requireLogin(): void {
    \src\Service\AuthService::requireLogin($_SERVER['REQUEST_URI'] ?? '/');
}

function nullInt(mixed $v): ?int {
    return ($v === null || $v === '') ? null : (int)$v;
}

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Routing ----
$method   = $_SERVER['REQUEST_METHOD'];
$uri      = strtok($_SERVER['REQUEST_URI'], '?');
$uri      = rtrim($uri, '/') ?: '/';

// Supporte installation dans un sous-dossier WAMP (ex: /fiches-pedagogiques/)
// Détection automatique du sous-dossier à partir du script courant
// Détection du sous-dossier (vide si VirtualHost, sinon /fiches-pedagogiques par ex.)
// Avec un VirtualHost, SCRIPT_NAME = /index.php donc dirname = '/'
$_BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($_BASE === '.' || $_BASE === '\\') $_BASE = '';
if ($_BASE !== '' && str_starts_with($uri, $_BASE)) {
    $uri = substr($uri, strlen($_BASE)) ?: '/';
}
$uri = $uri ?: '/';

// Définir la fonction url() pour générer des URLs avec le bon préfixe
function url(string $path = ''): string {
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
// ============================================================

match(true) {

    // ---- ACCUEIL ----
    $uri === '/' && $method === 'GET' => (function() {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        view('home');
    })(),

    // ---- DASHBOARD ----
    $uri === '/dashboard' && $method === 'GET' => (function() {
        requireLogin();
        $uid  = currentUserId();
        $seqs = \src\DAO\SequenceDAO::getInstance()->findByUser($uid, 10);
        $pub  = \src\DAO\SequenceDAO::getInstance()->findPublic([], 6);
        view('dashboard', ['sequences' => $seqs, 'publiques' => $pub]);
    })(),

    // ---- API : CLASSES ----
    $uri === '/api/classes' && $method === 'GET' => (function() {
        $cid = (int)($_GET['cycle_id'] ?? 0);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getClassesByCycle($cid));
    })(),

    // ---- API : MATIERES (filtrées par cycle + classe) ----
    $uri === '/api/matieres' && $method === 'GET' => (function() {
        $cid  = ($_GET['cycle_id']  ?? '') !== '' ? (int)$_GET['cycle_id']  : null;
        $clid = ($_GET['classe_id'] ?? '') !== '' ? (int)$_GET['classe_id'] : null;
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getMatieresByCycleClasse($cid, $clid));
    })(),

    // ---- API : PROGRAMME VERSIONS ----
    $uri === '/api/programme-versions' && $method === 'GET' => (function() {
        $cid  = (int)($_GET['cycle_id']  ?? 0);
        $clid = ($_GET['classe_id'] ?? '') !== '' ? (int)$_GET['classe_id'] : null;
        $mid  = (int)($_GET['matiere_id'] ?? 0);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getVersions($cid, $clid, $mid));
    })(),

    // ---- API : ITEMS PROGRAMME ----
    $uri === '/api/programme-items' && $method === 'GET' => (function() {
        $vid = (int)($_GET['version_id'] ?? 0);
        if (!$vid) jsonResponse([]);
        jsonResponse(\src\DAO\ProgrammeDAO::getInstance()->getItemsFlat($vid));
    })(),

    // ---- AUTH : LOGIN ----
    $uri === '/auth/login' && $method === 'GET' => (function() use ($config) {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        $googleUrl = !empty($config['oauth']['google']['client_id'])
            ? \src\Service\AuthService::getGoogleAuthUrl($config['oauth']['google'])
            : null;
        view('auth/login', ['googleUrl' => $googleUrl, 'errors' => []]);
    })(),

    $uri === '/auth/login' && $method === 'POST' => (function() use ($config) {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors   = [];
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

    // ---- AUTH : REGISTER ----
    $uri === '/auth/register' && $method === 'GET' => (function() {
        if (\src\Service\AuthService::isLoggedIn()) redirect(url('dashboard'));
        view('auth/register', ['errors' => []]);
    })(),

    $uri === '/auth/register' && $method === 'POST' => (function() {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $nom      = trim($_POST['nom'] ?? '');
        $prenom   = trim($_POST['prenom'] ?? '');
        $errors   = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
        if (strlen($password) < 8) $errors['password'] = 'Mot de passe trop court (8 caractères min).';
        if (empty($nom))    $errors['nom']    = 'Nom requis.';
        if (empty($prenom)) $errors['prenom'] = 'Prénom requis.';
        if (!$errors && \src\DAO\UserDAO::getInstance()->emailExists($email)) $errors['email'] = 'Email déjà utilisé.';
        if (!$errors) {
            $id   = \src\DAO\UserDAO::getInstance()->create($email, $password, $nom, $prenom);
            $user = \src\DAO\UserDAO::getInstance()->findById($id);
            \src\Service\AuthService::login($user);
            flash('success', 'Bienvenue ' . $prenom . ' !');
            redirect(url('dashboard'));
        }
        view('auth/register', ['errors' => $errors]);
    })(),

    // ---- AUTH : GOOGLE CALLBACK ----
    $uri === '/auth/google/callback' && $method === 'GET' => (function() use ($config) {
        $code  = $_GET['code'] ?? '';
        if (!$code) redirect(url('auth/login'));
        $user = \src\Service\AuthService::handleGoogleCallback($config['oauth']['google'], $code);
        if (!$user) { flash('error', 'Erreur Google OAuth.'); redirect(url('auth/login')); }
        \src\Service\AuthService::login($user);
        redirect(url('dashboard'));
    })(),

    // ---- AUTH : LOGOUT ----
    $uri === '/auth/logout' => (function() {
        \src\Service\AuthService::logout();
        redirect(url(''));
    })(),

    // ---- SÉQUENCES ----

    $uri === '/sequence/list' && $method === 'GET' => (function() {
        requireLogin();
        $sequences = \src\DAO\SequenceDAO::getInstance()->findByUser(currentUserId());
        view('sequence/index', ['sequences' => $sequences]);
    })(),

    $uri === '/sequence/create' && $method === 'GET' => (function() {
        requireLogin();
        $dao      = \src\DAO\ProgrammeDAO::getInstance();
        view('sequence/form', [
            'sequence' => [],
            'cycles'   => $dao->getCycles(),
            'matieres' => $dao->getMatieres(),
        ]);
    })(),

    $uri === '/sequence/create' && $method === 'POST' => (function() {
        requireLogin();
        $data = $_POST;
        // Convertir les champs integer : '' → null pour PostgreSQL
        $data['cycle_id']              = nullInt($_POST['cycle_id']              ?? null);
        $data['classe_id']             = nullInt($_POST['classe_id']             ?? null);
        $data['matiere_id']            = nullInt($_POST['matiere_id']            ?? null);
        $data['programme_version_id']  = nullInt($_POST['programme_version_id'] ?? null);
        $data['nb_seances']            = nullInt($_POST['nb_seances']            ?? 1) ?? 1;
        $data['programme_items']       = array_map('intval', $_POST['programme_items'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_sequence'] ?? []);
        try {
            $id = \src\DAO\SequenceDAO::getInstance()->create(currentUserId(), $data);
            flash('success', 'Séquence créée avec succès !');
            redirect(url('sequence/') . $id);
        } catch (\Exception $e) {
            flash('error', 'Erreur : ' . $e->getMessage());
            redirect(url('sequence/create'));
        }
    })(),

    preg_match('#^/sequence/(\d+)$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $id  = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq) { http_response_code(404); view('errors/404'); return; }
        $uid = currentUserId();
        if (!$seq['is_public'] && $seq['user_id'] !== $uid) { http_response_code(403); view('errors/403'); return; }
        $seances = \src\DAO\SeanceDAO::getInstance()->findBySequence($id);
        $sitsBySeance = [];
        foreach ($seances as $s) {
            $sitsBySeance[$s['id']] = \src\DAO\SituationDAO::getInstance()->findBySeance($s['id']);
        }
        // Items programme liés
        $progItems = [];
        if (!empty($seq['programme_items'])) {
            $allItems = \src\DAO\ProgrammeDAO::getInstance()->getItemsFlat($seq['programme_version_id'] ?? 0);
            $ids = $seq['programme_items'];
            $progItems = array_filter($allItems, fn($i) => in_array($i['id'], $ids));
        }
        // Toutes les séances disponibles pour le modal "ajouter séance existante"
        $seancesDisponibles = [];
        if ($uid) {
            try {
                $toutesSeances = \src\DAO\SeanceDAO::getInstance()->findAll(500);
                $seanceIdsDejaLiees = array_column($seances, 'id');
                // Exclure aussi celles liées via la table N-N si elle existe
                try {
                    $st2 = \src\DAO\ConnectionPool::getConnection()->prepare(
                        'SELECT seance_id FROM sequence_seances WHERE sequence_id = :sid'
                    );
                    $st2->execute(['sid' => $id]);
                    $liees2 = array_column($st2->fetchAll(), 'seance_id');
                    $seanceIdsDejaLiees = array_unique(array_merge($seanceIdsDejaLiees, $liees2));
                } catch (\Exception $e) { /* table N-N pas encore créée */ }
                $seancesDisponibles = array_values(array_filter(
                    $toutesSeances,
                    fn($s2) => !in_array($s2['id'], $seanceIdsDejaLiees)
                ));
            } catch (\Exception $e) { $seancesDisponibles = []; }
        }
        view('sequence/show', [
            'sequence'          => $seq,
            'seances'           => $seances,
            'situationsBySeance'=> $sitsBySeance,
            'programmeItems'    => $progItems,
            'isOwner'           => $uid && $seq['user_id'] === $uid,
            'seancesDisponibles'=> array_values($seancesDisponibles),
        ]);
    })(),

    preg_match('#^/sequence/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        requireLogin();
        $id  = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq || $seq['user_id'] !== currentUserId()) { http_response_code(403); view('errors/403'); return; }
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('sequence/form', [
            'sequence' => $seq,
            'cycles'   => $dao->getCycles(),
            'matieres' => $dao->getMatieres(),
        ]);
    })(),

    preg_match('#^/sequence/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $id = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq || $seq['user_id'] !== currentUserId()) { http_response_code(403); return; }
        $data = $_POST;
        $data['cycle_id']              = nullInt($_POST['cycle_id']              ?? null);
        $data['classe_id']             = nullInt($_POST['classe_id']             ?? null);
        $data['matiere_id']            = nullInt($_POST['matiere_id']            ?? null);
        $data['programme_version_id']  = nullInt($_POST['programme_version_id'] ?? null);
        $data['nb_seances']            = nullInt($_POST['nb_seances']            ?? 1) ?? 1;
        $data['programme_items']       = array_map('intval', $_POST['programme_items'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_sequence'] ?? []);
        \src\DAO\SequenceDAO::getInstance()->update($id, $data);
        flash('success', 'Séquence mise à jour.');
        redirect(url('sequence/') . $id);
    })(),

    preg_match('#^/sequence/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        \src\DAO\SequenceDAO::getInstance()->delete((int)$m[1], currentUserId());
        flash('success', 'Séquence supprimée.');
        redirect(url('sequence/list'));
    })(),

    preg_match('#^/sequence/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $id  = (int)$m[1];
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($id);
        if (!$seq) { http_response_code(404); return; }
        $uid = currentUserId();
        if (!$seq['is_public'] && $seq['user_id'] !== $uid) { http_response_code(403); return; }
        $seances = \src\DAO\SeanceDAO::getInstance()->findBySequence($id);
        $pdf = (new \src\Service\PdfService())->exportSequence($seq, $seances);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sequence-' . $id . '.pdf"');
        echo $pdf;
    })(),

    preg_match('#^/sequence/(\d+)/fork$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $newId = \src\DAO\SequenceDAO::getInstance()->fork((int)$m[1], currentUserId());
        flash('success', 'Séquence dupliquée dans vos fiches !');
        redirect(url('sequence/') . $newId);
    })(),

    // ---- SÉANCES ----

    $uri === '/seance/list' && $method === 'GET' => (function() {
        requireLogin();
        $db   = \src\DAO\ConnectionPool::getConnection();
        $st   = $db->prepare('
            SELECT s.*, seq.titre as sequence_titre,
                   (SELECT COUNT(*) FROM situations WHERE seance_id = s.id) as nb_situations
            FROM   seances s
            LEFT JOIN sequences seq ON s.sequence_id = seq.id
            ORDER  BY s.updated_at DESC
            LIMIT 200
        ');
        $st->execute();
        $seances = $st->fetchAll();
        foreach ($seances as &$r) {
            $r['deroulement'] = json_decode($r['deroulement'] ?? '[]', true) ?? [];
            $r['comportements_remediations'] = json_decode($r['comportements_remediations'] ?? '[]', true) ?? [];
        }
        view('seance/list', ['seances' => $seances]);
    })(),

    $uri === '/seance/create' && $method === 'GET' => (function() {
        requireLogin();
        $seqId = ($_GET['sequence_id'] ?? '') !== '' ? (int)$_GET['sequence_id'] : null;
        $dao   = \src\DAO\ProgrammeDAO::getInstance();
        view('seance/form', [
            'seance'      => [],
            'sequence_id' => $seqId,
            'sequence'    => $seqId ? \src\DAO\SequenceDAO::getInstance()->findById($seqId) : null,
            'cycles'      => $dao->getCycles(),
            'matieres'    => $dao->getMatieres(),
        ]);
    })(),

    $uri === '/seance/create' && $method === 'POST' => (function() {
        requireLogin();
        $seqId = ($_POST['sequence_id'] ?? '') !== '' ? (int)$_POST['sequence_id'] : null;
        // Si séquence liée, vérifier ownership
        if ($seqId && !\src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403); return;
        }
        $data = $_POST;
        $data['duree']                     = nullInt($_POST['duree'] ?? null);
        $data['deroulement']               = array_values($_POST['deroulement'] ?? []);
        $data['comportements_remediations']= array_values($_POST['comportements_seance'] ?? []);
        $id = \src\DAO\SeanceDAO::getInstance()->create($seqId, $data);
        flash('success', 'Séance créée !');
        if ($seqId) redirect(url('sequence/') . $seqId);
        else        redirect(url('seance/') . $id . '/show');
    })(),

    preg_match('#^/seance/(\d+)/show$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) { http_response_code(404); view('errors/404'); return; }
        // from_seq = séquence depuis laquelle on arrive (contexte de navigation)
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        if ($fromSeqId) {
            $sequence = \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId);
        } else {
            $sequence = $seance['sequence_id'] ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null;
        }
        $situations = \src\DAO\SituationDAO::getInstance()->findBySeance((int)$m[1]);
        $mesSequences = [];
        if (\src\Service\AuthService::isLoggedIn() && empty($seance['sequence_id'])) {
            $mesSequences = \src\DAO\SequenceDAO::getInstance()->findByUser(currentUserId());
        }
        view('seance/show', [
            'seance'       => $seance,
            'sequence'     => $sequence,
            'fromSeqId'    => $fromSeqId,
            'situations'   => $situations,
            'mesSequences' => $mesSequences,
        ]);
    })(),

    preg_match('#^/seance/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) { http_response_code(404); return; }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seqContext = $fromSeqId
            ? \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId)
            : ($seance['sequence_id'] ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null);
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('seance/form', [
            'seance'      => $seance,
            'sequence_id' => $seqContext['id'] ?? $seance['sequence_id'],
            'sequence'    => $seqContext,
            'fromSeqId'   => $fromSeqId,
            'cycles'      => $dao->getCycles(),
            'matieres'    => $dao->getMatieres(),
        ]);
    })(),

    preg_match('#^/seance/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) { http_response_code(404); return; }
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) { http_response_code(403); return; }
        $data = $_POST;
        $data['duree']                     = nullInt($_POST['duree'] ?? null);
        $data['deroulement']               = array_values($_POST['deroulement'] ?? []);
        $data['comportements_remediations']= array_values($_POST['comportements_seance'] ?? []);
        \src\DAO\SeanceDAO::getInstance()->update((int)$m[1], $data);
        flash('success', 'Séance mise à jour.');
        redirect(url('sequence/') . $seance['sequence_id']);
    })(),

    preg_match('#^/seance/(\d+)/attach$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $seqId = (int)($_POST['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            \src\DAO\SeanceDAO::getInstance()->linkToSequence((int)$m[1], $seqId);
            flash('success', 'Séance ajoutée à la séquence !');
            redirect(url('sequence/') . $seqId);
        } else {
            flash('error', 'Impossible d\'ajouter la séance.');
            redirect(url('seance/') . $m[1] . '/show');
        }
    })(),

    // Ajouter une séance existante à une séquence (depuis la page séquence)
    preg_match('#^/sequence/(\d+)/add-seance$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $seqId    = (int)$m[1];
        $seanceId = (int)($_POST['seance_id'] ?? 0);
        if (!\src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            http_response_code(403); return;
        }
        if ($seanceId) {
            // Créer la table N-N si elle n'existe pas encore (migration auto)
            try {
                \src\DAO\ConnectionPool::getConnection()->exec('
                    CREATE TABLE IF NOT EXISTS sequence_seances (
                        sequence_id INTEGER NOT NULL REFERENCES sequences(id) ON DELETE CASCADE,
                        seance_id   INTEGER NOT NULL REFERENCES seances(id)   ON DELETE CASCADE,
                        numero      INTEGER NOT NULL DEFAULT 1,
                        PRIMARY KEY (sequence_id, seance_id)
                    )
                ');
                // Migrer les liens existants si table vide
                $count = \src\DAO\ConnectionPool::getConnection()
                    ->query('SELECT COUNT(*) FROM sequence_seances')->fetchColumn();
                if ((int)$count === 0) {
                    \src\DAO\ConnectionPool::getConnection()->exec('
                        INSERT INTO sequence_seances (sequence_id, seance_id, numero)
                        SELECT sequence_id, id, numero FROM seances
                        WHERE sequence_id IS NOT NULL
                        ON CONFLICT DO NOTHING
                    ');
                }
            } catch (\Exception $e) {}
            \src\DAO\SeanceDAO::getInstance()->linkToSequence($seanceId, $seqId);
            flash('success', 'Séance ajoutée à la séquence !');
        } else {
            flash('error', 'Veuillez sélectionner une séance.');
        }
        redirect(url('sequence/') . $seqId);
    })(),

    preg_match('#^/seance/(\d+)/detach$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        \src\DAO\SeanceDAO::getInstance()->detachFromSequence((int)$m[1]);
        flash('success', 'Séance rendue autonome.');
        redirect(url('seance/') . $m[1] . '/show');
    })(),

    preg_match('#^/seance/(\d+)/position$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $seanceId  = (int)$m[1];
        $sequenceId = (int)($_POST['sequence_id'] ?? 0);
        $position  = max(1, (int)($_POST['position'] ?? 1));
        if ($sequenceId && \src\DAO\SequenceDAO::getInstance()->isOwner($sequenceId, currentUserId())) {
            \src\DAO\SeanceDAO::getInstance()->setPositionInSequence($seanceId, $sequenceId, $position);
        }
        redirect(url('sequence/') . $sequenceId);
    })(),

    preg_match('#^/seance/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        $seqId = $seance['sequence_id'] ?? null;
        if ($seance) {
            if ($seqId) {
                $seq = \src\DAO\SequenceDAO::getInstance()->findById($seqId);
                if ($seq && $seq['user_id'] === currentUserId()) \src\DAO\SeanceDAO::getInstance()->delete((int)$m[1]);
            } else {
                \src\DAO\SeanceDAO::getInstance()->delete((int)$m[1]);
            }
        }
        flash('success', 'Séance supprimée.');
        if ($seqId) redirect(url('sequence/') . $seqId);
        else        redirect(url('seance/list'));
    })(),

    preg_match('#^/seance/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $seance = \src\DAO\SeanceDAO::getInstance()->findById((int)$m[1]);
        if (!$seance) { http_response_code(404); return; }
        $situations = \src\DAO\SituationDAO::getInstance()->findBySeance((int)$m[1]);
        $pdf = (new \src\Service\PdfService())->exportSeance($seance, $situations);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="seance-' . $m[1] . '.pdf"');
        echo $pdf;
    })(),

    $uri === '/seance/reorder' && $method === 'POST' => (function() {
        requireLogin();
        $body  = json_decode(file_get_contents('php://input'), true);
        $seqId = (int)($body['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            \src\DAO\SeanceDAO::getInstance()->reorder($seqId, $body['order'] ?? []);
        }
        jsonResponse(['ok' => true]);
    })(),

    // ---- SITUATIONS ----

    $uri === '/situation/create' && $method === 'GET' => (function() {
        requireLogin();
        $seanceId = (int)($_GET['seance_id'] ?? 0);
        view('situation/form', ['situation' => [], 'seance_id' => $seanceId]);
    })(),

    $uri === '/situation/create' && $method === 'POST' => (function() {
        requireLogin();
        $seanceId = (int)($_POST['seance_id'] ?? 0);
        $seance   = \src\DAO\SeanceDAO::getInstance()->findById($seanceId);
        if (!$seance) { http_response_code(404); return; }
        $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) { http_response_code(403); return; }
        $data = $_POST;
        $data['duree']                      = nullInt($_POST['duree'] ?? null);
        $data['variables_evolution']        = array_values($_POST['variables_evolution'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_situation'] ?? []);
        $id = \src\DAO\SituationDAO::getInstance()->create($seanceId, $data);
        flash('success', 'Situation créée !');
        redirect(url('sequence/') . $seq['id']);
    })(),

    preg_match('#^/situation/(\d+)/show$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $sit    = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) { http_response_code(404); view('errors/404'); return; }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seance    = $sit['seance_id'] ? \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']) : null;
        if ($fromSeqId) {
            $sequence = \src\DAO\SequenceDAO::getInstance()->findById($fromSeqId);
        } else {
            $sequence = ($seance && $seance['sequence_id']) ? \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) : null;
        }
        view('situation/show', [
            'situation' => $sit,
            'seance'    => $seance,
            'sequence'  => $sequence,
            'fromSeqId' => $fromSeqId,
        ]);
    })(),

    preg_match('#^/situation/(\d+)/edit$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        requireLogin();
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) { http_response_code(404); return; }
        $fromSeqId = ($_GET['from_seq'] ?? '') !== '' ? (int)$_GET['from_seq'] : null;
        $seance    = $sit['seance_id'] ? \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']) : null;
        view('situation/form', [
            'situation'  => $sit,
            'seance_id'  => $sit['seance_id'],
            'seance'     => $seance,
            'fromSeqId'  => $fromSeqId,
        ]);
    })(),

    preg_match('#^/situation/(\d+)/update$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $sit    = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) { http_response_code(404); return; }
        $seance = \src\DAO\SeanceDAO::getInstance()->findById($sit['seance_id']);
        $seq    = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
        if ($seq['user_id'] !== currentUserId()) { http_response_code(403); return; }
        $data = $_POST;
        $data['duree']                      = nullInt($_POST['duree'] ?? null);
        $data['variables_evolution']        = array_values($_POST['variables_evolution'] ?? []);
        $data['comportements_remediations'] = array_values($_POST['comportements_situation'] ?? []);
        \src\DAO\SituationDAO::getInstance()->update((int)$m[1], $data);
        flash('success', 'Situation mise à jour.');
        redirect(url('sequence/') . $seq['id']);
    })(),

    preg_match('#^/situation/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        $sit     = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        $seanceId = null;
        if ($sit) {
            $seanceId = $sit['seance_id'];
            $seance   = $seanceId ? \src\DAO\SeanceDAO::getInstance()->findById($seanceId) : null;
            // Vérifier ownership : soit via séquence, soit séance autonome
            $canDelete = false;
            if ($seance && $seance['sequence_id']) {
                $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']);
                $canDelete = $seq && $seq['user_id'] === currentUserId();
            } else {
                $canDelete = true; // séance autonome, pas de vérification stricte
            }
            if ($canDelete) \src\DAO\SituationDAO::getInstance()->delete((int)$m[1]);
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['ok' => true]);
        flash('success', 'Situation supprimée.');
        if ($seanceId) redirect(url('seance/') . $seanceId . '/show');
        else redirect(url('dashboard'));
    })(),

    preg_match('#^/situation/(\d+)/pdf$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        $sit = \src\DAO\SituationDAO::getInstance()->findById((int)$m[1]);
        if (!$sit) { http_response_code(404); return; }
        $pdf = (new \src\Service\PdfService())->exportSituation($sit);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="situation-' . $m[1] . '.pdf"');
        echo $pdf;
    })(),

    // ---- EXPLORER (fiches publiques) ----
    $uri === '/explorer' && $method === 'GET' => (function() {
        $filters   = ['cycle_id' => $_GET['cycle_id'] ?? '', 'classe_id' => $_GET['classe_id'] ?? '',
            'matiere_id' => $_GET['matiere_id'] ?? '', 'search' => $_GET['q'] ?? ''];
        $sequences = \src\DAO\SequenceDAO::getInstance()->findPublic(array_filter($filters));
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        view('explorer', [
            'sequences' => $sequences,
            'cycles'    => $dao->getCycles(),
            'matieres'  => $dao->getMatieres(),
            'filters'   => $filters,
        ]);
    })(),

    // ---- PROGRAMMES (référentiel) ----
    $uri === '/programmes' && $method === 'GET' => (function() {
        $versions = \src\DAO\ProgrammeDAO::getInstance()->getAllVersionsGrouped();
        view('programmes', ['versions' => $versions]);
    })(),

    // ---- PROFIL ----
    $uri === '/profil' && $method === 'GET' => (function() {
        requireLogin();
        $user = \src\DAO\UserDAO::getInstance()->findById(currentUserId());
        view('profil', ['user' => $user]);
    })(),

    $uri === '/profil' && $method === 'POST' => (function() {
        requireLogin();
        $uid = currentUserId();
        \src\DAO\UserDAO::getInstance()->updateProfile($uid, trim($_POST['nom'] ?? ''), trim($_POST['prenom'] ?? ''));
        flash('success', 'Profil mis à jour.');
        redirect(url('profil'));
    })(),

    // ---- CHANGEMENT MDP ----
    $uri === '/profil/password' && $method === 'POST' => (function() {
        requireLogin();
        $uid = currentUserId();
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) { flash('error', 'Les mots de passe ne correspondent pas.'); redirect(url('profil')); }
        if (strlen($new) < 8)  { flash('error', 'Mot de passe trop court (8 caractères min).'); redirect(url('profil')); }
        $ok = \src\DAO\UserDAO::getInstance()->changePassword($uid, $old, $new);
        if ($ok) flash('success', 'Mot de passe mis à jour.');
        else     flash('error', 'Mot de passe actuel incorrect.');
        redirect(url('profil'));
    })(),

    // ---- API REORDER SEANCES ----
    $uri === '/api/seances/reorder' && $method === 'POST' => (function() {
        requireLogin();
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $seqId = (int)($_GET['sequence_id'] ?? 0);
        if ($seqId && \src\DAO\SequenceDAO::getInstance()->isOwner($seqId, currentUserId())) {
            \src\DAO\SeanceDAO::getInstance()->reorder($seqId, $body['ids'] ?? []);
        }
        jsonResponse(['ok' => true]);
    })(),

    // ---- DIAGNOSTIC PDF ----
    $uri === '/pdf/check' && $method === 'GET' => (function() {
        requireLogin();
        $errors = \src\Service\PdfService::checkDependencies();
        if (empty($errors)) {
            echo '<div style="font-family:monospace;padding:20px;background:#dcfce7;color:#14532d">';
            echo '✅ Python3 et ReportLab sont correctement installés. Le PDF fonctionne !';
            echo '</div>';
        } else {
            echo '<div style="font-family:monospace;padding:20px;background:#fee2e2;color:#7f1d1d">';
            echo '❌ Problème détecté :<br>';
            foreach ($errors as $e) echo '• ' . htmlspecialchars($e) . '<br>';
            echo '</div>';
        }
    })(),

    // ---- GOOGLE OAUTH POUR PDF (refresh token) ----
    $uri === '/auth/google/init' && $method === 'GET' => (function() {
        requireLogin();
        $user = \src\Service\AuthService::currentUser();
        //if (!$user['admin']) { http_response_code(403); return; }
        (new \src\Service\GoogleAuthService())->redirect();
    })(),

    $uri === '/auth/google/token-callback' && $method === 'GET' => (function() {
        (new \src\Service\GoogleAuthService())->callback();
    })(),

    // ---- ADMIN PROGRAMMES ----
    $uri === '/admin/programmes' && $method === 'GET' => (function() {
        requireLogin(); // Idéalement requireAdmin();
        $versions = \src\DAO\ProgrammeDAO::getInstance()->getAllVersionsGrouped();
        view('admin/programmes/index', ['versions' => $versions]);
    })(),

    preg_match('#^/admin/programmes/version/(\d+)$#', $uri, $m) && $method === 'GET' => (function() use ($m) {
        requireLogin();
        $dao = \src\DAO\ProgrammeDAO::getInstance();
        $version = $dao->getVersionById((int)$m[1]);
        $tree = $dao->getItemsTree((int)$m[1]);
        view('admin/programmes/edit_tree', ['version' => $version, 'tree' => $tree]);
    })(),

    $uri === '/api/admin/programme-items' && $method === 'POST' => (function() {
        requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = \src\DAO\ProgrammeDAO::getInstance()->saveItem($data);
        jsonResponse(['ok' => true, 'id' => $id]);
    })(),

    preg_match('#^/api/admin/programme-items/(\d+)/delete$#', $uri, $m) && $method === 'POST' => (function() use ($m) {
        requireLogin();
        \src\DAO\ProgrammeDAO::getInstance()->deleteItem((int)$m[1]);
        jsonResponse(['ok' => true]);
    })(),

    // ---- 404 ----
    default => (function() {
        http_response_code(404);
        view('errors/404');
    })(),
};
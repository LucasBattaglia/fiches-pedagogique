<?php
// check_env2.php — diagnostic avancé Python + SSL
// Accès : http://localhost/check_env2.php
// SUPPRIMER après utilisation !

echo "<h2>Diagnostic avancé</h2>";
echo "<style>
    body { font-family: monospace; padding: 20px; }
    .ok   { color: green;  font-weight: bold; }
    .err  { color: red;    font-weight: bold; }
    .warn { color: orange; font-weight: bold; }
    code  { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    pre   { background: #f0f0f0; padding: 10px; border-radius: 5px; }
</style>";

// ════════════════════════════════════════════════════════════════
//  1. TROUVER PYTHON
// ════════════════════════════════════════════════════════════════
echo "<h3>1. Recherche de Python</h3>";

// Chemins courants où Python s'installe sur Windows
$pythonCandidates = [
    'C:\Users\lucas\AppData\Local\Python\bin\python.exe',
];

// Chercher aussi via where (Windows)
exec('where python 2>&1', $whereOut, $whereCode);
if ($whereCode === 0) {
    foreach ($whereOut as $line) {
        $line = trim($line);
        if ($line) $pythonCandidates[] = $line;
    }
    echo "Résultat <code>where python</code> :<br>";
    foreach ($whereOut as $l) echo "&nbsp;&nbsp;<code>" . htmlspecialchars($l) . "</code><br>";
}

exec('where python3 2>&1', $whereOut3, $whereCode3);
if ($whereCode3 === 0) {
    foreach ($whereOut3 as $line) {
        $line = trim($line);
        if ($line) $pythonCandidates[] = $line;
    }
}

$foundPython = null;
echo "<br><b>Test de chaque chemin :</b><br>";
foreach (array_unique($pythonCandidates) as $candidate) {
    $cmd = '"' . $candidate . '" --version 2>&1';
    exec($cmd, $out, $code);
    $version = implode('', $out);
    if ($code === 0 && stripos($version, 'Python') !== false) {
        echo "<span class='ok'>✓ Trouvé : <code>" . htmlspecialchars($candidate) . "</code> → " . htmlspecialchars($version) . "</span><br>";
        if (!$foundPython) $foundPython = $candidate;
    }
}

if ($foundPython) {
    echo "<br><div style='background:#e8f5e9;padding:10px;border-radius:5px'>";
    echo "<b>→ Chemin à utiliser dans PdfService.php :</b><br>";
    echo "<code>" . htmlspecialchars($foundPython) . "</code><br><br>";
    echo "Dans <code>mergePdfs()</code>, remplacer :<br>";
    echo "<code>exec(\"python3 \" . escapeshellarg(\$script)</code><br>";
    echo "par :<br>";
    echo "<code>exec('\"" . htmlspecialchars(str_replace('\\', '\\\\', $foundPython)) . "\" ' . escapeshellarg(\$script)</code>";
    echo "</div>";

    // Tester pypdf avec ce Python
    echo "<br><h3>2. Test pypdf avec ce Python</h3>";
    $cmd = '"' . $foundPython . '" -c "import pypdf; print(pypdf.__version__)" 2>&1';
    exec($cmd, $outPyp, $codePyp);
    if ($codePyp === 0) {
        echo "<span class='ok'>✓ pypdf " . htmlspecialchars(implode('', $outPyp)) . "</span>";
    } else {
        echo "<span class='err'>✗ pypdf non installé pour ce Python</span><br>";
        // Trouver pip associé
        $pipPath = str_replace('python.exe', 'pip.exe', $foundPython);
        echo "<br><b>Pour installer pypdf, ouvrir un terminal et exécuter :</b><br>";
        echo "<pre>\"" . htmlspecialchars($foundPython) . "\" -m pip install pypdf</pre>";
        echo "ou si pip.exe existe :<br>";
        echo "<pre>\"" . htmlspecialchars($pipPath) . "\" install pypdf</pre>";
    }
} else {
    echo "<br><span class='err'>✗ Python introuvable dans les chemins testés.</span><br>";
    echo "Ouvrir un terminal Windows (<b>cmd</b>) et taper : <code>where python</code><br>";
    echo "Puis coller le chemin complet ici pour le tester manuellement.";
}

// ════════════════════════════════════════════════════════════════
//  3. FIX SSL
// ════════════════════════════════════════════════════════════════
echo "<h3>3. Fix SSL pour cURL</h3>";

// Télécharger le bundle de certificats Mozilla
$caPath = 'C:/wamp64/bin/php/' . PHP_VERSION . '/extras/ssl/cacert.pem';
$caDir  = dirname($caPath);

echo "Chemin cible du certificat : <code>$caPath</code><br><br>";

// Vérifier si le fichier existe déjà
if (file_exists($caPath)) {
    echo "<span class='ok'>✓ cacert.pem déjà présent</span><br>";
} else {
    echo "<span class='warn'>⚠ cacert.pem absent — téléchargement...</span><br>";

    // Télécharger cacert.pem depuis curl.se (sans vérification SSL pour bootstrapper)
    $ch = curl_init('https://curl.se/ca/cacert.pem');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // nécessaire pour bootstrapper
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $cert = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && strlen($cert) > 1000) {
        if (!is_dir($caDir)) mkdir($caDir, 0755, true);
        file_put_contents($caPath, $cert);
        echo "<span class='ok'>✓ cacert.pem téléchargé (" . number_format(strlen($cert)) . " octets)</span><br>";
    } else {
        echo "<span class='err'>✗ Téléchargement échoué (HTTP $code)</span><br>";
        echo "→ Télécharger manuellement depuis <a href='https://curl.se/ca/cacert.pem'>curl.se/ca/cacert.pem</a><br>";
        echo "→ Placer le fichier dans <code>$caDir</code><br>";
    }
}

// Afficher les lignes à ajouter dans php.ini
$phpIni = php_ini_loaded_file();
echo "<br><b>Vérifier / ajouter dans php.ini</b> (<code>$phpIni</code>) :<br>";
echo "<pre>curl.cainfo = \"$caPath\"
openssl.cafile = \"$caPath\"</pre>";

// Tester SSL après fix
echo "<b>Test SSL après configuration :</b><br>";
$ch = curl_init('https://www.googleapis.com/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_CAINFO         => $caPath,
]);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$sslError = curl_error($ch);
curl_close($ch);

if ($httpCode > 0) {
    echo "<span class='ok'>✓ SSL OK avec ce certificat (HTTP $httpCode)</span><br>";
    echo "<br><div style='background:#e8f5e9;padding:10px;border-radius:5px'>";
    echo "<b>✓ Fix SSL confirmé.</b><br>";
    echo "Il faut maintenant ajouter ces 2 lignes dans php.ini et redémarrer WAMP :<br>";
    echo "<pre>curl.cainfo = \"" . str_replace('/', '\\', $caPath) . "\"
openssl.cafile = \"" . str_replace('/', '\\', $caPath) . "\"</pre>";
    echo "</div>";
} else {
    echo "<span class='err'>✗ SSL toujours KO : $sslError</span><br>";
    echo "→ Chemin php.ini actif : <code>$phpIni</code><br>";
    echo "→ Ajouter manuellement et redémarrer WAMP";
}

echo "<br><br><i style='color:gray'>⚠ Supprimer ce fichier check_env2.php après utilisation !</i>";
<?php
$envFile = __DIR__ . '/.env';
echo "Fichier .env existe : " . (file_exists($envFile) ? "OUI" : "NON") . "<br>";
echo "Contenu brut :<br><pre>";
echo file_get_contents($envFile);
echo "</pre>";
echo "DB_NAME lu : " . getenv('DB_NAME') . "<br>";
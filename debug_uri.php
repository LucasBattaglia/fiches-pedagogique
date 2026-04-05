<?php
// check_pdf.php
// https://developpement.mapreparation.eduscol.org/check_pdf.php
// SUPPRIMER après utilisation !

echo "<pre style='font-family:monospace;padding:20px'>";

$paths = [
    __DIR__ . '/src/Service/PdfService.php',
    __DIR__ . '/src/service/PdfService.php',
    __DIR__ . '/src/Services/PdfService.php',
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "✓ TROUVÉ : $path\n";
        // Afficher les 5 premières lignes
        $lines = array_slice(file($path), 0, 10);
        echo implode('', $lines) . "\n...\n";
    } else {
        echo "✗ absent : $path\n";
    }
}

// Lister tout le dossier src/Service/
$dir = __DIR__ . '/src/Service/';
echo "\nContenu de src/Service/ :\n";
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f !== '.' && $f !== '..') echo "  - $f\n";
    }
} else {
    echo "  Dossier introuvable !\n";
}
echo "</pre>";
<?php
// src/Service/PdfService.php

namespace src\Service;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * PdfService — génération des fiches pédagogiques via Google Docs + Drive API
 *
 * Configuration dans .env :
 *   GOOGLE_CLIENT_ID=...
 *   GOOGLE_CLIENT_SECRET=...
 *   GOOGLE_REFRESH_TOKEN=...
 *   PYTHON_PATH=C:\Users\lucas\AppData\Local\Python\bin\python.exe
 *   CACERT_PATH=C:\wamp64\bin\php\cacert.pem
 */
class PdfService
{
    // ── IDs des Google Docs modèles ───────────────────────────────
    const TPL_SEQUENCE  = '1cwMaeu1igSTj80w-EINvzxjAbAB9GmpJEshq8eUjXc0';
    const TPL_SEANCE    = '1pgQpz_p7Ph9ifIECeYtpU-hn7oDaZnr6VD_iStatyMM';
    const TPL_SITUATION = '1qarvY2bvP7KinB0mT-PhJcPhOG4n8Gz98pWEEfjUurA';

    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $accessToken  = '';
    private string $pythonPath;
    private string $cacertPath;

    // ════════════════════════════════════════════════════════════════
    //  CONSTRUCTEUR — lit tout depuis .env
    // ════════════════════════════════════════════════════════════════
    public function __construct()
    {
        $cfg = $this->loadConfig();

        $this->clientId     = $cfg['GOOGLE_CLIENT_ID']     ?? '';
        $this->clientSecret = $cfg['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->refreshToken = $cfg['GOOGLE_REFRESH_TOKEN'] ?? '';
        $this->pythonPath   = $cfg['PYTHON_PATH'] ?? 'C:\\Users\\lucas\\AppData\\Local\\Python\\bin\\python.exe';
        $this->cacertPath   = $cfg['CACERT_PATH'] ?? '';

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET doivent être définis dans .env');
        }
        if (empty($this->refreshToken)) {
            throw new \RuntimeException("GOOGLE_REFRESH_TOKEN manquant dans .env. Ouvrir /auth/google/init pour l'obtenir.");
        }
    }

    // ── Lecture .env + config.php ─────────────────────────────────
    private function loadConfig(): array
    {
        $cfg = [];
        $configPath = __DIR__ . '/../../config/config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            $cfg['GOOGLE_CLIENT_ID']     = $config['oauth']['google']['client_id']     ?? '';
            $cfg['GOOGLE_CLIENT_SECRET'] = $config['oauth']['google']['client_secret'] ?? '';
        }
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$key, $val] = explode('=', $line, 2);
                $cfg[trim($key)] = trim($val);
            }
        }
        return $cfg;
    }

    // ════════════════════════════════════════════════════════════════
    //  SSL — applique les options SSL sur un handle cURL
    // ════════════════════════════════════════════════════════════════
    private function applySSL(\CurlHandle $ch): void
    {
        if (!empty($this->cacertPath) && file_exists($this->cacertPath)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->cacertPath);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  OAUTH2 — échange refresh_token → access_token
    // ════════════════════════════════════════════════════════════════
    private function ensureAccessToken(): void
    {
        if (!empty($this->accessToken)) return;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type'    => 'refresh_token',
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($resp['access_token'])) {
            throw new \RuntimeException("Impossible d'obtenir un access_token Google : " . json_encode($resp));
        }
        $this->accessToken = $resp['access_token'];
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPERS cURL
    // ════════════════════════════════════════════════════════════════

    /** POST JSON vers l'API Google, retourne le tableau décodé. */
    private function curlPost(string $url, array $body): array
    {
        $this->ensureAccessToken();
        $ch = curl_init($url);
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }

    /** GET vers l'API Google, retourne le tableau décodé. */
    private function curlGet(string $url): array
    {
        $this->ensureAccessToken();
        $ch = curl_init($url);
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->accessToken]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }

    // ════════════════════════════════════════════════════════════════
    //  API GOOGLE DRIVE / DOCS
    // ════════════════════════════════════════════════════════════════

    /** Copie un Google Doc, retourne l'ID de la copie. */
    private function copyDoc(string $templateId, string $title): string
    {
        $resp = $this->curlPost(
            "https://www.googleapis.com/drive/v3/files/{$templateId}/copy",
            ['name' => $title]
        );
        if (empty($resp['id'])) {
            throw new \RuntimeException('Impossible de copier le modèle : ' . json_encode($resp));
        }
        return $resp['id'];
    }

    /** Remplace tous les {{placeholders}} dans un Google Doc. */
    private function fillDoc(string $docId, array $replacements): void
    {
        $requests = [];
        foreach ($replacements as $placeholder => $value) {
            $requests[] = [
                'replaceAllText' => [
                    'containsText' => ['text' => $placeholder, 'matchCase' => true],
                    'replaceText'  => (string)($value ?? ''),
                ],
            ];
        }
        $this->curlPost(
            "https://docs.googleapis.com/v1/documents/{$docId}:batchUpdate",
            ['requests' => $requests]
        );
    }

    /** Récupère la structure complète d'un Google Doc. */
    private function getDoc(string $docId): array
    {
        return $this->curlGet("https://docs.googleapis.com/v1/documents/{$docId}");
    }

    /** Exporte un Google Doc en PDF, retourne le contenu binaire. */
    private function exportPdf(string $docId): string
    {
        $this->ensureAccessToken();
        $url = "https://www.googleapis.com/drive/v3/files/{$docId}/export?mimeType=application%2Fpdf";
        $ch  = curl_init($url);
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->accessToken]);
        $pdf  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || empty($pdf)) {
            throw new \RuntimeException("Export PDF échoué (HTTP $code) pour le doc $docId");
        }
        return $pdf;
    }

    /** Supprime un fichier Google Drive. */
    private function deleteDoc(string $docId): void
    {
        $this->ensureAccessToken();
        $ch = curl_init("https://www.googleapis.com/drive/v3/files/{$docId}");
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->accessToken]);
        curl_exec($ch);
        curl_close($ch);
    }

    // ════════════════════════════════════════════════════════════════
    //  fillTable() — remplit un tableau Google Docs ligne par ligne
    //
    //  Stratégie :
    //  1. Trouver le tableau contenant $tableMarker
    //  2. Identifier la ligne template (celle avec le marker)
    //  3. Insérer N-1 lignes vides après la ligne template
    //  4. Recharger le doc pour avoir les index à jour
    //  5. Remplir chaque cellule via insertText (de bas en haut)
    //  6. Supprimer le placeholder
    // ════════════════════════════════════════════════════════════════
    private function fillTable(string $docId, string $tableMarker, array $rows, int $headerRows = 1): void
    {
        if (empty($rows)) {
            $this->fillDoc($docId, [$tableMarker => '']);
            return;
        }

        // ── 1. Récupérer le document ──────────────────────────────
        $doc = $this->getDoc($docId);

        // ── 2. Trouver le tableau et la ligne template ────────────
        $tableStartIndex  = null;
        $templateRowIndex = null;

        foreach ($doc['body']['content'] ?? [] as $element) {
            if (!isset($element['table'])) continue;
            foreach ($element['table']['tableRows'] as $ri => $row) {
                foreach ($row['tableCells'] as $cell) {
                    foreach ($cell['content'] ?? [] as $par) {
                        foreach ($par['paragraph']['elements'] ?? [] as $el) {
                            if (str_contains($el['textRun']['content'] ?? '', $tableMarker)) {
                                $tableStartIndex  = $element['startIndex'];
                                $templateRowIndex = $ri;
                                break 4;
                            }
                        }
                    }
                }
            }
            if ($tableStartIndex !== null) break;
        }

        if ($tableStartIndex === null) return;

        // ── 3. Insérer N-1 lignes vides après la ligne template ───
        $requests = [];
        $nbExtra  = count($rows) - 1;
        for ($i = 0; $i < $nbExtra; $i++) {
            $requests[] = [
                'insertTableRow' => [
                    'tableCellLocation' => [
                        'tableStartLocation' => ['index' => $tableStartIndex],
                        'rowIndex'           => $templateRowIndex,
                        'columnIndex'        => 0,
                    ],
                    'insertBelow' => true,
                ],
            ];
        }
        if (!empty($requests)) {
            $this->curlPost(
                "https://docs.googleapis.com/v1/documents/{$docId}:batchUpdate",
                ['requests' => $requests]
            );
        }

        // ── 4. Recharger le doc ───────────────────────────────────
        $doc = $this->getDoc($docId);

        // Retrouver le tableau par startIndex
        $tableElement = null;
        foreach ($doc['body']['content'] ?? [] as $element) {
            if (isset($element['table']) && $element['startIndex'] === $tableStartIndex) {
                $tableElement = $element;
                break;
            }
        }
        if (!$tableElement) return;

        // ── 5. Remplir chaque cellule (de bas en haut) ────────────
        $insertRequests = [];
        foreach (array_reverse(array_keys($rows)) as $ri) {
            $rowData  = $rows[$ri];
            $tableRow = $tableElement['table']['tableRows'][$templateRowIndex + $ri] ?? null;
            if (!$tableRow) continue;

            foreach (array_reverse(array_keys($rowData)) as $ci) {
                $cell = $tableRow['tableCells'][$ci] ?? null;
                if (!$cell) continue;

                $text = (string)($rowData[$ci] ?? '');
                if ($text === '') continue;

                $insertIndex = $cell['content'][0]['startIndex'] ?? null;
                if ($insertIndex === null) continue;

                $insertRequests[] = [
                    'insertText' => [
                        'location' => ['index' => $insertIndex],
                        'text'     => $text,
                    ],
                ];
            }
        }

        // ── 6. Supprimer le placeholder ───────────────────────────
        $insertRequests[] = [
            'replaceAllText' => [
                'containsText' => ['text' => $tableMarker, 'matchCase' => true],
                'replaceText'  => '',
            ],
        ];

        if (!empty($insertRequests)) {
            $this->curlPost(
                "https://docs.googleapis.com/v1/documents/{$docId}:batchUpdate",
                ['requests' => $insertRequests]
            );
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  generatePdf() — pipeline complet pour une fiche
    // ════════════════════════════════════════════════════════════════
    private function generatePdf(string $templateId, string $title, array $data, array $tables = []): string
    {
        $docId = $this->copyDoc($templateId, '[tmp] ' . $title);
        try {
            $this->fillDoc($docId, $data);
            foreach ($tables as $marker => $tableData) {
                $this->fillTable($docId, $marker, $tableData['rows'], $tableData['headerRows'] ?? 1);
            }
            return $this->exportPdf($docId);
        } finally {
            $this->deleteDoc($docId);
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  mergePdfs() — concatène plusieurs PDFs binaires via pypdf
    // ════════════════════════════════════════════════════════════════
    private function mergePdfs(array $pdfContents): string
    {
        $tmp   = sys_get_temp_dir();
        $files = [];
        foreach ($pdfContents as $i => $content) {
            $path    = "$tmp/fiche_{$i}_" . uniqid() . '.pdf';
            file_put_contents($path, $content);
            $files[] = $path;
        }

        $out    = "$tmp/merged_" . uniqid() . '.pdf';
        $list   = json_encode($files);
        $script = "$tmp/merge_" . uniqid() . '.py';

        file_put_contents($script, <<<PYTHON
import json
from pypdf import PdfWriter, PdfReader
files = json.loads(r'{$list}')
writer = PdfWriter()
for path in files:
    for page in PdfReader(path).pages:
        writer.add_page(page)
with open(r"{$out}", "wb") as f:
    writer.write(f)
PYTHON);

        exec('"' . $this->pythonPath . '" ' . escapeshellarg($script) . ' 2>&1', $output, $code);
        unlink($script);
        foreach ($files as $f) { @unlink($f); }

        if ($code !== 0 || !file_exists($out)) {
            throw new \RuntimeException('Erreur merge PDF : ' . implode("\n", $output));
        }
        $merged = file_get_contents($out);
        unlink($out);
        return $merged;
    }

    // ════════════════════════════════════════════════════════════════
    //  MAPPING données → placeholders
    // ════════════════════════════════════════════════════════════════

    private function sequenceData(array $seq): array
    {
        // — Items programme : séparés par niveau ——————————
        $competencesText       = '';
        $objectifsApprentissage = '';
        if (!empty($seq['programme_items']) && !empty($seq['programme_version_id'])) {
            try {
                $allItems = \src\DAO\ProgrammeDAO::getInstance()->getItemsFlat((int)$seq['programme_version_id']);
                $ids      = array_map('intval', (array)$seq['programme_items']);
                $lignes1  = [];
                $lignes2  = [];
                foreach ($allItems as $item) {
                    if (!in_array((int)$item['id'], $ids, true)) continue;
                    $label = (!empty($item['code']) ? $item['code'] . ' – ' : '') . $item['label'];
                    $niveau = (int)($item['niveau'] ?? 0);
                    if ($niveau === 1) $lignes1[] = $label;
                    if ($niveau === 2) $lignes2[] = $label;
                }
                $competencesText        = implode("\n", $lignes1);
                $objectifsApprentissage = implode("\n", $lignes2);
            } catch (\Throwable $e) {}
        }

        return [
            '{{domaine}}'                 => $seq['domaine']                 ?? '',
            '{{champ_apprentissage}}'     => $seq['domaine']                 ?? $seq['programme_label'] ?? '',
            '{{niveau}}'                  => trim(preg_replace('/^(Cycle\\s+\\d+).*$/u', '$1', $seq['cycle_label'] ?? '') . ' – ' . ($seq['classe_code'] ?? ''), ' –'),
            '{{titre_sequence}}'          => $seq['titre']                   ?? '',
            '{{nb_seances}}'              => (string)($seq['nb_seances']     ?? ''),
            '{{tache_finale}}'            => $seq['tache_finale']            ?? '',
            '{{competences}}'             => $competencesText,
            '{{objectifs_apprentissage}}' => $objectifsApprentissage,
            '{{objectifs_generaux}}'      => $seq['objectifs_generaux']      ?? '',
            '{{materiel}}'                => $seq['materiel']                ?? '',
        ];
    }

    private function seanceData(array $s, int $num, array $seq = []): array
    {
        return [
            '{{num_seance}}'              => (string)$num,
            '{{numero}}'                  => (string)$num,
            '{{cycle}}'                   => preg_replace('/^(Cycle\s+\d+).*$/u', '$1', $seq['cycle_label'] ?? $s['cycle_label'] ?? ''),
            '{{cycle_numero}}'            => preg_replace('/[^0-9]/', '', $seq['cycle_code'] ?? $s['cycle_code'] ?? ''),
            '{{champ_apprentissage}}'     => $s['champ_apprentissage']    ?? '',
            '{{niveau}}'                  => $seq['classe_code']          ?? $s['niveau'] ?? '',
            '{{titre_sequence}}'          => $seq['titre']                 ?? $s['titre_sequence'] ?? '',
            '{{titre_seance}}'            => $s['titre']                   ?? '',
            '{{competence_visee}}'        => $s['competence_visee']        ?? '',
            '{{afc}}'                     => $s['afc']                     ?? '',
            '{{objectif_general}}'        => $s['objectif_general']        ?? '',
            '{{objectif_intermediaire}}'  => $s['objectif_intermediaire']  ?? '',
            '{{duree}}'                   => ($s['duree'] ?? '') ? $s['duree'] . ' min' : '',
            '{{materiel}}'                => $s['materiel']                ?? '',
            '{{criteres_realisation}}'    => $s['criteres_realisation']    ?? '',
            '{{criteres_reussite}}'       => $s['criteres_reussite']       ?? '',
            '{{variables_didactiques}}'   => $s['variables_didactiques']   ?? '',
        ];
    }

    private function situationData(array $sit): array
    {
        $ve = is_array($sit['variables_evolution'])
            ? $sit['variables_evolution']
            : (json_decode($sit['variables_evolution'] ?? '[]', true) ?? []);
        $veLines = array_map(
            fn($r) => ($r['variable'] ?? '') .
                ($r['plus']  ? '  (+) ' . $r['plus']  : '') .
                ($r['moins'] ? '  (−) ' . $r['moins'] : ''),
            $ve
        );

        return [
            '{{num_situation}}'           => (string)($sit['numero'] ?? 1),
            '{{num_seance}}'              => (string)($sit['seance_numero'] ?? ''),
            '{{titre_situation}}'         => $sit['titre']                   ?? '',
            '{{numero}}'                  => (string)($sit['numero'] ?? 1),
            '{{titre}}'                   => $sit['titre']                   ?? '',
            '{{champ_apprentissage}}'     => $sit['champ_apprentissage']     ?? '',
            '{{seance_support}}'          => $sit['seance_support']          ?? '',
            '{{duree}}'                   => ($sit['duree'] ?? '') ? $sit['duree'] . ' min' : '',
            '{{afc}}'                     => $sit['afc']                     ?? '',
            '{{objectif_moteur}}'         => $sit['objectif_moteur']         ?? '',
            '{{objectif_socio_affectif}}' => $sit['objectif_socio_affectif'] ?? '',
            '{{objectif_cognitif}}'       => $sit['objectif_cognitif']       ?? '',
            '{{materiel}}'                => $sit['materiel']                ?? '',
            '{{but}}'                     => $sit['but']                     ?? '',
            '{{dispositif}}'              => $sit['dispositif']              ?? '',
            '{{organisation}}'            => $sit['organisation']            ?? '',
            '{{fonctionnement}}'          => $sit['fonctionnement']          ?? '',
            '{{consignes_base}}'          => $sit['consignes_base']          ?? '',
            '{{variables_evolution}}'     => implode("\n", $veLines),
            '{{criteres_realisation}}'    => $sit['criteres_realisation']    ?? '',
            '{{criteres_reussite}}'       => $sit['criteres_reussite']       ?? '',
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  Helper : comportements → rows pour fillTable
    // ════════════════════════════════════════════════════════════════
    private function crRows(mixed $cr): array
    {
        $data = is_array($cr) ? $cr : (json_decode($cr ?? '[]', true) ?? []);
        return array_map(fn($r) => [
            $r['comportement'] ?? '',
            $r['remediation']  ?? '',
        ], $data);
    }

    // ════════════════════════════════════════════════════════════════
    //  EXPORTS PUBLICS
    // ════════════════════════════════════════════════════════════════

    public function exportSequence(array $seq, array $seances): string
    {
        $pdfs = [];

        // Tableau des séances
        $seancesRows = array_map(fn($s, $i) => [
            (string)(($s['position_in_seq'] . " - " . $s['titre']) ?? ($s['numero'] . " - " . $s['titre']) ?? ($i + 1 . " - " . $s['titre'])),
            $s['objectif_intermediaire'] ?? $s['objectif_general'] ?? '',
            ($s['duree'] ?? '') ? $s['duree'] . ' min' : '',
        ], $seances, array_keys($seances));

        $pdfs[] = $this->generatePdf(
            self::TPL_SEQUENCE,
            'Séquence – ' . ($seq['titre'] ?? 'export'),
            $this->sequenceData($seq),
            [
                '{{seances_table}}'       => ['rows' => $seancesRows,             'headerRows' => 1],
                '{{comportements_table}}' => ['rows' => $this->crRows($seq['comportements_remediations']), 'headerRows' => 1],
            ]
        );

        foreach ($seances as $i => $seance) {
            $num = $seance['position_in_seq'] ?? $seance['numero'] ?? ($i + 1);

            $derr = is_array($seance['deroulement'])
                ? $seance['deroulement']
                : (json_decode($seance['deroulement'] ?? '[]', true) ?? []);
            $derrRows = array_map(fn($r) => [
                ($r['duree'] ?? '') ? $r['duree'] . ' min' : '',
                $r['enseignant'] ?? '',
                $r['eleves']     ?? '',
            ], $derr);

            $pdfs[] = $this->generatePdf(
                self::TPL_SEANCE,
                "Séance $num – " . ($seance['titre'] ?? ''),
                $this->seanceData($seance, $num, $seq),
                [
                    '{{deroulement_table}}'   => ['rows' => $derrRows,                                         'headerRows' => 1],
                    '{{comportements_table}}' => ['rows' => $this->crRows($seance['comportements_remediations']), 'headerRows' => 1],
                ]
            );

            $situations = \src\DAO\SituationDAO::getInstance()->findBySeance($seance['id']);
            foreach ($situations as $sit) {
                $sit['seance_numero'] = $num;
                $pdfs[] = $this->generatePdf(
                    self::TPL_SITUATION,
                    'Situation ' . ($sit['numero'] ?? 1) . ' – ' . ($sit['titre'] ?? ''),
                    $this->situationData($sit),
                    [
                        '{{comportements_table}}' => ['rows' => $this->crRows($sit['comportements_remediations']), 'headerRows' => 1],
                    ]
                );
            }
        }

        return count($pdfs) === 1 ? $pdfs[0] : $this->mergePdfs($pdfs);
    }

    public function exportSeance(array $seance, array $situations): string
    {
        $num = $seance['numero'] ?? 1;
        $seq = [];
        if (!empty($seance['sequence_id'])) {
            $seq = \src\DAO\SequenceDAO::getInstance()->findById($seance['sequence_id']) ?? [];
        }

        $derr = is_array($seance['deroulement'])
            ? $seance['deroulement']
            : (json_decode($seance['deroulement'] ?? '[]', true) ?? []);
        $derrRows = array_map(fn($r) => [
            ($r['duree'] ?? '') ? $r['duree'] . ' min' : '',
            $r['enseignant'] ?? '',
            $r['eleves']     ?? '',
        ], $derr);

        $pdfs = [];
        $pdfs[] = $this->generatePdf(
            self::TPL_SEANCE,
            "Séance $num – " . ($seance['titre'] ?? ''),
            $this->seanceData($seance, $num, $seq),
            [
                '{{deroulement_table}}'   => ['rows' => $derrRows,                                           'headerRows' => 1],
                '{{comportements_table}}' => ['rows' => $this->crRows($seance['comportements_remediations']), 'headerRows' => 1],
            ]
        );

        foreach ($situations as $sit) {
            $sit['seance_numero'] = $num;
            $pdfs[] = $this->generatePdf(
                self::TPL_SITUATION,
                'Situation ' . ($sit['numero'] ?? 1) . ' – ' . ($sit['titre'] ?? ''),
                $this->situationData($sit),
                [
                    '{{comportements_table}}' => ['rows' => $this->crRows($sit['comportements_remediations']), 'headerRows' => 1],
                ]
            );
        }

        return count($pdfs) === 1 ? $pdfs[0] : $this->mergePdfs($pdfs);
    }

    public function exportSituation(array $sit): string
    {
        return $this->generatePdf(
            self::TPL_SITUATION,
            'Situation ' . ($sit['numero'] ?? 1) . ' – ' . ($sit['titre'] ?? ''),
            $this->situationData($sit),
            [
                '{{comportements_table}}' => ['rows' => $this->crRows($sit['comportements_remediations']), 'headerRows' => 1],
            ]
        );
    }
}
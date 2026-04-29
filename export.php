<?php
session_start();
if (!($_SESSION['admin'] ?? false)) {
    http_response_code(403);
    exit('Accesso negato.');
}

$cfg = require __DIR__ . '/config.php';
$dataFile = $cfg['data_file'];
$responses = [];
if (file_exists($dataFile)) {
    $responses = json_decode(file_get_contents($dataFile), true) ?: [];
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="rsvp_' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
fputcsv($out, ['Nome', 'Risposta', 'Adulti extra', 'Bimbi', 'Salite', 'Birre', 'Porta', 'Note', 'Data']);

foreach ($responses as $r) {
    fputcsv($out, [
        $r['name'],
        $r['coming'],
        $r['adults']   ?? 0,
        $r['children'] ?? 0,
        $r['climbs']   ?? 0,
        $r['coming'] === 'si' ? ($r['climbs'] ?? 0) : 0,
        $r['bringing'] ?? '',
        $r['notes']    ?? '',
        $r['timestamp'] ?? '',
    ]);
}
fclose($out);

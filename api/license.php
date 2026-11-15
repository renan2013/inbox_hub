<?php
/**
 * Inbox Cloud Hub — API Pública de Consulta de Licencia (Pull Mode)
 * GET /api/license.php?client_id=unela (o cefi)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

$clientId = strtolower(trim($_GET['client_id'] ?? $_POST['client_id'] ?? ''));
if ($clientId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetro client_id requerido.']);
    exit;
}

$platforms = get_registered_platforms();
if (!isset($platforms[$clientId])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Plataforma no registrada en Inbox Hub.']);
    exit;
}

$plat = $platforms[$clientId];
$responsePayload = [
    'success'         => true,
    'client_id'       => $plat['id'],
    'nombre'          => $plat['nombre'],
    'estado_licencia' => $plat['estado_licencia'] ?? 'activo',
    'mensaje_aviso'   => $plat['mensaje_aviso'] ?? '',
    'plan_comercial'  => $plat['plan_comercial'] ?? '',
    'flags'           => $plat['flags'] ?? get_default_flags_for_profile('full_suite'),
    'timestamp'       => time(),
];

$jsonBody = json_encode($responsePayload, JSON_UNESCAPED_UNICODE);
$signature = hash_hmac('sha256', $jsonBody, INBOX_HUB_MASTER_SECRET);
header('X-Inbox-Hub-Signature: ' . $signature);

echo $jsonBody;

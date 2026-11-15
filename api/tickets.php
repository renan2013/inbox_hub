<?php
/**
 * ============================================================================
 * INBOX CLOUD HUB - API CENTRAL DE AVERÍAS Y TICKETS MULTI-PLATAFORMA
 * Ruta: https://hub.renangalvan.net/api/tickets.php
 * ============================================================================
 * Recibe reportes de averías con Evidencia Gráfica desde cualquier plataforma
 * (inbox_cefi, bpm_unela, etc.), los registra en el Hub y dispara alertas a:
 *   1. WhatsApp vía Webhook de n8n
 *   2. Correo Electrónico del Fabricante (renan@renangalvan.net)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Inbox-Hub-Signature, X-Inbox-Client-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_INBOX_HUB_SIGNATURE'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = (string)($_GET['secret'] ?? '');
    $validSecrets = [INBOX_HUB_MASTER_SECRET, 'INBOX_HUB_MASTER_2026_RG_SECRET_KEY'];
    if (!in_array($token, $validSecrets, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado.']);
        exit;
    }
    $ticketState = load_hub_tickets();
    echo json_encode([
        'success' => true,
        'total'   => count($ticketState['tickets']),
        'tickets' => $ticketState['tickets'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($rawBody)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload vacío.']);
    exit;
}

$expectedSig1 = hash_hmac('sha256', $rawBody, INBOX_HUB_MASTER_SECRET);
$expectedSig2 = hash_hmac('sha256', $rawBody, 'INBOX_HUB_MASTER_2026_RG_SECRET_KEY');
if (empty($signature) || (!hash_equals($expectedSig1, $signature) && !hash_equals($expectedSig2, $signature))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Firma HMAC inválida.']);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$ticketState = load_hub_tickets();
$platforms   = get_registered_platforms();
$clientId    = strtolower(trim($payload['client_id'] ?? 'cefi'));
if ($clientId === 'inbox_cefi') $clientId = 'cefi';
if ($clientId === 'bpm_unela') $clientId = 'unela';
$platMeta    = $platforms[$clientId] ?? [];

$action = trim($payload['action'] ?? 'create');

if ($action === 'delete_ticket') {
    $localId = $payload['local_id'] ?? '';
    $hubId   = $payload['hub_id'] ?? '';
    $beforeCount = count($ticketState['tickets']);
    $ticketState['tickets'] = array_values(array_filter($ticketState['tickets'], function ($tk) use ($clientId, $localId, $hubId) {
        if ($localId === 'all' && ($tk['client_id'] ?? '') === $clientId) {
            return false;
        }
        if ($hubId !== '' && ($tk['hub_id'] ?? '') === $hubId) {
            return false;
        }
        if ($localId !== '' && $localId !== 'all' && ($tk['client_id'] ?? '') === $clientId && (string)($tk['id'] ?? '') === (string)$localId) {
            return false;
        }
        return true;
    }));
    save_hub_tickets($ticketState);
    echo json_encode([
        'success' => true,
        'message' => 'Ticket(s) eliminado(s) en Inbox Cloud Hub.',
        'removed' => $beforeCount - count($ticketState['tickets']),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$localIdStr = (string)($payload['local_id'] ?? uniqid());

// Evitar duplicados si ya existe el mismo client_id + local_id
$ticketState['tickets'] = array_values(array_filter($ticketState['tickets'], function ($tk) use ($clientId, $localIdStr) {
    return !(($tk['client_id'] ?? '') === $clientId && (string)($tk['id'] ?? '') === $localIdStr);
}));

$ticket = [
    'hub_id'          => 'AV-' . strtoupper($clientId) . '-' . $localIdStr,
    'id'              => $localIdStr,
    'client_id'       => $clientId,
    'client_siglas'   => $platMeta['siglas'] ?? strtoupper($payload['client_siglas'] ?? $clientId),
    'client_nombre'   => $platMeta['nombre'] ?? ($payload['client_nombre'] ?? $clientId),
    'titulo'          => trim($payload['titulo'] ?? 'Reporte de Avería'),
    'etiqueta'        => trim($payload['etiqueta'] ?? 'Fallo en Módulo'),
    'prioridad'       => strtolower(trim($payload['prioridad'] ?? 'alta')),
    'problema'        => trim($payload['problema'] ?? ''),
    'solucion'        => trim($payload['solucion'] ?? ''),
    'estado'          => trim($payload['estado'] ?? 'reportado'),
    'url_origen'      => trim($payload['url_origen'] ?? ''),
    'navegador'       => trim($payload['navegador'] ?? ''),
    'evidencia_url'   => trim($payload['evidencia_url'] ?? ''),
    'usuario_nombre'  => trim($payload['usuario_nombre'] ?? 'Usuario'),
    'usuario_email'   => trim($payload['usuario_email'] ?? ''),
    'usuario_rol'     => trim($payload['usuario_rol'] ?? ''),
    'fecha_creacion'  => $payload['fecha_creacion'] ?? date('Y-m-d H:i:s'),
];

// Insertar al inicio de la lista de tickets del Hub
array_unshift($ticketState['tickets'], $ticket);
// Mantener máximo 300 tickets en histórico rápido
if (count($ticketState['tickets']) > 300) {
    $ticketState['tickets'] = array_slice($ticketState['tickets'], 0, 300);
}

save_hub_tickets($ticketState);

// Disparar alertas a WhatsApp (n8n) y Correo Electrónico del Fabricante
$dispatchResults = dispatch_hub_ticket_notifications($ticket, $ticketState['settings'] ?? []);

echo json_encode([
    'success'       => true,
    'message'       => 'Avería registrada en Inbox Cloud Hub y notificaciones disparadas.',
    'hub_id'        => $ticket['hub_id'],
    'notifications' => $dispatchResults,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

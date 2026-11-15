<?php
/**
 * ============================================================================
 * INBOX CLOUD HUB — ADMINISTRADOR CENTRAL DE PLATAFORMAS Y LICENCIAS (SaaS)
 * Creador: Renan Galván (renangalvan.net)
 * ============================================================================
 */
require_once __DIR__ . '/config.php';

$hubCfg = get_hub_config();
$validKeys = array_map('strtolower', array_merge(
    $hubCfg['fallback_keys'] ?? ['RenanDev2026', 'unela2026', 'cefi2026'],
    ['renandev2026', 'unela2026', 'cefi2026', 'admin2026']
));

// Procesar Login / Logout
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hub_action']) && $_POST['hub_action'] === 'login') {
    $pass = trim($_POST['master_password'] ?? '');
    if (in_array(strtolower($pass), $validKeys, true) || (!empty($hubCfg['admin_pass_hash']) && password_verify($pass, $hubCfg['admin_pass_hash']))) {
        $_SESSION['inbox_hub_authenticated'] = true;
        $_SESSION['inbox_hub_login_time'] = time();
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Clave Maestra incorrecta. Acceso exclusivo para Renan Galván.';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['inbox_hub_authenticated']);
    header('Location: index.php');
    exit;
}

$isAuthenticated = !empty($_SESSION['inbox_hub_authenticated']);

// Cargar plataformas y árbol maestro
$platforms = get_registered_platforms();
$selectedId = strtolower(trim($_GET['client'] ?? $_POST['client_id'] ?? array_key_first($platforms)));
if (!isset($platforms[$selectedId])) {
    $selectedId = array_key_first($platforms);
}
$autoCount = 0;
$tree = get_effective_menu_tree($platforms[$selectedId], $autoCount);

// Exportar JSON de una plataforma
if ($isAuthenticated && isset($_GET['export_client'])) {
    $cid = strtolower(trim($_GET['export_client']));
    if (isset($platforms[$cid])) {
        $p = $platforms[$cid];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="licencia_menu_' . $cid . '_' . date('Ymd_His') . '.json"');
        echo json_encode([
            'sistema'         => 'Inbox Cloud Hub - Renan Galvan',
            'client_id'       => $p['id'],
            'nombre'          => $p['nombre'],
            'export_at'       => date('Y-m-d H:i:s'),
            'estado_licencia' => $p['estado_licencia'] ?? 'activo',
            'mensaje_aviso'   => $p['mensaje_aviso'] ?? '',
            'flags'           => $p['flags'] ?? get_default_flags_for_profile('full_suite'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$selectedId = strtolower(trim($_GET['client'] ?? $_POST['client_id'] ?? array_key_first($platforms)));
if (!isset($platforms[$selectedId])) {
    $selectedId = array_key_first($platforms);
}

$flashSuccess = '';
$flashWarning = '';
$flashError   = '';
$ticketState  = load_hub_tickets();

// Procesar acciones del Hub autenticado
if ($isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['hub_action'] ?? '';

    // A. Guardar configuración de alertas omnicanal (WhatsApp n8n + Email fijo interno a renangalvan@gmail.com)
    if ($action === 'save_ticket_settings') {
        $ticketState['settings'] = [
            'admin_email'     => INBOX_CREATOR_EMAIL,
            'admin_whatsapp'  => trim($_POST['admin_whatsapp'] ?? ''),
            'n8n_webhook_url' => trim($_POST['n8n_webhook_url'] ?? ''),
            'notify_email'    => !empty($_POST['notify_email']),
            'notify_whatsapp' => !empty($_POST['notify_whatsapp']),
        ];
        save_hub_tickets($ticketState);
        $flashSuccess = "<strong>¡Canales de Alerta Actualizados!</strong> Configuración de WhatsApp (n8n) guardada. El correo del proveedor está fijado internamente a <code>" . INBOX_CREATOR_EMAIL . "</code>.";
    }

    // B. Probar disparo de alerta a WhatsApp (n8n) y Correo
    if ($action === 'test_ticket_alert') {
        $sampleTicket = [
            'id'             => 'TEST-' . rand(100, 999),
            'client_id'      => $selectedId,
            'client_siglas'  => $platforms[$selectedId]['siglas'] ?? 'CEFI',
            'client_nombre'  => $platforms[$selectedId]['nombre'] ?? 'Plataforma Inbox',
            'titulo'         => 'Prueba de Alerta Omnicanal desde Inbox Cloud Hub',
            'etiqueta'       => '🔴 Error Crítico / Prueba',
            'prioridad'      => 'urgente',
            'problema'       => 'Este es un mensaje de prueba para verificar la recepción instantánea en WhatsApp (vía n8n) y Correo Electrónico.',
            'url_origen'     => 'https://hub.renangalvan.net',
            'evidencia_url'  => '',
            'usuario_nombre' => 'Renan Galván (Fabricante)',
            'usuario_email'  => $ticketState['settings']['admin_email'] ?? 'renan@renangalvan.net',
            'usuario_rol'    => 'SuperAdmin',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];
        $resAlert = dispatch_hub_ticket_notifications($sampleTicket, $ticketState['settings']);
        $flashSuccess = "<strong>Prueba de Alerta Disparada:</strong> WhatsApp n8n: " . ($resAlert['whatsapp_n8n'] ? '✅ Enviado' : '⚠️ Sin Webhook / No conectado') . " | Correo: " . ($resAlert['email'] ? '✅ Enviado' : '⚠️ Pendiente');
    }

    // C. Actualizar estado o solución de una avería en el Hub
    if ($action === 'update_ticket_status') {
        $hubId     = trim($_POST['hub_id'] ?? '');
        $newStatus = trim($_POST['estado'] ?? 'solucionado');
        $solucion  = trim($_POST['solucion'] ?? '');
        foreach ($ticketState['tickets'] as &$tk) {
            if (($tk['hub_id'] ?? '') === $hubId) {
                $tk['estado']   = $newStatus;
                $tk['solucion'] = $solucion;
                $tk['fecha_solucion'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($tk);
        save_hub_tickets($ticketState);
        $flashSuccess = "Estado de la avería <strong>" . htmlspecialchars($hubId) . "</strong> actualizado a <strong>" . strtoupper(htmlspecialchars($newStatus)) . "</strong>.";
    }

    // 0. Escanear en vivo nuevos menús / sub-ítems creados en el código de la plataforma cliente
    if ($action === 'discover_tree') {
        $cid = strtolower(trim($_POST['client_id'] ?? $selectedId));
        if (isset($platforms[$cid])) {
            $selectedId = $cid;
            $discRes = discover_tree_from_platform($platforms[$cid]);
            save_registered_platforms($platforms);
            $tree = get_effective_menu_tree($platforms[$cid], $autoCount);
            if ($discRes['success']) {
                $flashSuccess = "<strong>¡Auto-Descubrimiento Completado!</strong> " . htmlspecialchars($discRes['message']);
            } else {
                $flashWarning = htmlspecialchars($discRes['message']);
            }
        }
    }

    // 1. Crear o editar plataforma cliente
    if ($action === 'save_platform_meta') {
        $rawId   = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['plat_id'] ?? ''));
        $nombre  = trim($_POST['plat_nombre'] ?? '');
        $siglas  = strtoupper(trim($_POST['plat_siglas'] ?? ''));
        $motor   = trim($_POST['plat_motor'] ?? 'Laravel 11 (Inbox 2.0)');
        $url     = rtrim(trim($_POST['plat_url'] ?? ''), '/');
        $syncUrl = trim($_POST['plat_sync_url'] ?? '');
        $color   = trim($_POST['plat_color'] ?? '#2563eb');
        $plan    = trim($_POST['plat_plan'] ?? 'Plan Personalizado');

        if ($rawId !== '' && $nombre !== '' && $url !== '') {
            if ($syncUrl === '') {
                $syncUrl = (stripos($motor, 'laravel') !== false)
                    ? $url . '/api/license-sync'
                    : $url . '/api/license_sync.php';
            }
            if (!isset($platforms[$rawId])) {
                $platforms[$rawId] = [
                    'id'              => $rawId,
                    'nombre'          => $nombre,
                    'siglas'          => $siglas ?: strtoupper($rawId),
                    'motor'           => $motor,
                    'url'             => $url,
                    'sync_endpoint'   => $syncUrl,
                    'estado_licencia' => 'activo',
                    'mensaje_aviso'   => '',
                    'color'           => $color,
                    'plan_comercial'  => $plan,
                    'ultima_sync'     => null,
                    'ultimo_resultado'=> null,
                    'flags'           => get_default_flags_for_profile('full_suite'),
                ];
            } else {
                $platforms[$rawId]['nombre']         = $nombre;
                $platforms[$rawId]['siglas']         = $siglas ?: strtoupper($rawId);
                $platforms[$rawId]['motor']          = $motor;
                $platforms[$rawId]['url']            = $url;
                $platforms[$rawId]['sync_endpoint']  = $syncUrl;
                $platforms[$rawId]['color']          = $color;
                $platforms[$rawId]['plan_comercial'] = $plan;
            }
            save_registered_platforms($platforms);
            $selectedId = $rawId;
            $flashSuccess = "Plataforma <strong>" . htmlspecialchars($nombre) . "</strong> guardada correctamente en el Hub.";
        } else {
            $flashError = "Por favor complete el ID, Nombre y URL Base de la plataforma.";
        }
    }

    // 2. Guardar Árbol de Menús + Estado de Licencia (y opcionalmente Sincronizar por HTTPS)
    if ($action === 'save_tree' || $action === 'save_and_sync') {
        $cid = strtolower(trim($_POST['client_id'] ?? $selectedId));
        if (isset($platforms[$cid])) {
            $selectedId = $cid;
            $postedMenus = isset($_POST['menus']) && is_array($_POST['menus']) ? $_POST['menus'] : [];
            $postedItems = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

            $newFlags = [];
            foreach ($tree as $menuKey => $menuMeta) {
                $newFlags[$menuKey] = !empty($postedMenus[$menuKey]);
                if (!empty($menuMeta['children'])) {
                    foreach ($menuMeta['children'] as $itemKey => $itemMeta) {
                        $newFlags[$itemKey] = !empty($postedItems[$itemKey]);
                    }
                }
            }

            // Sincronizar banderas macro de compatibilidad
            $newFlags['boletas_matricula'] = !empty($newFlags['item.generar_boleta']) || !empty($newFlags['item.gestion_boletas']) || !empty($newFlags['item.parametros_pagos']);
            $newFlags['finanzas']          = !empty($newFlags['item.estado_cuenta']) || !empty($newFlags['item.morosidad']);
            $newFlags['whatsapp_n8n']      = !empty($newFlags['item.campanas_whatsapp']) || !empty($newFlags['item.suite_whatsapp']);

            $platforms[$cid]['flags']           = $newFlags;
            $platforms[$cid]['estado_licencia'] = in_array($_POST['estado_licencia'] ?? 'activo', ['activo', 'aviso_cobro', 'suspendido'], true)
                                                  ? $_POST['estado_licencia']
                                                  : 'activo';
            $platforms[$cid]['mensaje_aviso']   = trim($_POST['mensaje_aviso'] ?? '');

            if ($action === 'save_and_sync') {
                $syncRes = push_license_to_platform($platforms[$cid]);
                save_registered_platforms($platforms);
                $tree = get_effective_menu_tree($platforms[$cid], $autoCount);

                if ($syncRes['success']) {
                    $flashSuccess = "<strong>¡Sincronización Remota Exitosa!</strong> " . htmlspecialchars($syncRes['message']);
                } else {
                    $flashWarning = "<strong>Guardado en el Hub Central</strong>, pero la sincronización directa reportó: " . htmlspecialchars($syncRes['message']) . " (Verifique que los últimos cambios estén desplegados en el servidor destino o descargue el JSON).";
                }
            } else {
                save_registered_platforms($platforms);
                $flashSuccess = "Configuración de <strong>" . htmlspecialchars($platforms[$cid]['siglas']) . "</strong> guardada en el Hub Central.";
            }
        }
    }
}

$currentPlatform = $platforms[$selectedId];
$currentFlags    = $currentPlatform['flags'] ?? get_default_flags_for_profile('full_suite');

// Mapa de colores hex para las tarjetas del árbol
$bsColors = [
    'primary'   => '#2563eb',
    'info'      => '#0ea5e9',
    'success'   => '#10b981',
    'warning'   => '#f59e0b',
    'danger'    => '#ef4444',
    'secondary' => '#64748b',
];

// Contadores del árbol para la plataforma seleccionada
$totalMenus   = count($tree);
$activeMenus  = 0;
$totalItems   = 0;
$activeItems  = 0;
foreach ($tree as $mKey => $mMeta) {
    $mOn = !empty($currentFlags[$mKey]);
    if ($mOn) $activeMenus++;
    foreach ($mMeta['children'] ?? [] as $iKey => $iMeta) {
        $totalItems++;
        if ($mOn && !empty($currentFlags[$iKey])) {
            $activeItems++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox Cloud Hub | Administrador Central de Plataformas — renangalvan.net</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --hub-bg: #f1f5f9;
            --hub-dark: #0f172a;
            --hub-accent: #2563eb;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--hub-bg);
            color: #0f172a;
            padding-bottom: 110px;
        }
        .hub-navbar {
            background: linear-gradient(135deg, #090d16 0%, #0f172a 60%, #1e293b 100%);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 0.9rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.25);
        }
        .hub-brand-badge {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            font-weight: 800;
            font-size: 0.72rem;
            letter-spacing: 0.8px;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .platform-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.15rem 1.25rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }
        .platform-card:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            color: inherit;
        }
        .platform-card.active {
            border-color: var(--plat-color, #2563eb);
            box-shadow: 0 12px 28px -6px rgba(37, 99, 235, 0.18);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        .platform-card.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--plat-color, #2563eb);
        }
        .plat-avatar {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
            flex-shrink: 0;
        }
        .status-pill {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-activo { background: #dcfce7; color: #15803d; }
        .status-aviso_cobro { background: #fef3c7; color: #b45309; }
        .status-suspendido { background: #fee2e2; color: #b91c1c; }

        .tree-card {
            background: #fff;
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .tree-card.menu-active {
            border-color: #cbd5e1;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        }
        .tree-card.menu-disabled {
            background: #f8fafc;
            border-color: #e2e8f0;
            opacity: 0.72;
        }
        .tree-card-header {
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .tree-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            flex-shrink: 0;
        }
        .tree-branch-list {
            padding: 0.85rem 1.25rem 1rem 2.15rem;
            position: relative;
        }
        .tree-branch-list::before {
            content: '';
            position: absolute;
            top: 0.5rem;
            bottom: 1.2rem;
            left: 1.35rem;
            width: 2px;
            background: #cbd5e1;
        }
        .tree-item-row {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.35rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .tree-item-row::before {
            content: '';
            position: absolute;
            left: -0.8rem;
            top: 50%;
            width: 0.8rem;
            height: 2px;
            background: #cbd5e1;
        }
        .tree-item-row:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }
        .tree-item-row.item-unchecked {
            background: #f1f5f9;
            color: #94a3b8;
            border-style: dashed;
        }
        .tree-item-row.item-unchecked .item-title {
            text-decoration: line-through;
        }
        .form-check-input.menu-parent-check {
            width: 2.6em;
            height: 1.35em;
            cursor: pointer;
        }
        .form-check-input.item-child-check {
            width: 1.25em;
            height: 1.25em;
            cursor: pointer;
        }
        .sticky-save-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255,255,255,0.12);
            padding: 0.9rem 1.5rem;
            color: #fff;
            box-shadow: 0 -10px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<!-- NAVBAR SUPERIOR -->
<nav class="hub-navbar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4 fw-bolder text-white" style="letter-spacing:-0.8px;">inbox<span style="color:#38bdf8;">.hub</span></span>
            <span class="hub-brand-badge">Cloud Control Center</span>
        </div>
        <span class="text-white-50 d-none d-md-inline">|</span>
        <span class="text-white-50 small d-none d-md-inline">
            <i class="bi bi-globe2 me-1 text-info"></i>renangalvan.net — Administrador Maestro Multi-Plataforma
        </span>
    </div>
    <?php if ($isAuthenticated): ?>
    <?php
        $openTicketsCount = 0;
        foreach ($ticketState['tickets'] ?? [] as $tkItem) {
            if (($tkItem['estado'] ?? 'reportado') !== 'solucionado') {
                $openTicketsCount++;
            }
        }
    ?>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm <?= $openTicketsCount > 0 ? 'btn-danger' : 'btn-outline-warning' ?> rounded-pill px-3 fw-bold position-relative" data-bs-toggle="modal" data-bs-target="#modalCentroAverias">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>Centro de Averías
            <span class="badge bg-light text-dark rounded-pill ms-1"><?= $openTicketsCount ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalPlatformMeta" onclick="openNewPlatformModal()">
            <i class="bi bi-plus-circle me-1"></i>Añadir Plataforma / Cliente
        </button>
        <a href="index.php?logout=1" class="btn btn-sm btn-outline-light rounded-pill px-3">
            <i class="bi bi-lock-fill me-1"></i>Bloquear Hub
        </a>
    </div>
    <?php endif; ?>
</nav>

<?php if (!$isAuthenticated): ?>
<!-- PANTALLA DE LOGIN MAESTRO -->
<div class="container py-5">
    <div class="row justify-content-center mt-4">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:68px;height:68px;background:rgba(56,189,248,0.15);border:1px solid rgba(56,189,248,0.4);">
                        <i class="bi bi-diagram-3-fill fs-2 text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Inbox Cloud Hub</h4>
                    <p class="small text-white-50 mb-0">Administrador Central de Plataformas Instaladas — renangalvan.net</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($loginError): ?>
                        <div class="alert alert-danger small py-2 rounded-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= htmlspecialchars($loginError) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="hub_action" value="login">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Clave Maestra del Creador</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-key-fill text-primary"></i></span>
                                <input type="password" name="master_password" class="form-control" placeholder="Ingrese su clave maestra..." required autofocus>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 shadow-sm">
                            <i class="bi bi-shield-lock-fill me-1"></i>Ingresar al Centro de Control
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>

<!-- PANEL CENTRAL MULTI-PLATAFORMA -->
<div class="container-fluid px-4 pt-4">

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
            <div><?= $flashSuccess ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashWarning): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
            <div><?= $flashWarning ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-x-octagon-fill fs-4 text-danger"></i>
            <div><?= $flashError ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SELECTOR DE PLATAFORMAS INSTALADAS -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-uppercase text-secondary small mb-0" style="letter-spacing:0.7px;">
            <i class="bi bi-hdd-network me-1 text-primary"></i>1. Seleccione la Plataforma / Cliente a Administrar
        </h6>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= count($platforms) ?> plataformas registradas</span>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($platforms as $pid => $plat):
            $isSelected = ($pid === $selectedId);
            $st = $plat['estado_licencia'] ?? 'activo';
            $stLabel = $st === 'activo' ? 'Licencia Activa' : ($st === 'aviso_cobro' ? 'Aviso de Cobro' : 'Suspendida');
            $pFlags = $plat['flags'] ?? [];
            $enabledMenusCount = 0;
            foreach ($tree as $mk => $mm) {
                if (!empty($pFlags[$mk])) $enabledMenusCount++;
            }
            $platUrl = $plat['url'] ?? '';
            $platMotor = $plat['motor'] ?? 'Inbox 2.0';
        ?>
        <div class="col-md-6 col-xl-4">
            <a href="index.php?client=<?= urlencode($pid) ?>" class="platform-card <?= $isSelected ? 'active' : '' ?>" style="--plat-color: <?= htmlspecialchars($plat['color'] ?? '#2563eb') ?>;">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="plat-avatar shadow-sm" style="background: <?= htmlspecialchars($plat['color'] ?? '#2563eb') ?>;">
                            <i class="bi bi-building-check"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($plat['siglas']) ?></h5>
                                <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><?= htmlspecialchars($platMotor) ?></span>
                            </div>
                            <div class="text-muted small fw-medium text-truncate" style="max-width: 240px;">
                                <?= htmlspecialchars($plat['nombre']) ?>
                            </div>
                        </div>
                    </div>
                    <span class="status-pill status-<?= htmlspecialchars($st) ?>">
                        <i class="bi bi-circle-fill" style="font-size:0.45rem;"></i><?= $stLabel ?>
                    </span>
                </div>
                <hr class="my-2 opacity-25">
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span class="text-truncate" style="max-width: 230px;">
                        <i class="bi bi-link-45deg text-primary"></i><?= htmlspecialchars($platUrl) ?>
                    </span>
                    <span class="fw-bold text-dark">
                        <?= $enabledMenusCount ?>/<?= $totalMenus ?> menús
                    </span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FORMULARIO MAESTRO DE LA PLATAFORMA SELECCIONADA -->
    <form method="POST" id="hubMasterForm">
        <input type="hidden" name="client_id" value="<?= htmlspecialchars($selectedId) ?>">
        <input type="hidden" name="hub_action" id="hubActionInput" value="save_and_sync">

        <!-- CABECERA DE PLATAFORMA SELECCIONADA + ESTADO DE SUSCRIPCIÓN -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="p-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
                 style="background: linear-gradient(135deg, #0f172a 0%, <?= htmlspecialchars($currentPlatform['color'] ?? '#1e3a8a') ?> 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="plat-avatar bg-white bg-opacity-10 border border-white border-opacity-25" style="width:56px;height:56px;font-size:1.6rem;">
                        <i class="bi bi-hdd-rack-fill"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-info text-dark fw-bold">ADMINISTRANDO PLATAFORMA</span>
                            <span class="badge bg-dark bg-opacity-50 text-white-50 font-monospace">client_id: <?= htmlspecialchars($selectedId) ?></span>
                        </div>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($currentPlatform['nombre']) ?></h3>
                        <div class="small text-white-50 mt-1">
                            <i class="bi bi-globe me-1"></i>Webhook Receptor: <code class="text-info"><?= htmlspecialchars($currentPlatform['sync_endpoint'] ?? '') ?></code>
                            <?php if (!empty($currentPlatform['ultima_sync'])): ?>
                                <span class="ms-2">| <i class="bi bi-clock-history me-1"></i>Último Sync: <?= htmlspecialchars($currentPlatform['ultima_sync']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-warning fw-bold rounded-pill px-3 shadow-sm"
                            onclick="document.getElementById('hubActionInput').value='discover_tree';"
                            title="Escanea en vivo navbar.php / app.blade.php del cliente y agrega automáticamente cualquier menú nuevo">
                        <i class="bi bi-arrow-repeat me-1"></i>Detectar Nuevos Menús
                    </button>
                    <a href="<?= htmlspecialchars($currentPlatform['url'] ?? '#') ?>" target="_blank" class="btn btn-sm btn-light fw-bold rounded-pill px-3">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Abrir <?= htmlspecialchars($currentPlatform['siglas']) ?>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3"
                            data-bs-toggle="modal" data-bs-target="#modalPlatformMeta"
                            onclick='openEditPlatformModal(<?= json_encode([
                                "id" => $currentPlatform["id"],
                                "nombre" => $currentPlatform["nombre"],
                                "siglas" => $currentPlatform["siglas"],
                                "motor" => $currentPlatform["motor"] ?? "PHP Nativo (Inbox 2.0)",
                                "url" => $currentPlatform["url"] ?? "",
                                "sync_endpoint" => $currentPlatform["sync_endpoint"] ?? "",
                                "color" => $currentPlatform["color"] ?? "#2563eb",
                                "plan" => $currentPlatform["plan_comercial"] ?? ""
                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-gear me-1"></i>Configurar URL / Endpoint
                    </button>
                    <a href="index.php?export_client=<?= urlencode($selectedId) ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                        <i class="bi bi-download me-1"></i>Descargar JSON
                    </a>
                </div>
            </div>

            <!-- Barra de Estado de Licencia (Kill-Switch / Aviso de Cobro) + Contadores -->
            <div class="card-body bg-white p-3 px-4 border-top">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                            <i class="bi bi-shield-check me-1 text-primary"></i>Estado de Suscripción / Licencia Remota
                        </label>
                        <select name="estado_licencia" class="form-select form-select-sm fw-bold">
                            <option value="activo" <?= ($currentPlatform['estado_licencia'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>🟢 ACTIVO NORMAL — Operación al 100% según árbol</option>
                            <option value="aviso_cobro" <?= ($currentPlatform['estado_licencia'] ?? '') === 'aviso_cobro' ? 'selected' : '' ?>>🟡 AVISO DE FACTURACIÓN — Muestra cintillo superior de pago pendiente</option>
                            <option value="suspendido" <?= ($currentPlatform['estado_licencia'] ?? '') === 'suspendido' ? 'selected' : '' ?>>🔴 SUSPENDIDO (Kill-Switch) — Bloquea acceso al cliente por mora/mantenimiento</option>
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                            <i class="bi bi-chat-square-quote me-1 text-warning"></i>Mensaje de Aviso o Suspensión (Opcional)
                        </label>
                        <input type="text" name="mensaje_aviso" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($currentPlatform['mensaje_aviso'] ?? '') ?>"
                               placeholder="Ej: Recordatorio: Renovación anual de licencia pendiente de pago...">
                    </div>
                    <div class="col-lg-3 text-lg-end">
                        <div class="d-inline-flex gap-2 bg-light p-2 px-3 rounded-3 border">
                            <div>
                                <div class="text-muted" style="font-size:0.68rem;font-weight:700;">MENÚS ACTIVOS</div>
                                <div class="fw-bold fs-5 text-primary"><span id="counterMenus"><?= $activeMenus ?></span> / <?= $totalMenus ?></div>
                            </div>
                            <div class="vr mx-1"></div>
                            <div>
                                <div class="text-muted" style="font-size:0.68rem;font-weight:700;">SUB-ÍTEMS VISIBLES</div>
                                <div class="fw-bold fs-5 text-success"><span id="counterItems"><?= $activeItems ?></span> / <?= $totalItems ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARRA DE HERRAMIENTAS DEL ÁRBOL (BÚSQUEDA + PAQUETES RÁPIDOS) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 440px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="treeSearchInput" class="form-control border-start-0" placeholder="Buscar menú o sub-ítem (ej: Actas, Morosidad, Automatizaciones, Tutor AI)...">
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-bold me-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Presets Comerciales:</span>
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold rounded-pill" onclick="applyPreset('all')">
                        <i class="bi bi-check2-all me-1"></i>Full Suite (100%)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill" onclick="applyPreset('cefi_standard')">
                        <i class="bi bi-mortarboard me-1"></i>Académico + Registro + Finanzas + Config
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold rounded-pill" onclick="applyPreset('basic')">
                        <i class="bi bi-box me-1"></i>Básico (Cursos + Estudiantes)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold rounded-pill" onclick="applyPreset('none')">
                        <i class="bi bi-x-lg me-1"></i>Desmarcar Todo
                    </button>
                </div>
            </div>
        </div>

        <!-- GRID DEL ÁRBOL JERÁRQUICO DE MENÚS Y SUB-ÍTEMS -->
        <div class="row g-4" id="treeCardsContainer">
            <?php foreach ($tree as $menuKey => $menuMeta):
                $isMenuChecked = !empty($currentFlags[$menuKey]);
                $items = $menuMeta['children'] ?? [];
                $itemCount = count($items);
                $hexColor = $bsColors[$menuMeta['color'] ?? 'primary'] ?? '#2563eb';
            ?>
            <div class="col-lg-6 col-xxl-4 tree-card-col" data-menu-key="<?= htmlspecialchars($menuKey) ?>">
                <div class="tree-card h-100 <?= $isMenuChecked ? 'menu-active' : 'menu-disabled' ?>" id="card_<?= htmlspecialchars($menuKey) ?>">
                    <!-- CABECERA DEL MENÚ PADRE -->
                    <div class="tree-card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="tree-icon-box shadow-sm" style="background: <?= htmlspecialchars($hexColor) ?>;">
                                <i class="bi <?= htmlspecialchars($menuMeta['icono'] ?? 'bi-grid') ?>"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($menuMeta['nombre']) ?></h6>
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary" style="font-size:0.68rem;">
                                        <?= $itemCount ?> sub-ítems
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($menuMeta['descripcion'] ?? '') ?></div>
                            </div>
                        </div>

                        <div class="form-check form-switch m-0">
                            <input class="form-check-input menu-parent-check"
                                   type="checkbox"
                                   role="switch"
                                   id="menu_<?= htmlspecialchars($menuKey) ?>"
                                   name="menus[<?= htmlspecialchars($menuKey) ?>]"
                                   value="1"
                                   data-menu-key="<?= htmlspecialchars($menuKey) ?>"
                                   <?= $isMenuChecked ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- CUERPO DEL ÁRBOL: RAMAS E ÍTEMS HIJOS -->
                    <div class="px-3 pt-2 pb-1 d-flex justify-content-between align-items-center border-bottom bg-light bg-opacity-50">
                        <span class="text-muted" style="font-size:0.72rem;">
                            <i class="bi bi-diagram-2 me-1"></i>Marque o desmarque sub-ítems individuales:
                        </span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 me-2" style="font-size:0.72rem;" onclick="toggleAllChildren('<?= htmlspecialchars($menuKey) ?>', true)">Todos</button>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none text-secondary p-0" style="font-size:0.72rem;" onclick="toggleAllChildren('<?= htmlspecialchars($menuKey) ?>', false)">Ninguno</button>
                        </div>
                    </div>

                    <div class="tree-branch-list">
                        <?php foreach ($items as $itemKey => $itemMeta):
                            $isItemChecked = !empty($currentFlags[$itemKey]);
                        ?>
                        <label class="tree-item-row <?= (!$isItemChecked || !$isMenuChecked) ? 'item-unchecked' : '' ?>"
                               for="item_<?= htmlspecialchars(str_replace('.', '__', $itemKey)) ?>"
                               data-search-text="<?= htmlspecialchars(strtolower($menuMeta['nombre'] . ' ' . $itemMeta['nombre'] . ' ' . $itemKey)) ?>">
                            <div class="d-flex align-items-center gap-2 pe-2">
                                <i class="bi <?= htmlspecialchars($itemMeta['icono'] ?? 'bi-circle') ?>" style="font-size:0.95rem;"></i>
                                <div>
                                    <div class="item-title fw-semibold" style="font-size:0.83rem; line-height:1.25;">
                                        <?= htmlspecialchars($itemMeta['nombre']) ?>
                                    </div>
                                    <code class="text-muted" style="font-size:0.65rem;"><?= htmlspecialchars($itemKey) ?></code>
                                </div>
                            </div>
                            <input class="form-check-input item-child-check m-0"
                                   type="checkbox"
                                   id="item_<?= htmlspecialchars(str_replace('.', '__', $itemKey)) ?>"
                                   name="items[<?= htmlspecialchars($itemKey) ?>]"
                                   value="1"
                                   data-parent-menu="<?= htmlspecialchars($menuKey) ?>"
                                   <?= $isItemChecked ? 'checked' : '' ?>>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- BARRA FLOTANTE INFERIOR DE GUARDADO Y SINCRONIZACIÓN REMOTA -->
        <div class="sticky-save-bar">
            <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="bi bi-broadcast-pin me-1"></i><?= htmlspecialchars($currentPlatform['siglas']) ?>
                    </span>
                    <div class="small">
                        <div class="fw-bold">Sincronización Directa HTTPS (Firmada con HMAC-SHA256)</div>
                        <div class="text-white-50" style="font-size:0.75rem;">
                            Al pulsar <strong>Guardar y Sincronizar</strong>, el árbol se aplica inmediatamente en <code><?= htmlspecialchars($currentPlatform['url'] ?? '') ?></code>.
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-outline-light rounded-pill px-3 fw-semibold"
                            onclick="document.getElementById('hubActionInput').value='save_tree';">
                        <i class="bi bi-hdd me-1"></i>Guardar Solo en Hub
                    </button>
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow"
                            onclick="document.getElementById('hubActionInput').value='save_and_sync';">
                        <i class="bi bi-cloud-upload-fill me-2"></i>Guardar y Sincronizar con <?= htmlspecialchars($currentPlatform['siglas']) ?> Ahora
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL PARA AÑADIR / EDITAR PLATAFORMA CLIENTE -->
<div class="modal fade" id="modalPlatformMeta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="hub_action" value="save_platform_meta">
            <div class="modal-header bg-dark text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalPlatformTitle">
                    <i class="bi bi-hdd-network me-2 text-info"></i>Registrar / Configurar Plataforma
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">ID Único (slug) *</label>
                        <input type="text" name="plat_id" id="plat_id" class="form-control" placeholder="ej: unela, cefi, uisil" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Siglas Cortas *</label>
                        <input type="text" name="plat_siglas" id="plat_siglas" class="form-control" placeholder="ej: UNELA" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Color</label>
                        <input type="color" name="plat_color" id="plat_color" class="form-control form-control-color w-100" value="#2563eb">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Nombre de la Institución / Cliente *</label>
                        <input type="text" name="plat_nombre" id="plat_nombre" class="form-control" placeholder="Ej: Universidad Evangélica de las Américas" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Motor / Arquitectura de la Plataforma</label>
                        <select name="plat_motor" id="plat_motor" class="form-select">
                            <option value="Laravel 11 (Inbox 2.0)" selected>Laravel 11 (Estándar para nuevas plataformas — tipo inbox_cefi)</option>
                            <option value="PHP Nativo (Inbox 2.0)">PHP Nativo (Legacy — tipo bpm_unela)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">URL Base de la Plataforma *</label>
                        <input type="url" name="plat_url" id="plat_url" class="form-control" placeholder="https://midominio.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Endpoint Receptor de Sincronización (Webhook)</label>
                        <input type="url" name="plat_sync_url" id="plat_sync_url" class="form-control font-monospace small" placeholder="Se autocompleta si lo deja en blanco">
                        <div class="form-text small">
                            Para PHP Nativo: <code>.../api/license_sync.php</code> | Para Laravel: <code>.../api/license-sync</code>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Plan Comercial / Notas</label>
                        <input type="text" name="plat_plan" id="plat_plan" class="form-control" placeholder="Ej: Plan Académico + Finanzas + IA">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="bi bi-save me-1"></i>Guardar Plataforma
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php if ($isAuthenticated): ?>
<!-- MODAL: CENTRO GLOBAL DE AVERÍAS MULTI-PLATAFORMA + CONFIGURACIÓN OMNICANAL (WHATSAPP n8n & EMAIL) -->
<div class="modal fade" id="modalCentroAverias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #0f172a 0%, #7f1d1d 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-exclamation-octagon-fill text-warning me-2"></i>Centro Global de Averías &amp; Alertas Omnicanal (n8n WhatsApp + Correo)
                    </h5>
                    <small class="text-white-50">Recepción centralizada en tiempo real de reportes con Evidencia Gráfica desde todas las plataformas instaladas.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 1. Configuración de Alertas Directas (WhatsApp vía n8n + Email) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-whatsapp text-success me-1"></i>
                                <i class="bi bi-envelope-at-fill text-primary me-1"></i>
                                Canales de Notificación Instantánea al Creador
                            </h6>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="hub_action" value="test_ticket_alert">
                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                                    <i class="bi bi-send-check me-1"></i>Probar Alerta WhatsApp / Correo
                                </button>
                            </form>
                        </div>
                        <form method="POST" class="row g-2 align-items-end">
                            <input type="hidden" name="hub_action" value="save_ticket_settings">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-secondary mb-1">
                                    <i class="bi bi-lock-fill text-success me-1"></i>Correo Interno del Proveedor (Fijo)
                                </label>
                                <input type="email" class="form-control form-control-sm bg-light fw-bold text-success" value="<?= htmlspecialchars(INBOX_CREATOR_EMAIL) ?>" readonly title="Definido internamente en el núcleo del sistema">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-secondary mb-1">Tu WhatsApp</label>
                                <input type="text" name="admin_whatsapp" class="form-control form-control-sm" value="<?= htmlspecialchars($ticketState['settings']['admin_whatsapp'] ?? '+50688888888') ?>" placeholder="+506...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary mb-1">URL Webhook n8n (Flujo WhatsApp / Email)</label>
                                <input type="url" name="n8n_webhook_url" class="form-control form-control-sm" value="<?= htmlspecialchars($ticketState['settings']['n8n_webhook_url'] ?? '') ?>" placeholder="https://tu-n8n.com/webhook/inbox-averias">
                            </div>
                            <div class="col-md-3 d-flex align-items-center justify-content-end gap-2">
                                <div class="form-check form-switch me-1">
                                    <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="chkWa" <?= !empty($ticketState['settings']['notify_whatsapp']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small fw-semibold" for="chkWa">n8n</label>
                                </div>
                                <div class="form-check form-switch me-2">
                                    <input class="form-check-input" type="checkbox" name="notify_email" value="1" id="chkEm" <?= !empty($ticketState['settings']['notify_email']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small fw-semibold" for="chkEm">Email</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                    <i class="bi bi-save me-1"></i>Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 2. Listado Global de Averías Reportadas -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-danger me-2"></i>Bandeja Unificada de Averías Reportadas</h6>
                        <span class="badge bg-dark rounded-pill"><?= count($ticketState['tickets'] ?? []) ?> reportes totales</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Ticket / Plataforma</th>
                                    <th>Etiqueta &amp; Prioridad</th>
                                    <th>Problema &amp; URL Origen</th>
                                    <th>Evidencia Gráfica</th>
                                    <th>Estado / Gestión</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ticketState['tickets'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                                            No hay averías reportadas en este momento. Todas las plataformas operan con normalidad.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ticketState['tickets'] as $tk): ?>
                                        <?php
                                            $prio = strtolower($tk['prioridad'] ?? 'alta');
                                            $prioBadge = $prio === 'urgente' ? 'bg-danger' : ($prio === 'alta' ? 'bg-warning text-dark' : 'bg-info text-dark');
                                            $est = $tk['estado'] ?? 'reportado';
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($tk['hub_id'] ?? ('#' . ($tk['id'] ?? ''))) ?></div>
                                                <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($tk['client_siglas'] ?? $tk['client_id'] ?? 'INBOX') ?></span>
                                                <div class="small text-muted mt-1"><?= htmlspecialchars($tk['fecha_creacion'] ?? '') ?></div>
                                                <div class="small text-secondary"><i class="bi bi-person me-1"></i><?= htmlspecialchars($tk['usuario_nombre'] ?? '') ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($tk['etiqueta'] ?? 'General') ?></div>
                                                <span class="badge <?= $prioBadge ?> rounded-pill text-uppercase" style="font-size:0.68rem;"><?= htmlspecialchars($prio) ?></span>
                                            </td>
                                            <td style="max-width: 340px;">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($tk['titulo'] ?? '') ?></div>
                                                <div class="small text-secondary text-wrap"><?= nl2br(htmlspecialchars($tk['problema'] ?? '')) ?></div>
                                                <?php if (!empty($tk['url_origen'])): ?>
                                                    <div class="mt-1">
                                                        <a href="<?= htmlspecialchars($tk['url_origen']) ?>" target="_blank" class="small text-primary text-decoration-none">
                                                            <i class="bi bi-link-45deg"></i><?= htmlspecialchars($tk['url_origen']) ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($tk['evidencia_url'])): ?>
                                                    <a href="<?= htmlspecialchars($tk['evidencia_url']) ?>" target="_blank" class="d-inline-block text-decoration-none">
                                                        <img src="<?= htmlspecialchars($tk['evidencia_url']) ?>" alt="Evidencia" style="width:76px;height:52px;object-fit:cover;border-radius:8px;border:2px solid #0ea5e9;" class="shadow-sm">
                                                        <div class="small text-info fw-bold mt-1"><i class="bi bi-zoom-in me-1"></i>Ver Captura</div>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small"><i class="bi bi-image me-1"></i>Sin captura</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4">
                                                <form method="POST" class="d-flex flex-column gap-1">
                                                    <input type="hidden" name="hub_action" value="update_ticket_status">
                                                    <input type="hidden" name="hub_id" value="<?= htmlspecialchars($tk['hub_id'] ?? '') ?>">
                                                    <select name="estado" class="form-select form-select-sm fw-bold <?= $est === 'solucionado' ? 'border-success text-success' : 'border-danger text-danger' ?>">
                                                        <option value="reportado" <?= $est === 'reportado' ? 'selected' : '' ?>>🔴 Reportado</option>
                                                        <option value="en_revision" <?= $est === 'en_revision' ? 'selected' : '' ?>>🟠 En Revisión</option>
                                                        <option value="solucionado" <?= $est === 'solucionado' ? 'selected' : '' ?>>🟢 Solucionado</option>
                                                    </select>
                                                    <input type="text" name="solucion" class="form-control form-control-sm" placeholder="Nota de solución..." value="<?= htmlspecialchars($tk['solucion'] ?? '') ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark rounded-pill">Actualizar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'averias' || params.get('tab') === 'tickets') {
        const el = document.getElementById('modalCentroAverias');
        if (el) new bootstrap.Modal(el).show();
    }
});
function updateTreeVisualState() {
    let activeMenus = 0;
    let activeItems = 0;

    document.querySelectorAll('.menu-parent-check').forEach(parentCheck => {
        const menuKey = parentCheck.getAttribute('data-menu-key');
        const card = document.getElementById('card_' + menuKey);
        const isParentOn = parentCheck.checked;

        if (isParentOn) activeMenus++;

        if (card) {
            card.classList.toggle('menu-active', isParentOn);
            card.classList.toggle('menu-disabled', !isParentOn);
        }

        document.querySelectorAll(`.item-child-check[data-parent-menu="${menuKey}"]`).forEach(childCheck => {
            const row = childCheck.closest('.tree-item-row');
            const isEffectiveOn = isParentOn && childCheck.checked;
            if (isEffectiveOn) activeItems++;
            if (row) {
                row.classList.toggle('item-unchecked', !isEffectiveOn);
            }
        });
    });

    const cm = document.getElementById('counterMenus');
    const ci = document.getElementById('counterItems');
    if (cm) cm.textContent = activeMenus;
    if (ci) ci.textContent = activeItems;
}

document.querySelectorAll('.menu-parent-check').forEach(parentCheck => {
    parentCheck.addEventListener('change', function () {
        const menuKey = this.getAttribute('data-menu-key');
        const children = document.querySelectorAll(`.item-child-check[data-parent-menu="${menuKey}"]`);
        if (this.checked) {
            const anyChecked = Array.from(children).some(c => c.checked);
            if (!anyChecked) {
                children.forEach(c => c.checked = true);
            }
        }
        updateTreeVisualState();
    });
});

document.querySelectorAll('.item-child-check').forEach(childCheck => {
    childCheck.addEventListener('change', function () {
        const menuKey = this.getAttribute('data-parent-menu');
        const parentCheck = document.getElementById('menu_' + menuKey);
        const siblings = document.querySelectorAll(`.item-child-check[data-parent-menu="${menuKey}"]`);
        const anyChecked = Array.from(siblings).some(c => c.checked);

        if (this.checked && parentCheck && !parentCheck.checked) {
            parentCheck.checked = true;
        } else if (!anyChecked && parentCheck) {
            parentCheck.checked = false;
        }
        updateTreeVisualState();
    });
});

function toggleAllChildren(menuKey, state) {
    const parentCheck = document.getElementById('menu_' + menuKey);
    if (parentCheck) {
        parentCheck.checked = state;
    }
    document.querySelectorAll(`.item-child-check[data-parent-menu="${menuKey}"]`).forEach(c => {
        c.checked = state;
    });
    updateTreeVisualState();
}

function applyPreset(preset) {
    const allParents = document.querySelectorAll('.menu-parent-check');
    const allChildren = document.querySelectorAll('.item-child-check');

    if (preset === 'all') {
        allParents.forEach(p => p.checked = true);
        allChildren.forEach(c => c.checked = true);
    } else if (preset === 'none') {
        allParents.forEach(p => {
            p.checked = (p.getAttribute('data-menu-key') === 'dashboard');
        });
        allChildren.forEach(c => {
            c.checked = (c.getAttribute('data-parent-menu') === 'dashboard');
        });
    } else if (preset === 'cefi_standard') {
        const allowedMenus = ['dashboard', 'gestion_cursos', 'estudiante_tcu', 'registro_academico', 'menu_configuracion'];
        allParents.forEach(p => {
            p.checked = allowedMenus.includes(p.getAttribute('data-menu-key'));
        });
        allChildren.forEach(c => {
            c.checked = allowedMenus.includes(c.getAttribute('data-parent-menu'));
        });
    } else if (preset === 'basic') {
        const allowedMenus = ['dashboard', 'gestion_cursos', 'estudiante_tcu'];
        allParents.forEach(p => {
            p.checked = allowedMenus.includes(p.getAttribute('data-menu-key'));
        });
        allChildren.forEach(c => {
            c.checked = allowedMenus.includes(c.getAttribute('data-parent-menu'));
        });
    }
    updateTreeVisualState();
}

const searchInput = document.getElementById('treeSearchInput');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.tree-card-col').forEach(col => {
            if (!q) {
                col.style.display = '';
                col.querySelectorAll('.tree-item-row').forEach(r => r.style.display = '');
                return;
            }
            let anyMatchInCard = false;
            col.querySelectorAll('.tree-item-row').forEach(row => {
                const txt = row.getAttribute('data-search-text') || '';
                const matched = txt.includes(q);
                row.style.display = matched ? '' : 'none';
                if (matched) anyMatchInCard = true;
            });
            const headerTxt = col.querySelector('.tree-card-header')?.innerText.toLowerCase() || '';
            if (headerTxt.includes(q)) anyMatchInCard = true;
            col.style.display = anyMatchInCard ? '' : 'none';
        });
    });
}

function openNewPlatformModal() {
    document.getElementById('modalPlatformTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-info"></i>Añadir Nueva Plataforma / Cliente';
    document.getElementById('plat_id').value = '';
    document.getElementById('plat_id').readOnly = false;
    document.getElementById('plat_siglas').value = '';
    document.getElementById('plat_nombre').value = '';
    document.getElementById('plat_url').value = '';
    document.getElementById('plat_sync_url').value = '';
    document.getElementById('plat_color').value = '#2563eb';
    document.getElementById('plat_plan').value = '';
}

function openEditPlatformModal(data) {
    document.getElementById('modalPlatformTitle').innerHTML = '<i class="bi bi-gear me-2 text-info"></i>Editar Plataforma: ' + data.siglas;
    document.getElementById('plat_id').value = data.id || '';
    document.getElementById('plat_id').readOnly = true;
    document.getElementById('plat_siglas').value = data.siglas || '';
    document.getElementById('plat_nombre').value = data.nombre || '';
    document.getElementById('plat_motor').value = data.motor || 'PHP Nativo (Inbox 2.0)';
    document.getElementById('plat_url').value = data.url || '';
    document.getElementById('plat_sync_url').value = data.sync_endpoint || '';
    document.getElementById('plat_color').value = data.color || '#2563eb';
    document.getElementById('plat_plan').value = data.plan || '';
}
</script>
</body>
</html>

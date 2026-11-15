<?php
/**
 * ==============================================================================
 * INBOX CLOUD HUB - CONTROL PLANE & LICENSE SERVER (renangalvan.net)
 * ==============================================================================
 * Configuración central y catálogo maestro del Árbol de Menús y Sub-ítems
 * para todas las plataformas instaladas (UNELA, CEFI y nuevos clientes).
 * ==============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('HUB_VERSION', '2.5.0');
define('HUB_DATA_DIR', __DIR__ . '/storage');
define('HUB_CLIENTS_FILE', HUB_DATA_DIR . '/platforms.json');
define('HUB_BACKUP_CLIENTS_FILE', dirname(__DIR__) . '/.inbox_hub_platforms_backup.json');
define('HUB_CONFIG_FILE', HUB_DATA_DIR . '/hub_config.json');

// Clave secreta HMAC compartida entre el Hub (renangalvan.net) y los conectores de las plataformas clientes
define('INBOX_HUB_MASTER_SECRET', 'RenanHub2026_MasterHmacSecretKey_998877!');

// Asegurar carpeta storage protegida
if (!is_dir(HUB_DATA_DIR)) {
    @mkdir(HUB_DATA_DIR, 0775, true);
}
if (!file_exists(HUB_DATA_DIR . '/.htaccess')) {
    @file_put_contents(HUB_DATA_DIR . '/.htaccess', "Order deny,allow\nDeny from all\n");
}

/**
 * Obtiene la configuración de acceso del Hub.
 */
function get_hub_config(): array {
    $default = [
        'admin_user' => 'renan',
        'admin_pass_hash' => password_hash('RenanDev2026', PASSWORD_DEFAULT),
        'fallback_keys' => ['RenanDev2026', 'unela2026', 'cefi2026'],
        'owner_name' => 'Renan Galván',
        'owner_domain' => 'renangalvan.net',
    ];

    if (file_exists(HUB_CONFIG_FILE)) {
        $raw = @file_get_contents(HUB_CONFIG_FILE);
        $dec = $raw ? json_decode($raw, true) : null;
        if (is_array($dec)) {
            return array_merge($default, $dec);
        }
    }
    return $default;
}

/**
 * Árbol Maestro Universal del Sistema Inbox BPM 2.0
 * Cubre todos los menús y sub-ítems tanto de instalaciones PHP Nativo (UNELA)
 * como de instalaciones Laravel (CEFI).
 */
function get_master_menu_tree(): array {
    return [
        'dashboard' => [
            'key'         => 'dashboard',
            'nombre'      => 'Dashboard Principal',
            'icono'       => 'bi-speedometer2',
            'color'       => 'primary',
            'categoria'   => 'Núcleo',
            'descripcion' => 'Panel principal de métricas, KPIs de tareas, filtros y tablero Kanban.',
            'default'     => true,
            'children'    => [
                'item.dashboard_link' => [
                    'nombre'  => 'Enlace Dashboard en Barra Superior',
                    'icono'   => 'bi-speedometer2 text-primary',
                    'ruta'    => '/dashboard',
                    'default' => true,
                ],
                'item.averias_alerta' => [
                    'nombre'  => 'Alerta Luminosa de Averías Pendientes (Bombillo)',
                    'icono'   => 'bi-lightbulb-fill text-warning',
                    'ruta'    => '/soporte',
                    'default' => true,
                ],
            ],
        ],

        'tareas_gantt' => [
            'key'         => 'tareas_gantt',
            'nombre'      => 'Gantt Tareas',
            'icono'       => 'bi-bar-chart-steps',
            'color'       => 'info',
            'categoria'   => 'Núcleo',
            'descripcion' => 'Cronograma visual de tareas colaborativas, plantillas y diagrama de Gantt.',
            'default'     => true,
            'children'    => [
                'item.gantt_tareas' => [
                    'nombre'  => 'Vista General Gantt Tareas',
                    'icono'   => 'bi-bar-chart-steps text-info',
                    'ruta'    => '/cursos/gantt_tareas.php',
                    'default' => true,
                ],
            ],
        ],

        'inventario' => [
            'key'         => 'inventario',
            'nombre'      => 'Logística',
            'icono'       => 'bi-box-seam',
            'color'       => 'warning',
            'categoria'   => 'Operaciones',
            'descripcion' => 'Módulo desplegable de control de stock y operaciones de bodega.',
            'default'     => true,
            'children'    => [
                'item.inventario_control' => [
                    'nombre'  => 'Control de Inventario',
                    'icono'   => 'bi-clipboard-data text-primary',
                    'ruta'    => '/inventario',
                    'default' => true,
                ],
                'item.inventario_operaciones' => [
                    'nombre'  => 'Operaciones de Bodega',
                    'icono'   => 'bi-box-arrow-right text-warning',
                    'ruta'    => '/inventario/operaciones',
                    'default' => true,
                ],
            ],
        ],

        'gestion_cursos' => [
            'key'         => 'gestion_cursos',
            'nombre'      => 'Gestionar Curso',
            'icono'       => 'bi-mortarboard-fill',
            'color'       => 'success',
            'categoria'   => 'Académico & Docente',
            'descripcion' => 'Supervisión académica, aulas docentes, sílabos, recursos, biblioteca y encuestas.',
            'default'     => true,
            'children'    => [
                'item.supervision_cursos' => [
                    'nombre'  => 'Supervisión de Cursos',
                    'icono'   => 'bi-eye text-info',
                    'ruta'    => '/cursos/supervision',
                    'default' => true,
                ],
                'item.mis_cursos' => [
                    'nombre'  => 'Mis Cursos (Aula Docente, Sílabos, Notas, Actas)',
                    'icono'   => 'bi-book text-success',
                    'ruta'    => '/docente/cursos',
                    'default' => true,
                ],
                'item.cortina_curso' => [
                    'nombre'  => 'Cortina de Eventos',
                    'icono'   => 'bi-display-fill text-primary',
                    'ruta'    => '/cortina',
                    'default' => true,
                ],
                'item.mis_recursos' => [
                    'nombre'  => 'Mis Recursos',
                    'icono'   => 'bi-collection text-secondary',
                    'ruta'    => '/docente/recursos',
                    'default' => true,
                ],
                'item.recursos_compartidos' => [
                    'nombre'  => 'Recursos Compartidos',
                    'icono'   => 'bi-share-fill text-info',
                    'ruta'    => '/docente/recursos-compartidos',
                    'default' => true,
                ],
                'item.videotutoriales' => [
                    'nombre'  => 'Videotutoriales',
                    'icono'   => 'bi-play-btn-fill text-success',
                    'ruta'    => '/tutoriales',
                    'default' => true,
                ],
                'item.biblioteca_medios' => [
                    'nombre'  => 'Biblioteca de Medios',
                    'icono'   => 'bi-images text-warning',
                    'ruta'    => '/biblioteca',
                    'default' => true,
                ],
                'item.resultados_encuesta' => [
                    'nombre'  => 'Resultados de Encuestas',
                    'icono'   => 'bi-bar-chart-fill text-success',
                    'ruta'    => '/encuestas/resultados',
                    'default' => true,
                ],
                'item.banco_preguntas' => [
                    'nombre'  => 'Banco de Preguntas de Encuesta',
                    'icono'   => 'bi-clipboard2-check text-primary',
                    'ruta'    => '/encuestas/preguntas',
                    'default' => true,
                ],
            ],
        ],

        'estudiante_tcu' => [
            'key'         => 'estudiante_tcu',
            'nombre'      => 'Estudiante',
            'icono'       => 'bi-journal-text',
            'color'       => 'info',
            'categoria'   => 'Académico & Estudiantil',
            'descripcion' => 'Portal de autogestión estudiantil y seguimiento de horas de TCU.',
            'default'     => true,
            'children'    => [
                'item.portal_estudiante' => [
                    'nombre'  => 'Portal de Estudiantes (Autogestión 360°)',
                    'icono'   => 'bi-mortarboard text-success',
                    'ruta'    => '/estudiante/portal',
                    'default' => true,
                ],
                'item.bitacora_tcu' => [
                    'nombre'  => 'Bitácora de TCU',
                    'icono'   => 'bi-journal-text text-info',
                    'ruta'    => '/estudiante/tcu',
                    'default' => true,
                ],
            ],
        ],

        'soporte' => [
            'key'         => 'soporte',
            'nombre'      => 'Soporte, IA & Automatizaciones',
            'icono'       => 'bi-headset',
            'color'       => 'danger',
            'categoria'   => 'Soporte & Tecnología',
            'descripcion' => 'Mesa de ayuda, bóveda de claves, Inbox AI 2.0, SMTP, Automatizaciones y Suite WhatsApp.',
            'default'     => true,
            'children'    => [
                'item.gestionar_soporte' => [
                    'nombre'  => 'Gestionar Soporte',
                    'icono'   => 'bi-life-preserver text-danger',
                    'ruta'    => '/soporte',
                    'default' => true,
                ],
                'item.categorias_soporte' => [
                    'nombre'  => 'Gestionar Categorías',
                    'icono'   => 'bi-tags text-secondary',
                    'ruta'    => '/soporte/categorias',
                    'default' => true,
                ],
                'item.lista_soporte' => [
                    'nombre'  => 'Lista de Soporte',
                    'icono'   => 'bi-card-list text-primary',
                    'ruta'    => '/soporte/lista',
                    'default' => true,
                ],
                'item.gestionar_claves' => [
                    'nombre'  => 'Gestionar Claves (Bóveda)',
                    'icono'   => 'bi-key-fill text-warning',
                    'ruta'    => '/claves',
                    'default' => true,
                ],
                'inbox_ai' => [
                    'nombre'  => 'Cerebro Inbox AI 2.0',
                    'icono'   => 'bi-cpu-fill text-success',
                    'ruta'    => '/configuracion/conocimiento-ai',
                    'default' => true,
                ],
                'item.servidor_smtp' => [
                    'nombre'  => 'Servidor de Correo (SMTP)',
                    'icono'   => 'bi-envelope-at-fill text-info',
                    'ruta'    => '/configuracion/parametros_correo.php',
                    'default' => true,
                ],
                'item.alertas_inventario' => [
                    'nombre'  => 'Configuración Alertas',
                    'icono'   => 'bi-bell-fill text-warning',
                    'ruta'    => '/configuracion/alertas',
                    'default' => true,
                ],
                'item.utilitarios' => [
                    'nombre'  => 'Utilitarios y Diagnóstico',
                    'icono'   => 'bi-tools text-warning',
                    'ruta'    => '/utilitarios',
                    'default' => true,
                ],
                'item.centro_soporte' => [
                    'nombre'  => 'Centro de Soporte (Credenciales)',
                    'icono'   => 'bi-shield-lock text-primary',
                    'ruta'    => '/centro-soporte',
                    'default' => true,
                ],
                'item.automatizaciones' => [
                    'nombre'  => 'Automatizaciones (Plantillas y Certificados)',
                    'icono'   => 'bi-gear-wide-connected text-success',
                    'ruta'    => '/automatizaciones',
                    'default' => true,
                ],
                'item.campanas_whatsapp' => [
                    'nombre'  => 'Difusión y Campañas WhatsApp',
                    'icono'   => 'bi-megaphone-fill text-warning',
                    'ruta'    => '/whatsapp/campanas',
                    'default' => true,
                ],
                'item.prospectos_marketing' => [
                    'nombre'  => 'Base de Prospectos (Marketing)',
                    'icono'   => 'bi-person-lines-fill text-primary',
                    'ruta'    => '/marketing/prospectos',
                    'default' => true,
                ],
                'item.suite_whatsapp' => [
                    'nombre'  => 'Suite WhatsApp & VPS',
                    'icono'   => 'bi-whatsapp text-success',
                    'ruta'    => '/configuracion/whatsapp',
                    'default' => true,
                ],
            ],
        ],

        'planilla' => [
            'key'         => 'planilla',
            'nombre'      => 'Planilla',
            'icono'       => 'bi-calendar2-check',
            'color'       => 'primary',
            'categoria'   => 'Recursos Humanos',
            'descripcion' => 'Control de asistencia con QR, tarifas por empleado, cálculo de planilla y pagos.',
            'default'     => true,
            'children'    => [
                'item.planilla_marcar' => [
                    'nombre'  => 'Registrar Asistencia',
                    'icono'   => 'bi-clock-fill text-success',
                    'ruta'    => '/asistencia/marcar',
                    'default' => true,
                ],
                'item.planilla_credencial' => [
                    'nombre'  => 'Mi Credencial QR',
                    'icono'   => 'bi-qr-code-scan text-primary',
                    'ruta'    => '/planilla/credencial',
                    'default' => true,
                ],
                'item.planilla_panel' => [
                    'nombre'  => 'Panel de Planilla',
                    'icono'   => 'bi-speedometer text-info',
                    'ruta'    => '/planilla',
                    'default' => true,
                ],
                'item.planilla_escanear' => [
                    'nombre'  => 'Estación de Escaneo QR',
                    'icono'   => 'bi-camera-fill text-danger',
                    'ruta'    => '/planilla/escanear',
                    'default' => true,
                ],
                'item.planilla_empleados' => [
                    'nombre'  => 'Configurar Empleados',
                    'icono'   => 'bi-person-gear text-secondary',
                    'ruta'    => '/planilla/empleados',
                    'default' => true,
                ],
                'item.planilla_historial' => [
                    'nombre'  => 'Historial de Asistencia',
                    'icono'   => 'bi-calendar3 text-primary',
                    'ruta'    => '/asistencia/historial',
                    'default' => true,
                ],
                'item.planilla_calculo' => [
                    'nombre'  => 'Cálculo de Planilla',
                    'icono'   => 'bi-calculator text-success',
                    'ruta'    => '/planilla/calcular',
                    'default' => true,
                ],
                'item.planilla_pagos' => [
                    'nombre'  => 'Historial de Pagos',
                    'icono'   => 'bi-receipt-cutoff text-warning',
                    'ruta'    => '/planilla/historial-pagos',
                    'default' => true,
                ],
            ],
        ],

        'registro_academico' => [
            'key'         => 'registro_academico',
            'nombre'      => 'Registro & Finanzas',
            'icono'       => 'bi-bank2',
            'color'       => 'success',
            'categoria'   => 'Registro & Finanzas',
            'descripcion' => 'Boletas de matrícula, estado de cuenta, morosidad, actas, usuarios, expedientes 360°, programas, cursos y Fase 4.',
            'default'     => true,
            'children'    => [
                'item.generar_boleta' => [
                    'nombre'  => 'Generar Boleta de Matrícula / Pago',
                    'icono'   => 'bi-file-earmark-plus-fill text-success',
                    'ruta'    => '/boletas/generar',
                    'default' => true,
                ],
                'item.gestion_boletas' => [
                    'nombre'  => 'Gestión / Historial de Boletas',
                    'icono'   => 'bi-file-earmark-check-fill text-primary',
                    'ruta'    => '/boletas',
                    'default' => true,
                ],
                'item.estado_cuenta' => [
                    'nombre'  => 'Estado de Cuenta del Estudiante',
                    'icono'   => 'bi-wallet2 text-info',
                    'ruta'    => '/boletas/estado-cuenta',
                    'default' => true,
                ],
                'item.morosidad' => [
                    'nombre'  => 'Control de Morosidad y Cobro',
                    'icono'   => 'bi-exclamation-octagon-fill text-danger',
                    'ruta'    => '/boletas/morosidad',
                    'default' => true,
                ],
                'item.parametros_pagos' => [
                    'nombre'  => 'Configuración de Pagos y Firmas',
                    'icono'   => 'bi-gear-fill text-secondary',
                    'ruta'    => '/configuracion/pagos',
                    'default' => true,
                ],
                'item.repositorio_actas' => [
                    'nombre'  => 'Repositorio de Actas',
                    'icono'   => 'bi-journal-bookmark-fill text-danger',
                    'ruta'    => '/registro/repositorio_actas.php',
                    'default' => true,
                ],
                'item.registrar_usuario' => [
                    'nombre'  => 'Registrar Usuario',
                    'icono'   => 'bi-person-plus-fill text-primary',
                    'ruta'    => '/usuarios/crear',
                    'default' => true,
                ],
                'item.lista_usuarios' => [
                    'nombre'  => 'Lista de Usuarios',
                    'icono'   => 'bi-people-fill text-info',
                    'ruta'    => '/usuarios',
                    'default' => true,
                ],
                'expedientes_360' => [
                    'nombre'  => 'Expedientes Digitales 360°',
                    'icono'   => 'bi-folder-symlink-fill text-success',
                    'ruta'    => '/expedientes',
                    'default' => true,
                ],
                'item.crear_expediente' => [
                    'nombre'  => 'Crear Expediente Digital',
                    'icono'   => 'bi-folder-plus text-success',
                    'ruta'    => '/expedientes/crear',
                    'default' => true,
                ],
                'item.registrar_programa' => [
                    'nombre'  => 'Registrar Programa / Carrera',
                    'icono'   => 'bi-mortarboard text-primary',
                    'ruta'    => '/programas/crear',
                    'default' => true,
                ],
                'item.registrar_curso' => [
                    'nombre'  => 'Registrar Curso (Plan de Estudios)',
                    'icono'   => 'bi-book-half text-success',
                    'ruta'    => '/cursos/crear',
                    'default' => true,
                ],
                'item.gestionar_grupos' => [
                    'nombre'  => 'Gestionar Grupos',
                    'icono'   => 'bi-grid-3x3-gap text-info',
                    'ruta'    => '/grupos',
                    'default' => true,
                ],
                'item.formularios_registro' => [
                    'nombre'  => 'Formularios de Registro / Cursos Libres',
                    'icono'   => 'bi-ui-checks text-secondary',
                    'ruta'    => '/solicitudes-cursos-libres',
                    'default' => true,
                ],
                'item.lista_programas' => [
                    'nombre'  => 'Lista de Programas',
                    'icono'   => 'bi-list-task text-primary',
                    'ruta'    => '/programas',
                    'default' => true,
                ],
                'item.lista_cursos' => [
                    'nombre'  => 'Lista de Cursos',
                    'icono'   => 'bi-journals text-success',
                    'ruta'    => '/cursos',
                    'default' => true,
                ],
                'item.programas_completos' => [
                    'nombre'  => 'Ver Programas Completos',
                    'icono'   => 'bi-file-earmark-ruled text-info',
                    'ruta'    => '/programas-completos',
                    'default' => true,
                ],
                'item.convalidaciones' => [
                    'nombre'  => 'Convalidaciones & Homologaciones (Fase 4)',
                    'icono'   => 'bi-patch-check text-primary',
                    'ruta'    => '/convalidaciones',
                    'default' => true,
                ],
                'item.retiros' => [
                    'nombre'  => 'Retiros y Cambios de Curso (Fase 4)',
                    'icono'   => 'bi-arrow-left-right text-warning',
                    'ruta'    => '/retiros-asignaturas',
                    'default' => true,
                ],
                'item.graduaciones' => [
                    'nombre'  => 'Auditoría y Graduaciones (Fase 4)',
                    'icono'   => 'bi-award text-success',
                    'ruta'    => '/graduaciones',
                    'default' => true,
                ],
                'item.etiquetas_tareas' => [
                    'nombre'  => 'Etiquetas de Tareas',
                    'icono'   => 'bi-bookmarks text-warning',
                    'ruta'    => '/configuracion/gestionar_etiquetas.php',
                    'default' => true,
                ],
                'item.saludos_plantillas' => [
                    'nombre'  => 'Módulo Saludos: Gestionar Plantillas',
                    'icono'   => 'bi-chat-heart text-success',
                    'ruta'    => '/saludo/gestionar_plantillas.php',
                    'default' => true,
                ],
                'item.saludos_categorias' => [
                    'nombre'  => 'Módulo Saludos: Gestionar Categorías',
                    'icono'   => 'bi-folder2 text-secondary',
                    'ruta'    => '/saludo/gestionar_categorias.php',
                    'default' => true,
                ],
                'item.plantillas_tareas' => [
                    'nombre'  => 'Plantillas de Tareas',
                    'icono'   => 'bi-card-checklist text-primary',
                    'ruta'    => '/tareas/plantillas/gestionar',
                    'default' => true,
                ],
                'item.reportes_sistema' => [
                    'nombre'  => 'Reportes del Sistema',
                    'icono'   => 'bi-file-earmark-pdf text-danger',
                    'ruta'    => '/reportes',
                    'default' => true,
                ],
                'item.registro_utilitarios' => [
                    'nombre'  => 'Utilitarios (En Menú Registro)',
                    'icono'   => 'bi-tools text-secondary',
                    'ruta'    => '/utilitarios',
                    'default' => true,
                ],
            ],
        ],

        'base_datos' => [
            'key'         => 'base_datos',
            'nombre'      => 'Base de Datos & Eventos',
            'icono'       => 'bi-gift-fill',
            'color'       => 'warning',
            'categoria'   => 'Marketing & Eventos',
            'descripcion' => 'Cortina digital de eventos, sorteos en vivo, directorio de contactos y exportación CSV.',
            'default'     => true,
            'children'    => [
                'item.bd_cortina' => [
                    'nombre'  => 'Cortina Digital de Eventos',
                    'icono'   => 'bi-display-fill text-primary',
                    'ruta'    => '/cortina',
                    'default' => true,
                ],
                'item.bd_sorteos' => [
                    'nombre'  => 'Eventos & Sorteos en Vivo',
                    'icono'   => 'bi-collection-play-fill text-success',
                    'ruta'    => '/base_datos/index.php',
                    'default' => true,
                ],
                'item.bd_directorio' => [
                    'nombre'  => 'Directorio de Contactos',
                    'icono'   => 'bi-telephone-fill text-primary',
                    'ruta'    => '/base_datos/index.php#tabProspectos',
                    'default' => true,
                ],
                'item.bd_exportar' => [
                    'nombre'  => 'Descargar Base Completa (CSV)',
                    'icono'   => 'bi-file-earmark-excel text-success',
                    'ruta'    => '/base_datos/exportar.php?tipo=global',
                    'default' => true,
                ],
            ],
        ],

        'menu_configuracion' => [
            'key'         => 'menu_configuracion',
            'nombre'      => 'Menú Configuración (Administración General)',
            'icono'       => 'bi-gear-fill',
            'color'       => 'info',
            'categoria'   => 'Configuración',
            'descripcion' => 'Menú desplegable de configuración institucional, Tutor AI, Automatizaciones, n8n y SMTP.',
            'default'     => true,
            'children'    => [
                'item.cfg_panel' => [
                    'nombre'  => 'Panel de Configuración',
                    'icono'   => 'bi-speedometer2 text-info',
                    'ruta'    => '/configuracion',
                    'default' => true,
                ],
                'item.cfg_pagos' => [
                    'nombre'  => 'Pagos, Morosidad e Intereses',
                    'icono'   => 'bi-cash-coin text-success',
                    'ruta'    => '/configuracion/pagos',
                    'default' => true,
                ],
                'item.cfg_rag' => [
                    'nombre'  => 'Base de Conocimiento (RAG Tutor AI)',
                    'icono'   => 'bi-mortarboard-fill text-info',
                    'ruta'    => '/configuracion/conocimiento-ai',
                    'default' => true,
                ],
                'item.cfg_chat_ai' => [
                    'nombre'  => 'Abrir Chat Tutor AI',
                    'icono'   => 'bi-chat-dots-fill text-success',
                    'ruta'    => '#inboxAiFab',
                    'default' => true,
                ],
                'item.cfg_automatizaciones' => [
                    'nombre'  => 'Automatizaciones (Plantillas y Certificados)',
                    'icono'   => 'bi-gear-wide-connected text-success',
                    'ruta'    => '/automatizaciones',
                    'default' => true,
                ],
                'item.cfg_parametros' => [
                    'nombre'  => 'Parámetros del Sistema',
                    'icono'   => 'bi-buildings text-primary',
                    'ruta'    => '/configuracion?tab=parametros',
                    'default' => true,
                ],
                'item.cfg_identidad' => [
                    'nombre'  => 'Logotipo e Identidad',
                    'icono'   => 'bi-palette text-warning',
                    'ruta'    => '/configuracion?tab=identidad',
                    'default' => true,
                ],
                'item.cfg_n8n' => [
                    'nombre'  => 'Parámetros n8n & WhatsApp',
                    'icono'   => 'bi-diagram-3-fill text-success',
                    'ruta'    => '/configuracion?tab=n8n',
                    'default' => true,
                ],
                'item.cfg_smtp' => [
                    'nombre'  => 'Correo Saliente (SMTP)',
                    'icono'   => 'bi-envelope-at-fill text-warning',
                    'ruta'    => '/configuracion?tab=smtp',
                    'default' => true,
                ],
                'item.cfg_modulos' => [
                    'nombre'  => 'Módulos y Personalización',
                    'icono'   => 'bi-toggles2 text-danger',
                    'ruta'    => '/configuracion?tab=modulos',
                    'default' => true,
                ],
                'item.cfg_seguridad' => [
                    'nombre'  => 'Clave Maestra Superior',
                    'icono'   => 'bi-key-fill text-info',
                    'ruta'    => '/configuracion?tab=seguridad',
                    'default' => true,
                ],
            ],
        ],
    ];
}

/**
 * Obtiene el mapa plano de flags por defecto según el perfil inicial.
 */
function get_default_flags_for_profile(string $profile = 'full_suite'): array {
    $tree = get_master_menu_tree();
    $flags = [];

    foreach ($tree as $pKey => $parent) {
        $flags[$pKey] = true;
        foreach ($parent['children'] as $cKey => $child) {
            $flags[$cKey] = true;
        }
    }

    if ($profile === 'cefi_default') {
        // Estado actual contratado por CEFI
        $disabledMenus = ['tareas_gantt', 'inventario', 'soporte', 'planilla', 'base_datos'];
        foreach ($disabledMenus as $dm) {
            $flags[$dm] = false;
            if (!empty($tree[$dm]['children'])) {
                foreach ($tree[$dm]['children'] as $ck => $cv) {
                    $flags[$ck] = false;
                }
            }
        }
    }

    // Sincronizar banderas macro
    $flags['boletas_matricula'] = !empty($flags['item.generar_boleta']) || !empty($flags['item.gestion_boletas']) || !empty($flags['item.parametros_pagos']);
    $flags['finanzas']          = !empty($flags['item.estado_cuenta']) || !empty($flags['item.morosidad']);
    $flags['whatsapp_n8n']      = !empty($flags['item.campanas_whatsapp']) || !empty($flags['item.suite_whatsapp']);

    return $flags;
}

/**
 * Obtiene todas las plataformas registradas en el Hub.
 */
function get_registered_platforms(): array {
    if (file_exists(HUB_CLIENTS_FILE)) {
        $raw = @file_get_contents(HUB_CLIENTS_FILE);
        $dec = $raw ? json_decode($raw, true) : null;
        if (is_array($dec) && count($dec) > 0) {
            return $dec;
        }
    }

    // Respaldo externo por si Hostinger limpia la carpeta en una reinstalación de Git
    if (file_exists(HUB_BACKUP_CLIENTS_FILE)) {
        $raw = @file_get_contents(HUB_BACKUP_CLIENTS_FILE);
        $dec = $raw ? json_decode($raw, true) : null;
        if (is_array($dec) && count($dec) > 0) {
            @file_put_contents(HUB_CLIENTS_FILE, json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $dec;
        }
    }

    // Semilla inicial con UNELA y CEFI listos
    $initial = [
        'unela' => [
            'id'              => 'unela',
            'nombre'          => 'UNELA - Universidad Evangélica de las Américas',
            'siglas'          => 'UNELA',
            'url'             => 'https://unela.org/bpm_unela',
            'sync_endpoint'   => 'https://unela.org/bpm_unela/api/license_sync.php',
            'motor'           => 'PHP Nativo (Inbox 2.0)',
            'color'           => '#2563eb',
            'estado_licencia' => 'activo', // 'activo', 'aviso_cobro', 'suspendido'
            'mensaje_aviso'   => '',
            'plan_comercial'  => 'Enterprise Full Suite 100%',
            'ultima_sync'     => null,
            'ultimo_resultado'=> null,
            'flags'           => get_default_flags_for_profile('full_suite'),
        ],
        'cefi' => [
            'id'              => 'cefi',
            'nombre'          => 'CEFI - Centro de Enseñanza de Formación Integral',
            'siglas'          => 'CEFI',
            'url'             => 'https://inbox.ceficr.com',
            'sync_endpoint'   => 'https://inbox.ceficr.com/api/license-sync',
            'motor'           => 'Laravel 11 (Inbox 2.0)',
            'color'           => '#5fb230',
            'estado_licencia' => 'activo',
            'mensaje_aviso'   => '',
            'plan_comercial'  => 'Plan Académico + Finanzas + IA',
            'ultima_sync'     => null,
            'ultimo_resultado'=> null,
            'flags'           => get_default_flags_for_profile('cefi_default'),
        ],
    ];

    save_registered_platforms($initial);
    return $initial;
}

/**
 * Guarda todas las plataformas en el almacenamiento del Hub.
 */
function save_registered_platforms(array $platforms): bool {
    $json = json_encode($platforms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    @file_put_contents(HUB_BACKUP_CLIENTS_FILE, $json);
    return @file_put_contents(HUB_CLIENTS_FILE, $json) !== false;
}

/**
 * Envía por HTTPS (Push firmado con HMAC-SHA256) la configuración del árbol a la plataforma destino.
 */
function push_license_to_platform(array &$platform): array {
    $endpoint = trim($platform['sync_endpoint'] ?? '');
    if (empty($endpoint)) {
        return ['success' => false, 'message' => 'No se ha configurado el endpoint de sincronización para esta plataforma.'];
    }

    $payloadData = [
        'client_id'       => $platform['id'],
        'nombre'          => $platform['nombre'],
        'estado_licencia' => $platform['estado_licencia'] ?? 'activo',
        'mensaje_aviso'   => $platform['mensaje_aviso'] ?? '',
        'plan_comercial'  => $platform['plan_comercial'] ?? '',
        'timestamp'       => time(),
        'flags'           => $platform['flags'] ?? [],
    ];

    $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_UNICODE);
    $signature = hash_hmac('sha256', $jsonPayload, INBOX_HUB_MASTER_SECRET);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $jsonPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Inbox-Hub-Signature: ' . $signature,
            'X-Inbox-Client-Id: ' . $platform['id'],
        ],
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $platform['ultima_sync'] = date('Y-m-d H:i:s');

    if ($responseBody === false || $httpCode === 0) {
        $msg = 'No se pudo conectar con ' . $endpoint . ($curlErr ? " ({$curlErr})" : '');
        $platform['ultimo_resultado'] = ['ok' => false, 'msg' => $msg, 'code' => 0];
        return ['success' => false, 'message' => $msg];
    }

    $decoded = json_decode($responseBody, true);
    if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded) && !empty($decoded['success'])) {
        $msg = $decoded['message'] ?? 'Sincronizado correctamente en el servidor remoto.';
        $platform['ultimo_resultado'] = ['ok' => true, 'msg' => $msg, 'code' => $httpCode];
        return ['success' => true, 'message' => $msg, 'remote' => $decoded];
    }

    $errDetail = is_array($decoded) && !empty($decoded['message'])
        ? $decoded['message']
        : ('HTTP ' . $httpCode . ': ' . mb_substr(strip_tags((string)$responseBody), 0, 140));
    $platform['ultimo_resultado'] = ['ok' => false, 'msg' => $errDetail, 'code' => $httpCode];
    return ['success' => false, 'message' => $errDetail];
}

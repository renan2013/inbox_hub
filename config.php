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
            'nombre'      => 'Dashboard Principal & Tareas',
            'icono'       => 'bi-speedometer2',
            'color'       => 'primary',
            'categoria'   => 'Núcleo',
            'descripcion' => 'Panel principal de métricas, vistas de tareas (Tabla, Tablero Kanban, Cronograma Gantt), plantillas y etiquetas.',
            'default'     => true,
            'children'    => [
                'item.dashboard_link' => [
                    'nombre'  => 'Enlace Dashboard en Barra Superior (Tabla y Tablero)',
                    'icono'   => 'bi-speedometer2 text-primary',
                    'ruta'    => '/dashboard',
                    'default' => true,
                ],
                'item.gantt_tareas' => [
                    'nombre'  => 'Vista Cronograma / Diagrama de Gantt en Dashboard',
                    'icono'   => 'bi-bar-chart-steps text-info',
                    'ruta'    => '/tareas/gantt',
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
                    'nombre'  => 'Configuración de Pagos y Firmas',
                    'icono'   => 'bi-gear-fill text-success',
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
    $flags['boletas_matricula'] = !empty($flags['item.generar_boleta']) || !empty($flags['item.gestion_boletas']);
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
        if (!empty($decoded['discovered_tree']) && is_array($decoded['discovered_tree'])) {
            $platform['discovered_tree'] = $decoded['discovered_tree'];
        }
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

/**
 * Consulta en vivo la plataforma remota para auto-descubrir nuevos menús o sub-ítems
 * que se hayan creado en su navbar.php / app.blade.php.
 */
function discover_tree_from_platform(array &$platform): array {
    $endpoint = trim($platform['sync_endpoint'] ?? '');
    if (empty($endpoint)) {
        return ['success' => false, 'message' => 'No hay endpoint configurado.'];
    }

    $payloadData = [
        'action'    => 'discover_tree',
        'client_id' => $platform['id'],
        'timestamp' => time(),
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
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = $responseBody ? json_decode($responseBody, true) : null;
    if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded) && !empty($decoded['discovered_tree'])) {
        $platform['discovered_tree'] = $decoded['discovered_tree'];
        $newItemsCount = 0;
        $merged = get_effective_menu_tree($platform, $newItemsCount);
        foreach ($merged as $mKey => $mMeta) {
            if (!array_key_exists($mKey, $platform['flags'])) {
                $platform['flags'][$mKey] = true;
            }
            foreach ($mMeta['children'] ?? [] as $cKey => $cMeta) {
                if (!array_key_exists($cKey, $platform['flags'])) {
                    $platform['flags'][$cKey] = true;
                }
            }
        }
        return [
            'success'   => true,
            'message'   => "Escaneo en vivo completado ({$newItemsCount} ítems auto-detectados desde el código de {$platform['siglas']}).",
            'new_items' => $newItemsCount,
        ];
    }

    return ['success' => false, 'message' => 'No se pudo escanear el árbol remoto (HTTP ' . $httpCode . ').'];
}

/**
 * Combina el Árbol Maestro Universal con cualquier menú o sub-ítem nuevo
 * auto-descubierto desde el código de la plataforma cliente.
 */
function get_effective_menu_tree(array $platform, int &$autoDiscoveredCount = 0): array {
    $tree = get_master_menu_tree();
    $autoDiscoveredCount = 0;

    if (empty($platform['discovered_tree']) || !is_array($platform['discovered_tree'])) {
        return $tree;
    }

    // Indexar nombres de sub-ítems ya existentes en cada menú para evitar duplicados visuales
    foreach ($platform['discovered_tree'] as $mKey => $mMeta) {
        if (!isset($tree[$mKey])) {
            $tree[$mKey] = [
                'key'         => $mKey,
                'nombre'      => $mMeta['nombre'] ?? ucfirst($mKey),
                'icono'       => $mMeta['icono'] ?? 'bi-grid-fill',
                'color'       => $mMeta['color'] ?? 'primary',
                'categoria'   => 'Auto-Detectado',
                'descripcion' => $mMeta['descripcion'] ?? 'Menú detectado automáticamente desde la plataforma.',
                'default'     => true,
                'children'    => [],
            ];
            $autoDiscoveredCount++;
        }

        $existingLabels = [];
        foreach ($tree[$mKey]['children'] ?? [] as $existingKey => $existingChild) {
            $existingLabels[mb_strtolower(trim($existingChild['nombre'] ?? ''), 'UTF-8')] = $existingKey;
        }

        foreach ($mMeta['children'] ?? [] as $cKey => $cMeta) {
            $cLabelNorm = mb_strtolower(trim($cMeta['nombre'] ?? ''), 'UTF-8');
            if (!isset($tree[$mKey]['children'][$cKey]) && !isset($existingLabels[$cLabelNorm])) {
                $tree[$mKey]['children'][$cKey] = [
                    'nombre'  => ($cMeta['nombre'] ?? $cKey) . ' ✨',
                    'icono'   => $cMeta['icono'] ?? 'bi-stars text-info',
                    'ruta'    => $cMeta['ruta'] ?? '#',
                    'default' => true,
                ];
                $autoDiscoveredCount++;
            }
        }
    }

    return $tree;
}

// ============================================================================
// CENTRO GLOBAL DE AVERÍAS & NOTIFICACIONES OMNICANAL (WHATSAPP n8n + EMAIL)
// ============================================================================
// Correo Interno Fijo del Creador y Proveedor del Ecosistema Inbox
define('INBOX_CREATOR_EMAIL', 'renangalvan@gmail.com');
define('INBOX_HUB_TICKETS_FILE', HUB_DATA_DIR . '/tickets_state.json');
define('INBOX_HUB_TICKETS_BACKUP', dirname(__DIR__) . '/.inbox_hub_tickets_backup.json');

function load_hub_tickets(): array {
    $sourceFile = null;
    if (file_exists(INBOX_HUB_TICKETS_FILE)) {
        $sourceFile = INBOX_HUB_TICKETS_FILE;
    } elseif (file_exists(INBOX_HUB_TICKETS_BACKUP)) {
        $sourceFile = INBOX_HUB_TICKETS_BACKUP;
    }

    if ($sourceFile !== null) {
        $decoded = json_decode((string)@file_get_contents($sourceFile), true);
        if (is_array($decoded)) {
            if (!isset($decoded['settings'])) {
                $decoded['settings'] = get_default_ticket_settings();
            }
            $decoded['settings']['admin_email'] = INBOX_CREATOR_EMAIL;
            if (!isset($decoded['tickets']) || !is_array($decoded['tickets'])) {
                $decoded['tickets'] = [];
            }
            return $decoded;
        }
    }

    return [
        'settings' => get_default_ticket_settings(),
        'tickets'  => [],
    ];
}

function get_default_ticket_settings(): array {
    return [
        'admin_email'      => INBOX_CREATOR_EMAIL,
        'admin_whatsapp'   => '+50688888888',
        'n8n_webhook_url'  => '',
        'notify_email'     => true,
        'notify_whatsapp'  => true,
    ];
}

function save_hub_tickets(array $data): bool {
    $data['updated_at'] = date('Y-m-d H:i:s');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = @file_put_contents(INBOX_HUB_TICKETS_FILE, $json) !== false;
    @file_put_contents(INBOX_HUB_TICKETS_BACKUP, $json);
    return $ok;
}

/**
 * Envía una acción sobre un ticket (update_ticket o delete_ticket) desde el Hub hacia la plataforma cliente.
 */
function push_ticket_action_to_platform(array $platform, string $action, $localId, string $estado = '', string $solucion = ''): bool {
    $endpoint = trim($platform['sync_endpoint'] ?? '');
    if (empty($endpoint)) {
        return false;
    }
    $payloadData = [
        'action'    => $action,
        'client_id' => $platform['id'],
        'local_id'  => $localId,
        'estado'    => $estado,
        'solucion'  => $solucion,
        'timestamp' => time(),
    ];
    $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $signature   = hash_hmac('sha256', $jsonPayload, INBOX_HUB_MASTER_SECRET);

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
        CURLOPT_USERAGENT      => 'InboxCloudHub/2.5',
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $resp = @curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);
    return ($resp !== false && $code >= 200 && $code < 300);
}

/**
 * Sincroniza (Pull) los reportes de avería existentes en las plataformas conectadas (CEFI / UNELA)
 * hacia el Hub Central para asegurar que cualquier reporte aparezca siempre en hub.renangalvan.net.
 */
function sync_tickets_from_platforms(array &$ticketState, array $platforms): int {
    $imported = 0;
    if (!isset($ticketState['tickets']) || !is_array($ticketState['tickets'])) {
        $ticketState['tickets'] = [];
    }

    foreach ($platforms as $cid => $plat) {
        $endpoint = trim($plat['sync_endpoint'] ?? '');
        if ($endpoint === '') continue;

        $payloadData = [
            'action'    => 'fetch_tickets',
            'client_id' => $cid,
            'timestamp' => time(),
        ];
        $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_UNICODE);
        $signature   = hash_hmac('sha256', $jsonPayload, INBOX_HUB_MASTER_SECRET);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Inbox-Hub-Signature: ' . $signature,
                'X-Inbox-Client-Id: ' . $cid,
            ],
            CURLOPT_USERAGENT      => 'InboxCloudHub/2.5',
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $resp = @curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($resp === false || $code < 200 || $code >= 300) {
            continue;
        }
        $dec = json_decode($resp, true);
        if (!is_array($dec) || empty($dec['success']) || !isset($dec['tickets']) || !is_array($dec['tickets'])) {
            continue;
        }

        // Mapear los IDs remotos que siguen vivos en la plataforma
        $remoteAliveIds = [];
        foreach ($dec['tickets'] as $rt) {
            $lid = (string)($rt['local_id'] ?? '');
            if ($lid === '') continue;
            $remoteAliveIds[$lid] = true;

            $found = false;
            foreach ($ticketState['tickets'] as &$existing) {
                if (($existing['client_id'] ?? '') === $cid && (string)($existing['id'] ?? '') === $lid) {
                    $existing['estado']   = $rt['estado'] ?? $existing['estado'];
                    $existing['solucion'] = $rt['solucion'] ?? ($existing['solucion'] ?? '');
                    if (!empty($rt['evidencia_url']) && empty($existing['evidencia_url'])) {
                        $existing['evidencia_url'] = $rt['evidencia_url'];
                    }
                    $found = true;
                    break;
                }
            }
            unset($existing);

            if (!$found) {
                array_unshift($ticketState['tickets'], [
                    'hub_id'         => 'AV-' . strtoupper($cid) . '-' . $lid,
                    'id'             => $lid,
                    'client_id'      => $cid,
                    'client_siglas'  => $plat['siglas'] ?? strtoupper($cid),
                    'client_nombre'  => $plat['nombre'] ?? $cid,
                    'titulo'         => $rt['titulo'] ?? 'Reporte de Avería',
                    'etiqueta'       => $rt['etiqueta'] ?? '🟠 Fallo en Módulo',
                    'prioridad'      => $rt['prioridad'] ?? 'alta',
                    'problema'       => $rt['problema'] ?? '',
                    'solucion'       => $rt['solucion'] ?? '',
                    'estado'         => $rt['estado'] ?? 'reportado',
                    'url_origen'     => $rt['url_origen'] ?? ($plat['url'] ?? ''),
                    'evidencia_url'  => $rt['evidencia_url'] ?? '',
                    'usuario_nombre' => $rt['usuario_nombre'] ?? 'Usuario',
                    'usuario_email'  => $rt['usuario_email'] ?? '',
                    'fecha_creacion' => $rt['fecha_creacion'] ?? date('Y-m-d H:i:s'),
                ]);
                $imported++;
            }
        }
    }

    if ($imported > 0) {
        save_hub_tickets($ticketState);
    }
    return $imported;
}

/**
 * Dispara alerta de nueva avería hacia Webhook n8n (WhatsApp) y Correo Electrónico del Fabricante.
 */
function dispatch_hub_ticket_notifications(array $ticket, array $settings): array {
    $results = ['whatsapp_n8n' => false, 'email' => false];

    $whatsappText = "🚨 *NUEVA AVERÍA REPORTADA — " . strtoupper($ticket['client_siglas'] ?? $ticket['client_id'] ?? 'INBOX') . "*\n"
        . "━━━━━━━━━━━━━━━━━━━━\n"
        . "🆔 *Ticket:* #" . ($ticket['id'] ?? 'N/A') . "\n"
        . "🏷️ *Etiqueta:* " . ($ticket['etiqueta'] ?? 'General') . "\n"
        . "⚡ *Prioridad:* " . strtoupper($ticket['prioridad'] ?? 'Alta') . "\n"
        . "👤 *Reportado por:* " . ($ticket['usuario_nombre'] ?? 'Usuario') . " (" . ($ticket['usuario_rol'] ?? '') . ")\n"
        . "📌 *Asunto:* " . ($ticket['titulo'] ?? '') . "\n"
        . "📝 *Causa / Detalle:* " . ($ticket['problema'] ?? '') . "\n"
        . "🔗 *Pantalla origen:* " . ($ticket['url_origen'] ?? 'N/A') . "\n"
        . (!empty($ticket['evidencia_url']) ? "🖼️ *Evidencia Gráfica:* " . $ticket['evidencia_url'] . "\n" : "")
        . "━━━━━━━━━━━━━━━━━━━━\n"
        . "🛠️ *Gestionar en Hub:* https://hub.renangalvan.net/?tab=averias";

    // 1. Disparo hacia Webhook n8n (para WhatsApp + automatizaciones)
    $webhookUrl = trim($settings['n8n_webhook_url'] ?? '');
    if (!empty($settings['notify_whatsapp']) && !empty($webhookUrl)) {
        $payload = json_encode([
            'event'           => 'nueva_averia_sistema',
            'timestamp'       => date('c'),
            'admin_whatsapp'  => $settings['admin_whatsapp'] ?? '',
            'admin_email'     => $settings['admin_email'] ?? '',
            'whatsapp_text'   => $whatsappText,
            'ticket'          => $ticket,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $results['whatsapp_n8n'] = ($resp !== false && $code >= 200 && $code < 300);
    }

    // 2. Disparo por Correo Electrónico HTML con Evidencia Gráfica incrustada (Siempre fijo a INBOX_CREATOR_EMAIL)
    $adminEmail = INBOX_CREATOR_EMAIL;
    if (!empty($settings['notify_email']) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $subject = "🚨 [AVERÍA " . strtoupper($ticket['prioridad'] ?? 'ALTA') . "] " . ($ticket['client_siglas'] ?? 'INBOX') . ": " . ($ticket['titulo'] ?? 'Reporte de Sistema');
        $evidenciaHtml = '';
        if (!empty($ticket['evidencia_url'])) {
            $evUrl = htmlspecialchars($ticket['evidencia_url']);
            $evidenciaHtml = "
                <div style='margin-top:16px;padding:14px;background:#0f172a;border-radius:10px;border:1px solid #334155;'>
                    <div style='font-weight:bold;color:#38bdf8;margin-bottom:8px;'>📸 Evidencia Gráfica Adjunta:</div>
                    <a href='{$evUrl}' target='_blank' style='color:#10b981;font-weight:bold;'>Ver imagen en tamaño completo &rarr;</a>
                    <div style='margin-top:10px;'>
                        <img src='{$evUrl}' alt='Evidencia Gráfica' style='max-width:100%;max-height:420px;border-radius:8px;border:1px solid #475569;'>
                    </div>
                </div>";
        }

        $htmlBody = "
        <div style='font-family:Arial,sans-serif;max-width:640px;margin:0 auto;background:#1e293b;color:#f8fafc;border-radius:14px;overflow:hidden;border:1px solid #334155;'>
            <div style='background:linear-gradient(90deg,#dc2626,#ea580c);padding:18px 24px;color:#fff;'>
                <h2 style='margin:0;font-size:18px;'>🚨 Nueva Avería Reportada — " . htmlspecialchars($ticket['client_nombre'] ?? $ticket['client_id'] ?? 'Plataforma Inbox') . "</h2>
                <p style='margin:4px 0 0;font-size:12px;opacity:0.9;'>Ticket #" . htmlspecialchars($ticket['id'] ?? '') . " • " . htmlspecialchars($ticket['fecha_creacion'] ?? date('Y-m-d H:i:s')) . "</p>
            </div>
            <div style='padding:24px;'>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <tr><td style='padding:6px 0;color:#94a3b8;width:140px;'><strong>Etiqueta / Causa:</strong></td><td style='padding:6px 0;color:#f8fafc;'><strong>" . htmlspecialchars($ticket['etiqueta'] ?? 'General') . "</strong></td></tr>
                    <tr><td style='padding:6px 0;color:#94a3b8;'><strong>Prioridad:</strong></td><td style='padding:6px 0;color:#f87171;font-weight:bold;'>" . strtoupper(htmlspecialchars($ticket['prioridad'] ?? 'Alta')) . "</td></tr>
                    <tr><td style='padding:6px 0;color:#94a3b8;'><strong>Reportado por:</strong></td><td style='padding:6px 0;color:#f8fafc;'>" . htmlspecialchars(($ticket['usuario_nombre'] ?? '') . ' (' . ($ticket['usuario_email'] ?? '') . ')') . "</td></tr>
                    <tr><td style='padding:6px 0;color:#94a3b8;'><strong>URL del Fallo:</strong></td><td style='padding:6px 0;'><a href='" . htmlspecialchars($ticket['url_origen'] ?? '#') . "' style='color:#38bdf8;'>" . htmlspecialchars($ticket['url_origen'] ?? 'N/A') . "</a></td></tr>
                </table>
                <div style='margin-top:16px;padding:14px;background:#0f172a;border-radius:10px;border-left:4px solid #f59e0b;'>
                    <div style='font-weight:bold;color:#fbbf24;margin-bottom:6px;'>" . htmlspecialchars($ticket['titulo'] ?? '') . "</div>
                    <div style='color:#e2e8f0;white-space:pre-line;font-size:14px;'>" . htmlspecialchars($ticket['problema'] ?? '') . "</div>
                </div>
                {$evidenciaHtml}
                <div style='margin-top:22px;text-align:center;'>
                    <a href='https://hub.renangalvan.net/?tab=averias' style='display:inline-block;background:#10b981;color:#0f172a;font-weight:bold;text-decoration:none;padding:12px 24px;border-radius:999px;'>Atender y Resolver en Inbox Cloud Hub</a>
                </div>
            </div>
        </div>";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Inbox Cloud Hub <no-reply@renangalvan.net>\r\n";
        if (!empty($ticket['usuario_email']) && filter_var($ticket['usuario_email'], FILTER_VALIDATE_EMAIL)) {
            $headers .= "Reply-To: " . $ticket['usuario_email'] . "\r\n";
        }

        $results['email'] = @mail($adminEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
    }

    return $results;
}

<?php

class Slots_Manager {
    private $hours_option = 'asm_minimum_hours';
    private $time_option = 'asm_target_time';
    private $hours_option_2 = 'asm_minimum_hours_2';
    private $time_option_2 = 'asm_target_time_2';
    private $conditional_enabled = 'asm_conditional_enabled';
    private $minimum_minutes_option = 'asm_minimum_minutes';
    private $default_hours = 8;
    private $default_time = '06:10';
    private $default_minimum_minutes = 30;
    private $table_name;
    private $timezone;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'asm_blocked_slots';
        
        // Establecer timezone
        $this->timezone = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
    }

    private function get_wp_datetime() {
        return new DateTime('now', $this->timezone);
    }

    public function init() {
        // Add menu items
        add_action('admin_menu', array($this, 'add_menu_items'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Hook into Amelia's timeslots system
        add_filter('amelia_get_timeslots_filter', array($this, 'filter_amelia_timeslots'), 10, 2);

        // Add AJAX handlers
        add_action('wp_ajax_toggle_conditional', array($this, 'toggle_conditional'));
        add_action('wp_ajax_add_blocked_slot', array($this, 'add_blocked_slot'));
        add_action('wp_ajax_remove_blocked_slot', array($this, 'remove_blocked_slot'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Cleanup task
        add_action('asm_cleanup_blocked_slots', array($this, 'cleanup_old_blocks'));
        if (!wp_next_scheduled('asm_cleanup_blocked_slots')) {
            wp_schedule_event(time(), 'daily', 'asm_cleanup_blocked_slots');
        }
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asm_blocked_slots';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slot_time time NOT NULL,
            block_date date NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            reason text,
            PRIMARY KEY  (id),
            KEY block_date (block_date),
            UNIQUE KEY date_time (block_date, slot_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('asm_cleanup_blocked_slots');
    }

    public function cleanup_old_blocks() {
        global $wpdb;
        $today = $this->get_wp_datetime()->format('Y-m-d');
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE block_date < %s",
                $today
            )
        );
    }

    public function add_menu_items() {
        $main_page = add_menu_page(
            'Hrs Madrugada',
            'Hrs Madrugada',
            'manage_options',
            'slots-manager',
            array($this, 'render_settings_page'),
            'dashicons-beer'
        );

        add_submenu_page(
            'slots-manager',
            'Bloqueos',
            'Bloqueos',
            'manage_options',
            'slots-manager-blocks',
            array($this, 'render_blocks_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('toplevel_page_slots-manager', 'hrs-madrugada_page_slots-manager-blocks'))) {
            return;
        }

        // jQuery UI Core y sus dependencias
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');

        // Script principal del plugin
        wp_enqueue_script(
            'slots-manager-admin',
            ASM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
            '1.0.0',
            true
        );

        // Solo para la página de bloqueos
        if ($hook === 'hrs-madrugada_page_slots-manager-blocks') {
            // Estilos del datepicker
            wp_enqueue_style(
                'jquery-ui-style',
                'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css',
                array(),
                '1.13.2'
            );

            // Estilos personalizados para el datepicker
            wp_add_inline_style('jquery-ui-style', '
                .ui-datepicker {
                    background-color: #fff;
                    border: 1px solid #ccc;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    padding: 10px;
                }
                .ui-datepicker-header {
                    background: #f7f7f7;
                    border: none;
                    padding: 5px;
                }
                .ui-datepicker-calendar th {
                    background: #f7f7f7;
                    padding: 5px;
                }
                .ui-datepicker-calendar td {
                    padding: 2px;
                }
                .ui-datepicker-calendar td a {
                    text-align: center;
                }
                .ui-datepicker-calendar .ui-state-default {
                    background: #fff;
                    border: 1px solid #ddd;
                }
                .ui-datepicker-calendar .ui-state-hover {
                    background: #f0f0f0;
                }
                .ui-datepicker-calendar .ui-state-active {
                    background: #0073aa;
                    color: #fff;
                }
            ');
        }

        wp_localize_script('slots-manager-admin', 'slotsManagerAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('slots-manager-nonce')
        ));
    }

    public function register_settings() {
        // Registro de horas mínimas principal
        register_setting(
            'asm_settings',
            $this->hours_option,
            array(
                'type' => 'integer',
                'default' => $this->default_hours,
                'sanitize_callback' => array($this, 'sanitize_hours')
            )
        );

        // Registro de horario objetivo principal
        register_setting(
            'asm_settings',
            $this->time_option,
            array(
                'type' => 'string',
                'default' => $this->default_time,
                'sanitize_callback' => array($this, 'sanitize_time')
            )
        );

        // Registro de estado del condicional
        register_setting(
            'asm_settings',
            $this->conditional_enabled,
            array(
                'type' => 'boolean',
                'default' => false
            )
        );

        // Registro de horas mínimas secundario
        register_setting(
            'asm_settings',
            $this->hours_option_2,
            array(
                'type' => 'integer',
                'default' => $this->default_hours,
                'sanitize_callback' => array($this, 'sanitize_hours')
            )
        );

        // Registro de horario objetivo secundario
        register_setting(
            'asm_settings',
            $this->time_option_2,
            array(
                'type' => 'string',
                'default' => $this->default_time,
                'sanitize_callback' => array($this, 'sanitize_time')
            )
        );

        // Registro de minutos mínimos
        register_setting(
            'asm_settings',
            $this->minimum_minutes_option,
            array(
                'type' => 'integer',
                'default' => $this->default_minimum_minutes,
                'sanitize_callback' => array($this, 'sanitize_minutes')
            )
        );
    }

    public function toggle_conditional() {
        check_ajax_referer('slots-manager-nonce', 'nonce');
        
        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
        update_option($this->conditional_enabled, $enabled);
        
        wp_send_json_success(array(
            'enabled' => $enabled
        ));
    }

    public function sanitize_hours($value) {
        $value = intval($value);
        return max(1, min(24, $value));
    }

    public function sanitize_time($value) {
        if (preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):10$/', $value)) {
            return $value;
        }
        return $this->default_time;
    }

    public function sanitize_minutes($value) {
        $value = intval($value);
        return max(1, min(120, $value)); // Permitir entre 1 y 120 minutos
    }

    public function get_time_options($exclude_time = null) {
        $options = array();
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $time = "$hour:10";
            if ($time !== $exclude_time) {
                $options[$time] = $time;
            }
        }
        return $options;
    }

    public function render_settings_page() {
        $minimum_hours = get_option($this->hours_option, $this->default_hours);
        $target_time = get_option($this->time_option, $this->default_time);
        $minimum_hours_2 = get_option($this->hours_option_2, $this->default_hours);
        $target_time_2 = get_option($this->time_option_2, $this->default_time);
        $conditional_enabled = get_option($this->conditional_enabled, false);
        $time_options = $this->get_time_options();
        $time_options_2 = $this->get_time_options($target_time);
        include ASM_PLUGIN_DIR . 'views/settings-page.php';
    }

    public function render_blocks_page() {
        $time_options = $this->get_time_options();
        $blocks = $this->get_blocks();
        include ASM_PLUGIN_DIR . 'views/blocks-page.php';
    }

    public function get_blocks() {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE block_date >= %s 
                ORDER BY block_date ASC, slot_time ASC",
                date('Y-m-d')
            )
        );
    }

    public function add_blocked_slot() {
        check_ajax_referer('slots-manager-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes.'));
        }

        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';

        error_log('Intentando agregar bloqueo - Hora: ' . $time . ', Fecha: ' . $date);
        error_log('Timezone actual: ' . $this->timezone->getName());

        // Validar formato de hora
        if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):10$/', $time)) {
            error_log('Error: Formato de hora inválido - ' . $time);
            wp_send_json_error(array('message' => 'Formato de hora inválido.'));
            return;
        }

        try {
            // Validar y formatear fecha usando el timezone de WordPress
            $date_obj = DateTime::createFromFormat('Y-m-d', $date, $this->timezone);
            if (!$date_obj) {
                throw new Exception('Formato de fecha inválido');
            }

            $formatted_date = $date_obj->format('Y-m-d');
            $now = $this->get_wp_datetime();
            
            // Si es el día actual, validar que la hora sea futura
            if ($formatted_date === $now->format('Y-m-d')) {
                $current_hour = (int)$now->format('H');
                $block_hour = (int)explode(':', $time)[0];
                
                if ($block_hour <= $current_hour) {
                    throw new Exception('Para el día actual, solo se pueden bloquear horarios futuros.');
                }
            }

            error_log('Insertando bloqueo en la base de datos - Hora: ' . $time . ', Fecha: ' . $formatted_date);

            global $wpdb;
            // Guardar la hora en formato HH:10 como viene del formulario
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'slot_time' => $time,
                    'block_date' => $formatted_date,
                    'reason' => $reason,
                    'created_by' => get_current_user_id()
                ),
                array('%s', '%s', '%s', '%d')
            );

            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }

            error_log('Bloqueo agregado exitosamente con hora: ' . $time);
            wp_send_json_success(array(
                'message' => 'Bloqueo agregado exitosamente',
                'timezone' => $this->timezone->getName()
            ));

        } catch (Exception $e) {
            error_log('Error al procesar el bloqueo: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    public function remove_blocked_slot() {
        check_ajax_referer('slots-manager-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes.'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(array('message' => 'ID inválido.'));
        }

        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error al eliminar el bloqueo.'));
        }

        wp_send_json_success();
    }

    private function validate_block_data($time, $date) {
        // Validar formato de hora (HH:10)
        if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):10$/', $time)) {
            return false;
        }

        try {
            // Validar fecha usando el timezone de WordPress
            $date_obj = DateTime::createFromFormat('Y-m-d', $date, $this->timezone);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                return false;
            }

            $now = $this->get_wp_datetime();
            $formatted_date = $date_obj->format('Y-m-d');

            // Si es el día actual, validar que la hora sea futura
            if ($formatted_date === $now->format('Y-m-d')) {
                $current_hour = (int)$now->format('H');
                $block_hour = (int)explode(':', $time)[0];
                
                if ($block_hour <= $current_hour) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Error validando fecha: ' . $e->getMessage());
            return false;
        }
    }

    private function get_active_blocks_for_date($date) {
        global $wpdb;
        
        // Obtener los tiempos y asegurarnos que estén en formato HH:10
        $query = $wpdb->prepare(
            "SELECT TIME_FORMAT(slot_time, '%%H:%%i') as time FROM {$this->table_name} WHERE block_date = %s",
            $date
        );
        
        error_log('Consulta SQL para fecha ' . $date . ': ' . $query);
        
        $times = $wpdb->get_col($query);
        error_log('Resultados directos de la consulta: ' . print_r($times, true));
        
        // Asegurarnos que todos los tiempos estén en formato HH:10
        $normalized_times = array();
        foreach ($times as $time) {
            $parts = explode(':', $time);
            if (count($parts) >= 2) {
                $hour = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                // Asegurarnos que siempre sea :10 para coincidir con Amelia
                $normalized_times[] = $hour . ':10';
                error_log('Normalizando hora de bloqueo - Original: ' . $time . ', Normalizada: ' . $hour . ':10');
            }
        }
        
        error_log('Bloqueos normalizados para ' . $date . ': ' . print_r($normalized_times, true));
        return $normalized_times;
    }

    public function filter_amelia_timeslots($resultData, $props) {
        if (!isset($resultData['slots']) || !is_array($resultData['slots'])) {
            return $resultData;
        }

        $now = $this->get_wp_datetime();
        $current_time = $now->format('U');
        error_log('Hora actual en timezone ' . $this->timezone->getName() . ': ' . $now->format('Y-m-d H:i:s'));
        
        $tomorrow = clone $now;
        $tomorrow->modify('+1 day');
        $tomorrow_date = $tomorrow->format('Y-m-d');
        
        // Primera condicional
        $minimum_hours = get_option($this->hours_option, $this->default_hours);
        $target_time = get_option($this->time_option, $this->default_time);
        $min_required_time = $minimum_hours * 3600 + 2 * 60;

        // Segunda condicional
        $conditional_enabled = get_option($this->conditional_enabled, false);
        $minimum_hours_2 = get_option($this->hours_option_2, $this->default_hours);
        $target_time_2 = get_option($this->time_option_2, $this->default_time);
        $min_required_time_2 = $minimum_hours_2 * 3600 + 2 * 60;

        // Obtener minutos mínimos configurados
        $minimum_minutes = get_option($this->minimum_minutes_option, $this->default_minimum_minutes);

        error_log('Configuración de horarios:');
        error_log('Target time 1: ' . $target_time);
        error_log('Target time 2: ' . $target_time_2);
        error_log('Conditional enabled: ' . ($conditional_enabled ? 'true' : 'false'));
        error_log('Minimum minutes: ' . $minimum_minutes);

        // Cache para bloqueos
        $blocks_cache = array();

        $current_date = $now->format('Y-m-d');
        $found_first_slot = false;

        foreach ($resultData['slots'] as $date => &$times) {
            if (!is_array($times)) {
                continue;
            }

            // Obtener bloqueos para esta fecha y loguear para debugging
            if (!isset($blocks_cache[$date])) {
                $blocks_cache[$date] = $this->get_active_blocks_for_date($date);
                error_log('Procesando fecha: ' . $date);
                error_log('Horarios disponibles antes de filtrar: ' . print_r(array_keys($times), true));
                error_log('Bloqueos activos: ' . print_r($blocks_cache[$date], true));
            }

            // Solo procesar slots del día actual para la regla de minutos mínimos
            if ($date === $current_date && !$found_first_slot) {
                ksort($times); // Asegurar que los slots estén ordenados por hora
                foreach ($times as $time => $slot) {
                    if (!$found_first_slot) {
                        // Convertir el tiempo del slot a timestamp para comparación
                        $slot_time = new DateTime($date . ' ' . $time, $this->timezone);
                        $slot_timestamp = $slot_time->format('U');
                        
                        // Calcular diferencia en minutos
                        $diff_minutes = ($slot_timestamp - $current_time) / 60;
                        
                        if ($diff_minutes < $minimum_minutes) {
                            // Si hay menos del tiempo mínimo configurado hasta el slot, eliminarlo
                            unset($times[$time]);
                            error_log('Slot eliminado por regla de minutos mínimos (' . $minimum_minutes . ' min): ' . $date . ' ' . $time);
                        }
                        $found_first_slot = true; // Marcar que ya procesamos el primer slot
                    }
                }
            }

            // PRIMERA FASE: Aplicar bloqueos para todas las fechas
            foreach (array_keys($times) as $time) {
                error_log('Procesando horario: ' . $time . ' para fecha: ' . $date);
                
                // Primero verificar si es un horario en punto (HH:00)
                if (preg_match('/^\d{1,2}:00$/', $time)) {
                    unset($times[$time]);
                    error_log('Removiendo horario en punto - ' . $time);
                    continue;
                }

                // Log the comparison details
                error_log('Comparando horario ' . $time . ' con bloqueos: ' . print_r($blocks_cache[$date], true));
                
                // Comparar directamente con el tiempo original (HH:10)
                if (in_array($time, $blocks_cache[$date])) {
                    unset($times[$time]);
                    error_log('Removiendo horario bloqueado - ' . $time . ' encontrado en la lista de bloqueos');
                    continue;
                } else {
                    error_log('Horario ' . $time . ' no está bloqueado, se mantiene disponible');
                }
            }

            // SEGUNDA FASE: Aplicar reglas de madrugada solo para el día siguiente
            if ($date === $tomorrow_date) {
                foreach (array_keys($times) as $time) {
                    $normalized_time = substr($time, 0, 5);

                    error_log('Comparando madrugada:');
                    error_log('Hora normalizada: ' . $normalized_time);
                    error_log('Target time 1: ' . $target_time);
                    error_log('Target time 2: ' . $target_time_2);

                    // Procesar slots del día siguiente
                    if ($normalized_time === $target_time || $normalized_time === str_pad($target_time, 5, '0', STR_PAD_LEFT)) {
                        // Crear DateTime para el slot en la zona horaria correcta
                        $slot_datetime = new DateTime("$date $time:00", $this->timezone);
                        $time_difference = $slot_datetime->format('U') - $current_time;
                        
                        error_log(sprintf(
                            'Comparando horario %s - Diferencia: %d segundos (requerido: %d)',
                            $time,
                            $time_difference,
                            $min_required_time
                        ));

                        if ($time_difference < $min_required_time) {
                            unset($times[$time]);
                            error_log('Removiendo horario por tiempo mínimo 1 - ' . $time);
                            continue;
                        }
                    }

                    if ($conditional_enabled && 
                        ($normalized_time === $target_time_2 || $normalized_time === str_pad($target_time_2, 5, '0', STR_PAD_LEFT))) {
                        // Crear DateTime para el slot en la zona horaria correcta
                        $slot_datetime = new DateTime("$date $time:00", $this->timezone);
                        $time_difference = $slot_datetime->format('U') - $current_time;

                        error_log(sprintf(
                            'Comparando horario condicional %s - Diferencia: %d segundos (requerido: %d)',
                            $time,
                            $time_difference,
                            $min_required_time_2
                        ));

                        if ($time_difference < $min_required_time_2) {
                            unset($times[$time]);
                            error_log('Removiendo horario por tiempo mínimo 2 - ' . $time);
                        }
                    }
                }
            }

            // Remove date if no times left
            if (empty($times)) {
                unset($resultData['slots'][$date]);
                error_log('Removiendo fecha por no tener horarios - ' . $date);
            }
        }

        return $resultData;
    }
} 
<?php
/**
 * Plugin Name: Amelia Slots Blocker
 * Description: Gestiona los tiempos mínimos para reservas en horarios específicos.
 * Version: 1.0.1
 * Author: Rocash
 * Text Domain: slots-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main plugin class
require_once ASM_PLUGIN_DIR . 'includes/class-slots-manager.php';

// Activation hook
register_activation_hook(__FILE__, array('Slots_Manager', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('Slots_Manager', 'deactivate'));

// Initialize the plugin
function asm_init() {
    $slots_manager = new Slots_Manager();
    $slots_manager->init();
}
add_action('plugins_loaded', 'asm_init'); 
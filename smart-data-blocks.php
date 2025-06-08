<?php

/**
 * Plugin Name: Smart Data Blocks
 * Description: A simple ACF-style repeater plugin for custom admin fields.
 * Version: 1.0
 * Author: Jatin Pal
 */

defined('ABSPATH') || exit;

// Define constants
define('SDB_PATH', plugin_dir_path(__FILE__));
define('SDB_URL', plugin_dir_url(__FILE__));
define('SDB_VER', '1.0');

// Includes
require_once SDB_PATH . 'includes/db-schema.php';
require_once SDB_PATH . 'includes/ajax.php';
require_once SDB_PATH . 'admin/class-metaboxes.php';
require_once SDB_PATH . 'admin/class-admin.php';

// Activation hook
register_activation_hook(__FILE__, function () {
    sdb_create_database_tables();
    // flush_rewrite_rules();
});

// Initialize plugin
add_action('plugins_loaded', function () {
    if (is_admin()) {
        new SDB_Admin();
    }

    // Frontend field display
    require_once SDB_PATH . 'includes/field-rendering.php';
});

<?php
// \admin\class-admin.php

class SDB_Admin
{
    private $metaboxes;
    public function __construct()
    {
        $this->metaboxes = new SDB_Metaboxes();
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function admin_menu()
    {
        add_menu_page(
            'Smart Blocks',
            'Smart Blocks',
            'manage_options',
            'smart-blocks',
            [$this, 'render_groups_page'],
            'dashicons-screenoptions',
            25
        );

        // Corrected submenu page registration
        add_submenu_page(
            null,
            'Manage Fields',
            'Manage Fields',
            'manage_options',
            'sdb-fields',
            [$this, 'render_fields_page']
        );

        // Hide the duplicate submenu item
        remove_submenu_page('smart-blocks', 'sdb-fields');
    }

    public function enqueue_assets($hook)
    {
        // Only load on our plugin pages
        $valid_pages = ['toplevel_page_smart-blocks', 'admin_page_sdb-fields'];

        if (in_array($hook, $valid_pages)) {
            wp_enqueue_style('sdb-admin', SDB_URL . 'admin/assets/css/admin.css', [], SDB_VER);

            wp_enqueue_script(
                'sdb-admin',
                SDB_URL . 'admin/assets/js/admin.js',
                ['jquery', 'wp-util'],
                SDB_VER,
                true
            );

            wp_localize_script('sdb-admin', 'sdb_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sdb_admin_nonce')
            ]);
        }
    }

    public function render_groups_page()
    {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once SDB_PATH . 'admin/settings-page.php';
    }

    public function render_fields_page()
    {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once SDB_PATH . 'admin/settings-fields.php';
    }
}

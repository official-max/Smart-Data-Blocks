<?php
// admin/settings-fields.php

global $wpdb;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) {
    echo '<div class="notice notice-error"><p>No group selected.</p></div>';
    return;
}

// Fetch group
$group = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_groups WHERE id = %d", $group_id)
);
if (!$group) {
    echo '<div class="notice notice-error"><p>Invalid group selected.</p></div>';
    return;
}

// Save Fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sdb_fields_nonce']) && wp_verify_nonce($_POST['sdb_fields_nonce'], 'sdb_save_fields')) {
    $fields = $_POST['fields'] ?? [];

    $existing_ids = []; // collect IDs to preserve

    // Insert new fields
    foreach ($fields as $sort => $field) {
        $field_id = isset($field['id']) ? intval($field['id']) : 0;

        $config = [
            'label' => sanitize_text_field($field['label'] ?? ''),
            'name' => sanitize_title($field['name'] ?? ''),
            'type' => sanitize_text_field($field['type'] ?? 'text'),
        ];

        // For repeater type, save nested fields JSON string
        if ($config['type'] === 'repeater' && !empty($field['sub_fields'])) {
            $sub_fields = [];
            foreach ($field['sub_fields'] as $sub_field) {
                $sub_fields[] = [
                    'label' => sanitize_text_field($sub_field['label'] ?? ''),
                    'name' => sanitize_title($sub_field['name'] ?? ''),
                    'type' => sanitize_text_field($sub_field['type'] ?? 'text'),
                ];
            }
            $config['sub_fields'] = $sub_fields; // sari field ka data ek he field mai dalkr json mai add krdiya

        }

        if ($field_id > 0) {
            // Existing field → update
            $wpdb->update("{$wpdb->prefix}sdb_fields", [
                'config'     => wp_json_encode($config),
                'sort_order' => $sort,
            ], ['id' => $field_id], ['%s', '%d'], ['%d']);

            $existing_ids[] = $field_id;
        } else {
            // New field → insert
            $wpdb->insert("{$wpdb->prefix}sdb_fields", [
                'group_id'   => $group_id,
                'config'     => wp_json_encode($config),
                'sort_order' => $sort,
            ]);
            $existing_ids[] = $wpdb->insert_id;
        }

        // Delete removed fields (those not in submitted list) Delete in database
        // if (!empty($existing_ids)) {
        //     $placeholders = implode(',', array_fill(0, count($existing_ids), '%d'));
        //     $sql = "DELETE FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d AND id NOT IN ($placeholders)";
        //     $wpdb->query($wpdb->prepare($sql, array_merge([$group_id], $existing_ids)));
        // }
    }

    echo '<div class="notice notice-success"><p>Fields saved successfully!</p></div>';
}


// Fetch all fields for UI
$fields = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d ORDER BY sort_order ASC", $group_id)
);

?>

<div class="wrap">
    <h1>Manage Fields: <?= esc_html($group->name); ?></h1>

    <form method="post" id="sdb-fields-form">
        <?php wp_nonce_field('sdb_save_fields', 'sdb_fields_nonce'); ?>
        <input type="hidden" name="group_id" value="<?= esc_attr($group_id); ?>" />

        <div id="sdb-fields-container">
            <?php foreach ($fields as $index => $field):

                $config = json_decode($field->config, true);
            ?>
                <div class="sdb-field <?= ($config['type'] === 'repeater') ? 'repeater-field-group' : '' ?>" data-index="<?= $index ?>">
                    <input type="hidden" name="fields[<?= $index ?>][id]" value="<?= esc_attr($field->id); ?>" />

                    <div class="field-group">
                        <p>
                            <input type="text" name="fields[<?= $index ?>][label]" placeholder="Field Label" value="<?= esc_attr($config['label'] ?? '') ?>" />
                            <input type="text" name="fields[<?= $index ?>][name]" placeholder="field_name" value="<?= esc_attr($config['name'] ?? '') ?>" />
                            <select name="fields[<?= $index ?>][type]" class="field-type-select">
                                <option value="text" <?= selected($config['type'], 'text') ?>>Text</option>
                                <option value="textarea" <?= selected($config['type'], 'textarea') ?>>Textarea</option>
                                <option value="image" <?= selected($config['type'], 'image') ?>>Image</option>
                                <option value="editor" <?= selected($config['type'], 'editor') ?>>Editor</option>
                                <option value="repeater" <?= selected($config['type'], 'repeater') ?>>Repeater</option>
                            </select>

                            <button
                                type="button"
                                class="remove-field button"
                                onclick="removeField(this, <?= esc_js($field->id); ?>)">
                                Remove
                            </button>

                        </p>

                        <!-- Sub-fields container for repeater -->
                        <div class="sub-fields-container" style="<?= ($config['type'] === 'repeater') ? 'display:block;' : 'display:none;' ?>">
                            <h4>Sub Fields (Repeater)</h4>
                            <div class="sub-fields-list">
                                <?php if (!empty($config['sub_fields']) && is_array($config['sub_fields'])): ?>
                                    <?php foreach ($config['sub_fields'] as $sub_index => $sub_field): ?>
                                        <div class="sub-field">
                                            <input type="text" name="fields[<?= $index ?>][sub_fields][<?= $sub_index ?>][label]" placeholder="Sub Field Label" value="<?= esc_attr($sub_field['label']) ?>" />
                                            <input type="text" name="fields[<?= $index ?>][sub_fields][<?= $sub_index ?>][name]" placeholder="sub_field_name" value="<?= esc_attr($sub_field['name']) ?>" />
                                            <select name="fields[<?= $index ?>][sub_fields][<?= $sub_index ?>][type]" class="field-type-select">
                                                <option value="text" <?= selected($sub_field['type'], 'text') ?>>Text</option>
                                                <option value="textarea" <?= selected($sub_field['type'], 'textarea') ?>>Textarea</option>
                                                <option value="image" <?= selected($sub_field['type'], 'image') ?>>Image</option>
                                            </select>
                                            <button type="button" class="remove-sub-field button">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="add-sub-field button">+ Add Sub Field</button>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <p><button type="button" class="button" id="add-field">+ Add Field</button></p>
        <p><input type="submit" class="button button-primary" value="Save Fields"></p>
    </form>
</div>

<script>
    // Add Fields
    function addField(container, index) {
        const html = `
            <div class="sdb-field" data-index="${index}">
                <div class="field-group">
                    <p>
                        <input type="text" name="fields[${index}][label]" placeholder="Field Label" />
                        <input type="text" name="fields[${index}][name]" placeholder="field_name" />
                        <select name="fields[${index}][type]" class="field-type-select">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="image">Image</option>
                            <option value="editor">Editor</option>
                            <option value="repeater">Repeater</option>
                        </select>
                        <button 
                            type="button"  
                            class="remove-field button"
                            onclick="removeField(this, 0)">
                            Remove
                        </button>
                    </p>
                    <div class="sub-fields-container" style="display:none;">
                        <h4>Sub Fields (Repeater)</h4>
                        <div class="sub-fields-list"></div>
                        <button 
                            type="button" 
                            class="add-sub-field button ">
                            + Add Sub Field
                        </button>
                    </div>
            </div>

            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        index++;
    }


    // Remove Fields
    function removeField(button, fieldId) {
        const fieldWrapper = button.closest('.sdb-field');
        if (fieldId) {
            if (confirm("Are you sure you want to delete this field from the database?")) {
                jQuery.ajax({
                    url: sdb_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sdb_delete_field',
                        field_id: fieldId,
                        nonce: sdb_admin.nonce
                    },
                    success: function(res) {
                        if (res.success) {
                            fieldWrapper.remove();
                        } else {
                            alert('Failed to delete field.');
                        }
                    }
                });
            }
        } else {
            // new unsaved field, just remove from UI
            fieldWrapper.remove();
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let index = <?= count($fields) ?>;
        const container = document.getElementById('sdb-fields-container');

        // Add new main field
        document.getElementById('add-field').addEventListener('click', addField.bind(null, container, index));

        // Show/hide sub-fields container on type change
        container.addEventListener('change', (e) => {
            if (e.target.classList.contains('field-type-select')) {
                const sdbField = e.target.closest('.sdb-field');
                const subFieldsContainer = sdbField.querySelector('.sub-fields-container');
                if (e.target.value === 'repeater') {
                    subFieldsContainer.style.display = 'block';
                    sdbField.classList.add('repeater-field-group');
                } else {
                    subFieldsContainer.style.display = 'none';
                }
            }
        });

        // Add sub field inside repeater
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-sub-field')) {
                e.preventDefault();

                const sdbField = e.target.closest('.sdb-field');
                const subFieldsList = sdbField.querySelector('.sub-fields-list');
                const mainIndex = sdbField.getAttribute('data-index');
                const subIndex = subFieldsList.children.length;

                const html = `
                <div class="sub-field">
                    <input type="text" name="fields[${mainIndex}][sub_fields][${subIndex}][label]" placeholder="Sub Field Label" />
                    <input type="text" name="fields[${mainIndex}][sub_fields][${subIndex}][name]" placeholder="sub_field_name" />
                    <select name="fields[${mainIndex}][sub_fields][${subIndex}][type]" class="field-type-select">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="image">Image</option>
                    </select>
                    <button type="button" class="remove-sub-field button">Remove</button>
                </div>`;

                subFieldsList.insertAdjacentHTML('beforeend', html);
            }

            // Remove sub field
            if (e.target.classList.contains('remove-sub-field')) {
                e.target.closest('.sub-field').remove();
            }
        });
    });
</script>
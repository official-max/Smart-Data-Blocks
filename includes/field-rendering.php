<?php
// includes/field-rendering.php

function sdb_render_fields($group_key, $post_id = null)
{
    global $wpdb;
    if (!$post_id) $post_id = get_the_ID();

    $group = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_groups WHERE key_slug = %s", $group_key)
    );

    if (!$group) return '';

    $fields = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d ORDER BY sort_order ASC", $group->id)
    );

    if (!$fields) return '';

    ob_start();
    echo '<div class="sdb-frontend-fields">';

    foreach ($fields as $field) {
        $config = json_decode($field->config, true);
        $meta_key = 'sdb_field_' . $field->id;
        $value = get_post_meta($post_id, $meta_key, true);

        $label = esc_html($config['label'] ?? '');
        $type = $config['type'] ?? 'text';

        switch ($type) {
            case 'text':
                echo "<p><strong>{$label}:</strong> " . esc_html($value) . "</p>";
                break;

            case 'textarea':
            case 'editor':
                echo "<div><strong>{$label}:</strong><div>" . wpautop(wp_kses_post($value)) . "</div></div>";
                break;

            case 'image':
                if ($value) {
                    echo "<div><img src='" . esc_url($value) . "' alt='{$label}' style='max-width:100%; width: 100px;' /></div>";
                }
                break;

            case 'repeater':
                $repeater_items = $value ? json_decode($value, true) : [];
                if (!empty($repeater_items)) {
                    echo "<div class='sdb-repeater-block'>";
                    echo "<h4>{$label}</h4>";
                    foreach ($repeater_items as $item) {
                        echo "<div class='sdb-repeater-item'>";
                        foreach ($config['sub_fields'] as $sub) {
                            $sub_label = esc_html($sub['label']);
                            $sub_name = $sub['name'];
                            $sub_value = esc_html($item[$sub_name] ?? '');
                            echo "<p><strong>{$sub_label}:</strong> {$sub_value}</p>";
                        }
                        echo "</div>";
                    }
                    echo "</div>";
                }
                break;
        }
    }

    echo '</div>';
    return ob_get_clean();
}

// ✅ Shortcode wrapper
add_shortcode('sdb_fields', function ($atts) {
    $atts = shortcode_atts(['group' => '', 'id' => null], $atts);
    return sdb_render_fields($atts['group'], $atts['id']);
});



function sdb_get_field($group_key = '', $field_name = null, $post_id = null)
{
    global $wpdb;

    if (!$post_id) $post_id = get_the_ID(); // Same Post ki post Id Get
    if (!$post_id || !$group_key) return null;


    // Get group ID
    $group = $wpdb->get_row(
        $wpdb->prepare("SELECT id FROM {$wpdb->prefix}sdb_groups WHERE key_slug = %s", $group_key)
    );

    if (!$group) return null;

    // Get all fields in group
    $fields = $wpdb->get_results(
        $wpdb->prepare("SELECT id, config FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d", $group->id)
    );

    if (!$fields) return null;

    // If specific field name is requested
    if ($field_name) {
        foreach ($fields as $field) {
            $config = json_decode($field->config, true);
            if ($config['name'] === $field_name) {
                $meta_key = 'sdb_field_' . $field->id;
                $value = get_post_meta($post_id, $meta_key, true);

                return ($config['type'] === 'repeater' && is_string($value)) ? json_decode($value, true) : $value;
            }
        }
        return null; // field not found
    }

    // If NO field name — return all fields in array
    $output = [];

    foreach ($fields as $field) {
        $config = json_decode($field->config, true);
        $key = $config['name'] ?? 'field_' . $field->id;
        $meta_key = 'sdb_field_' . $field->id;
        $value = get_post_meta($post_id, $meta_key, true);

        if ($config['type'] === 'repeater' && is_string($value)) {
            $output[$key] = json_decode($value, true);
        } else {
            $output[$key] = $value;
        }
    }

    return $output;
}

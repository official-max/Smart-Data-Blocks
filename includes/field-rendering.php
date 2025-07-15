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
                echo "<p><strong>{$label}:</strong> " . $value . "</p>";
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
                            $sub_value = $item[$sub_name] ?? '';
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

    // Default to current post if no ID provided
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    // Return null if essential data is missing
    if (!$post_id || !$group_key) {
        return null;
    }

    // Get group ID from database
    $group = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sdb_groups WHERE key_slug = %s",
            $group_key
        )
    );

    if (!$group) {
        return null;
    }

    // Get all fields belonging to this group
    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, config FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d",
            $group->id
        )
    );

    if (!$fields) {
        return null;
    }

    /**
     * Process image value - converts ID to full attachment data
     * @param mixed $value
     * @return array|mixed
     */
    $process_image_value = function ($value) {
        if (is_numeric($value)) {
            $attachment_id = (int)$value;
            $attachment = wp_get_attachment_metadata($attachment_id);

            if ($attachment) {
                return [
                    'id'          => $attachment_id,
                    'url'         => wp_get_attachment_url($attachment_id),
                    'alt'         => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                    'title'       => get_the_title($attachment_id),
                    'caption'     => wp_get_attachment_caption($attachment_id),
                    'description' => get_post_field('post_content', $attachment_id),
                    'sizes'       => $attachment['sizes'] ?? [],
                    'width'       => $attachment['width'] ?? '',
                    'height'      => $attachment['height'] ?? '',
                    'thumbnail'   => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                    'medium'      => wp_get_attachment_image_url($attachment_id, 'medium'),
                    'large'       => wp_get_attachment_image_url($attachment_id, 'large')
                ];
            }
        }
        return $value;
    };

    /**
     * Process gallery value - converts JSON string to array of image data
     * @param mixed $value
     * @return array
     */
    $process_gallery_value = function ($value) use ($process_image_value) {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return [];
        }

        $gallery = [];
        foreach ($value as $image_id) {
            if ($image_data = $process_image_value($image_id)) {
                $gallery[] = $image_data;
            }
        }
        return $gallery;
    };

    // If specific field is requested
    if ($field_name) {
        foreach ($fields as $field) {
            $config = json_decode($field->config, true);

            if ($config['name'] === $field_name) {
                $meta_key = 'sdb_field_' . $field->id;
                $value = get_post_meta($post_id, $meta_key, true);

                switch ($config['type']) {
                    case 'repeater':
                        if (is_string($value)) {
                            $repeater_data = json_decode($value, true);

                            if (is_array($repeater_data) && !empty($config['sub_fields'])) {
                                foreach ($repeater_data as &$item) {
                                    foreach ($config['sub_fields'] as $sub_field) {
                                        if (isset($item[$sub_field['name']])) {
                                            if ($sub_field['type'] === 'image') {
                                                $item[$sub_field['name']] = $process_image_value($item[$sub_field['name']]);
                                            } elseif ($sub_field['type'] === 'gallery') {
                                                $item[$sub_field['name']] = $process_gallery_value($item[$sub_field['name']]);
                                            }
                                        }
                                    }
                                }
                            }
                            return $repeater_data;
                        }
                        return $value;

                    case 'image':
                        return $process_image_value($value);

                    case 'gallery':
                        return $process_gallery_value($value);

                    default:
                        return $value;
                }
            }
        }
        return null;
    }

    // If no specific field requested - return all fields
    $output = [];

    foreach ($fields as $field) {
        $config = json_decode($field->config, true);
        $key = $config['name'] ?? 'field_' . $field->id;
        $meta_key = 'sdb_field_' . $field->id;
        $value = get_post_meta($post_id, $meta_key, true);

        switch ($config['type']) {
            case 'repeater':
                if (is_string($value)) {
                    $repeater_data = json_decode($value, true);

                    if (is_array($repeater_data) && !empty($config['sub_fields'])) {
                        foreach ($repeater_data as &$item) {
                            foreach ($config['sub_fields'] as $sub_field) {
                                if (isset($item[$sub_field['name']])) {
                                    if ($sub_field['type'] === 'image') {
                                        $item[$sub_field['name']] = $process_image_value($item[$sub_field['name']]);
                                    } elseif ($sub_field['type'] === 'gallery') {
                                        $item[$sub_field['name']] = $process_gallery_value($item[$sub_field['name']]);
                                    }
                                }
                            }
                        }
                    }
                    $output[$key] = $repeater_data;
                } else {
                    $output[$key] = $value;
                }
                break;

            case 'image':
                $output[$key] = $process_image_value($value);
                break;

            case 'gallery':
                $output[$key] = $process_gallery_value($value);
                break;

            default:
                $output[$key] = $value;
                break;
        }
    }

    return $output;
}






// function sdb_get_field($group_key = '', $field_name = null, $post_id = null)
// {
//     global $wpdb;

//     if (!$post_id) $post_id = get_the_ID();
//     if (!$post_id || !$group_key) return null;

//     $group = $wpdb->get_row(
//         $wpdb->prepare("SELECT id FROM {$wpdb->prefix}sdb_groups WHERE key_slug = %s", $group_key)
//     );
//     if (!$group) return null;

//     $fields = $wpdb->get_results(
//         $wpdb->prepare("SELECT id, config FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d", $group->id)
//     );
//     if (!$fields) return null;

//     // Specific field
//     if ($field_name) {
//         foreach ($fields as $field) {
//             $config = json_decode($field->config, true);
//             if ($config['name'] === $field_name) {
//                 $meta_key = 'sdb_field_' . $field->id;
//                 $value = get_post_meta($post_id, $meta_key, true);

//                 if ($config['type'] === 'repeater' && is_string($value)) {
//                     $decoded = json_decode($value, true);

//                     // 🔁 decode repeater subfield values
//                     foreach ($decoded as &$item) {
//                         foreach ($item as &$val) {
//                             if (is_string($val)) {
//                                 $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
//                             }
//                         }
//                     }
//                     return $decoded;
//                 } else {
//                     return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
//                 }
//             }
//         }
//         return null;
//     }

//     // All fields
//     $output = [];
//     foreach ($fields as $field) {
//         $config = json_decode($field->config, true);
//         $key = $config['name'] ?? 'field_' . $field->id;
//         $meta_key = 'sdb_field_' . $field->id;
//         $value = get_post_meta($post_id, $meta_key, true);

//         if ($config['type'] === 'repeater' && is_string($value)) {
//             $decoded = json_decode($value, true);

//             // 🔁 decode repeater subfield values
//             foreach ($decoded as &$item) {
//                 foreach ($item as &$val) {
//                     if (is_string($val)) {
//                         $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
//                     }
//                 }
//             }

//             $output[$key] = $decoded;
//         } else {
//             $output[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
//         }
//     }

//     return $output;
// }

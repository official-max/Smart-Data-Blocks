<?php
// admin/class-metaboxes.php

class SDB_Metaboxes
{

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_conditional_metaboxes']);
        add_action('save_post', [$this, 'save_metabox_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }


    private function sdb_encode_quotes($string)
    {
        return str_replace(
            ['"', "'"],
            ['&quot;', '&#039;'],
            $string
        );
    }

    public function enqueue_admin_scripts()
    {
        wp_enqueue_media();

        wp_enqueue_script(
            'sdb-metaboxes-js',
            plugin_dir_url(__FILE__) . 'assets/js/metaboxes.js',
            ['jquery'],
            SDB_VER,
            true
        );

        wp_enqueue_style(
            'sdb-metaboxes-css',
            plugin_dir_url(__FILE__) . 'assets/css/metaboxes.css',
            [],
            SDB_VER
        );


        global $wpdb;
        $fields = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sdb_fields");

        wp_localize_script('sdb-metaboxes-js', 'sdbMetaboxData', [
            'fields' => $fields,
        ]);
    }

    public function add_conditional_metaboxes($post)
    {
        if (is_string($post)) {
            $post = get_post();
        }
        if (!$post) return;

        global $wpdb;
        $groups = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sdb_groups");
        if (!$groups) return;

        foreach ($groups as $group) {
            $location_rules = json_decode($group->location, true);
            if (!$location_rules) continue;

            foreach ($location_rules as $rule) {
                if ($this->location_rule_match($rule, $post)) {
                    add_meta_box(
                        'sdb_group_' . $group->id,
                        'Smart Block Group: ' . esc_html($group->name),
                        [$this, 'render_group_metabox'],
                        null,
                        'normal',
                        'default',
                        ['group_id' => $group->id]
                    );
                    break;
                }
            }
        }
    }

    private function location_rule_match($rule, $post)
    {
        if (!isset($rule['param'], $rule['operator'], $rule['value'])) return false;

        $param = $rule['param'];
        $operator = $rule['operator'];
        $value = $rule['value'];

        switch ($param) {
            case 'post_type':
                $current = get_post_type($post);
                break;
            case 'post':
                $current = $post->ID;
                break;
            case 'page_template':
                if ($post->post_type !== 'page') return false;
                $current = get_page_template_slug($post->ID);
                break;
            default:
                return false;
        }

        switch ($operator) {
            case '==':
                return $current == $value;
            case '!=':
                return $current != $value;
            default:
                return false;
        }
    }

    public function render_group_metabox($post, $metabox)
    {
        global $wpdb;

        $group_id = $metabox['args']['group_id'] ?? 0;
        if (!$group_id) {
            echo 'Invalid Group ID';
            return;
        }

        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_fields WHERE group_id = %d ORDER BY sort_order ASC", $group_id));

        echo '<div class="sdb-metabox-fields">';

        foreach ($fields as $field) {
            $config = json_decode($field->config, true);
            $field_name = 'sdb_fields[' . esc_attr($field->id) . ']';
            $meta_key = 'sdb_field_' . $field->id;
            $value = get_post_meta($post->ID, $meta_key, true);
            $label = esc_html($config['label'] ?? '');

            switch ($config['type']) {
                case 'text':
?>
                    <p>
                        <label for="<?= $meta_key ?>"><?= $label ?></label><br>
                        <input type="text" name="<?= $field_name ?>" id="<?= $meta_key ?>" value="<?= esc_attr($value); ?>" />
                    </p>
                <?php
                    break;

                case 'textarea':
                ?>
                    <p>
                        <label for="<?= $meta_key ?>"><?= $label ?></label><br>
                        <textarea name="<?= $field_name ?>" id="<?= $meta_key ?>" rows="4"><?= esc_textarea($value); ?></textarea>

                    </p>
                <?php
                    break;

                case 'image':
                    $saved_value = $value ?: '';
                    $image_url = '';

                    if (is_numeric($saved_value)) {
                        // If it's an attachment ID, try to get the image URL
                        $attachment_url = wp_get_attachment_url($saved_value);
                        if ($attachment_url) {
                            $image_url = $attachment_url;
                        }
                    } else {
                        // Assume it's a direct URL
                        $image_url = $saved_value;
                    }

                    $display_url = esc_url($image_url);
                ?>
                    <p>
                        <label><?= esc_html($label) ?></label><br>

                        <img src="<?= $display_url ?>" alt="" id="<?= esc_attr($meta_key) ?>_preview" style="max-width:100%; height:auto;" />
                        <input type="hidden" name="<?= esc_attr($field_name) ?>" id="<?= esc_attr($meta_key) ?>" value="<?= esc_attr($saved_value); ?>" />

                        <button type="button" class="button sdb-upload-image" data-target="<?= esc_attr($meta_key) ?>">
                            <?= $display_url ? 'Change Image' : 'Select Image' ?>
                        </button>

                        <?php if ($display_url): ?>
                            <button type="button" class="button sdb-remove-image" data-target="<?= esc_attr($meta_key) ?>">Remove Image</button>
                        <?php endif; ?>
                    </p>
                <?php
                    break;


                case 'gallery':
                    $gallery_data = $value ? json_decode($value, true) : [];
                ?>
                    <div class="sdb-gallery" data-field-id="<?= esc_attr($field->id) ?>">
                        <label><?= $label ?></label>
                        <div class="sdb-gallery-thumbnails">
                            <?php if (!empty($gallery_data)) : ?>
                                <?php foreach ($gallery_data as $image_id) : ?>
                                    <?php if (is_numeric($image_id)) : ?>
                                        <?php $image_url = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
                                        <div class="sdb-gallery-thumb" data-id="<?= esc_attr($image_id) ?>">
                                            <img src="<?= esc_url($image_url) ?>">
                                            <button type="button" class="sdb-remove-gallery-image">&times;</button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden"
                            name="<?= $field_name ?>"
                            id="<?= $meta_key ?>"
                            value="<?= esc_attr($value) ?>">
                        <button type="button" class="button sdb-add-gallery-images">
                            <?= empty($gallery_data) ? 'Add Gallery Images' : 'Edit Gallery' ?>
                        </button>
                    </div>
                <?php
                    break;


                case 'editor':
                ?>
                    <p>
                        <label for="<?= $meta_key ?>"><?= $label ?></label><br>
                        <?php
                        wp_editor(
                            $value,
                            $meta_key,
                            [
                                'textarea_name' => $field_name,
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'tinymce'       => true,
                                'quicktags'     => true,
                            ]
                        );
                        ?>
                    </p>
                <?php
                    break;

                case 'repeater':
                    $repeater_data = $value ? json_decode($value, true) : [];
                    $sub_fields = $config['sub_fields'] ?? [];
                ?>
                    <div class="sdb-repeater" data-field-id="<?= esc_attr($field->id) ?>" data-field-name="<?= esc_attr($field_name) ?>">
                        <label><?= $label ?></label>
                        <div class="sdb-repeater-items">
                            <?php
                            if ($repeater_data && is_array($repeater_data)) {
                                foreach ($repeater_data as $i => $item) {
                                    echo '<div class="sdb-repeater-item">';
                                    foreach ($sub_fields as $subfield) {
                                        $subname = $subfield['name'];
                                        $sublabel = $subfield['label'];
                                        $subtype = $subfield['type'];
                                        $subval = $item[$subname] ?? '';

                                        echo '<p><label>' . esc_html($sublabel) . '</label><br>';

                                        switch ($subtype) {

                                            case 'textarea':
                                                echo '<p><textarea name="' . $field_name . '[' . $i . '][' . esc_attr($subname) . ']" rows="3">' . esc_textarea($subval) . '</textarea></p>';
                                                break;

                                            case 'image':
                                                $image_url = '';
                                                if (is_numeric($subval)) {
                                                    $attachment_url = wp_get_attachment_url(absint($subval));
                                                    if ($attachment_url) {
                                                        $image_url = $attachment_url;
                                                    }
                                                } else {
                                                    $image_url = esc_url_raw($subval);
                                                }

                                                $input_id = esc_attr("repeater_{$field->id}_{$i}_{$subname}");
                                                $button_label = $image_url ? 'Change Image' : 'Select Image';

                                                echo <<<HTML
                                                        <p>
                                                            <img src="{$image_url}" alt="" id="{$input_id}_preview" style="max-width:100%; height:auto;" />
                                                            <input type="hidden" name="{$field_name}[{$i}][{$subname}]" id="{$input_id}" value="{$subval}" />
                                                            <button type="button" class="button sdb-upload-image" data-target="{$input_id}"> {$button_label} </button>
                                                            <button type="button" class="button sdb-remove-image" data-target="{$input_id}">Remove Image</button>
                                                        </p>
                                                    HTML;
                                                break;
                                            default: // text
                                                echo '<input type="text" name="' . $field_name . '[' . $i . '][' . esc_attr($subname) . ']" value="' . ($subval) . '" />';
                                                break;
                                        }

                                        echo '</p>';
                                    }
                                    echo '<button type="button" class="button dashicons dashicons-remove sdb-remove-repeater-item" title="Remove"></button>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>

                        <button type="button" class="button sdb-add-repeater-item">+ Add Item +</button>
                    </div>
<?php
                    break;


                default:
                    echo '<p>Unknown field type: ' . esc_html($config['type']) . '</p>';
                    break;
            }
        }

        echo '</div>';
    }

    public function save_metabox_data($post_id)
    {
        // Check autosave and permissions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Verify field data exists
        if (!isset($_POST['sdb_fields']) || !is_array($_POST['sdb_fields'])) return;

        global $wpdb;

        foreach ($_POST['sdb_fields'] as $field_id => $value) {
            // Get field config from database
            $field = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sdb_fields WHERE id = %d",
                intval($field_id)
            ));

            if (!$field) continue;

            $config = json_decode($field->config, true);
            $type = $config['type'] ?? 'text';
            $meta_key = 'sdb_field_' . intval($field_id);

            switch ($type) {
                case 'text':
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_kses_post($value)
                    );
                    break;

                case 'textarea':
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_kses_post($value)
                    );
                    break;

                case 'editor':
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_kses_post($value)
                    );
                    break;

                case 'image':
                    // 1 · Sanitise ------------------------------------
                    if (empty($value)) {
                        $sanitized = '';                   // or null if you prefer
                    } elseif (is_numeric($value)) {
                        $sanitized = absint($value);       // ID
                    } else {
                        $sanitized = esc_url_raw($value);  // fallback URL
                    }

                    // 2 · Save ----------------------------------------
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        $sanitized
                    );
                    break;

                case 'gallery':
                    $sanitized_gallery = [];

                    if (is_array($value)) {
                        $sanitized_gallery = array_map('absint', $value);
                    } elseif (is_string($value) && $decoded = json_decode($value, true)) {
                        $sanitized_gallery = array_map('absint', $decoded);
                    }

                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_json_encode($sanitized_gallery)
                    );
                    break;

                case 'repeater':
                    $sanitized_items = [];

                    if (is_array($value) && !empty($config['sub_fields'])) {
                        foreach ($value as $item) {
                            $sanitized_item = [];

                            foreach ($config['sub_fields'] as $sub) {
                                $key = $sub['name'];
                                $sub_type = $sub['type'] ?? 'text';
                                $raw_val = isset($item[$key]) ? wp_unslash($item[$key]) : '';

                                switch ($sub_type) {
                                    case 'text':
                                    case 'textarea':
                                    case 'editor':
                                        $sanitized = wp_kses_post($raw_val);
                                        $encoded = str_replace(['"', "'"], ['&quot;', '&#039;'], $sanitized);
                                        $sanitized_item[$key] = $encoded;
                                        break;

                                    case 'image':
                                        if (empty($raw_val)) {
                                            $sanitized_item[$key] = '';
                                        } elseif (is_numeric($raw_val)) {
                                            $sanitized_item[$key] = absint($raw_val);
                                        } else {
                                            $sanitized_item[$key] = esc_url_raw($raw_val);
                                        }
                                        break;

                                    case 'gallery':
                                        $sanitized_gallery = [];
                                        if (is_array($raw_val)) {
                                            $sanitized_gallery = array_map('absint', $raw_val);
                                        }
                                        $sanitized_item[$key] = $sanitized_gallery;
                                        break;

                                    default:
                                        $sanitized_item[$key] = sanitize_text_field($raw_val);
                                        break;
                                }
                            }

                            $sanitized_items[] = $sanitized_item;
                        }
                    }

                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_json_encode(
                            $sanitized_items,
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                        )
                    );
                    break;

                default:
                    // Fallback sanitization
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        sanitize_text_field($value)
                    );
                    break;
            }
        }
    }
}

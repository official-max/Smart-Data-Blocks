<?php
// admin/class-metaboxes.php

class SDB_Metaboxes
{
    public function __construct() // Use hooks
    {
        add_action('add_meta_boxes', [$this, 'add_conditional_metaboxes']);
        add_action('save_post', [$this, 'save_metabox_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts() // Add js and css
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

        /* var_dump($post); // post type */

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
                    // $image_url = $value ? wp_get_attachment_url(6) : '';
                    $image_url = $value ? $value : '';
                    // var_dump($value);
                ?>
                    <p>
                        <label><?= $label ?></label><br>
                        <img src="<?= esc_url($image_url) ?>" alt="" id="<?= $meta_key ?>_preview" />
                        <input type="hidden" name="<?= $field_name ?>" id="<?= $meta_key ?>" value="<?= esc_attr($value); ?>" />
                        <button type="button" class="button sdb-upload-image" data-target="<?= $meta_key ?>">
                            <?= $image_url ? 'Change Image' : 'Select Image' ?>
                        </button>
                        <button type="button" class="button sdb-remove-image" data-target="<?= $meta_key ?>">Remove Image</button>
                    </p>
                <?php
                    break;

                case 'editor':
                    // 🧠 Create safe editor ID (no brackets, no dot)
                    $editor_id = 'sdb_editor_' . $field->id;
                ?>
                    <p>
                        <label for="<?= $editor_id ?>"><?= $label ?></label><br>
                        <?php
                        wp_editor(
                            $value,              // Saved value
                            $editor_id,          // ID for TinyMCE (must be safe)
                            [
                                'textarea_name' => $field_name, // Actual <textarea name=""> (brackets allowed here)
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
                        <label><?= esc_html($label) ?></label>
                        <div class="sdb-repeater-items">
                            <?php
                            if ($repeater_data && is_array($repeater_data)) {
                                foreach ($repeater_data as $i => $item) {
                                    echo '<div class="sdb-repeater-item">';
                                    foreach ($sub_fields as $subfield) {
                                        $subname  = $subfield['name'];
                                        $sublabel = $subfield['label'];
                                        $subtype  = $subfield['type'];
                                        $subval   = $item[$subname] ?? '';
                                        $input_name = $field_name . '[' . $i . '][' . esc_attr($subname) . ']';

                                        echo '<p><label>' . esc_html($sublabel) . '</label><br>';

                                        switch ($subtype) {
                                            case 'textarea':
                                                echo '<textarea name="' . esc_attr($input_name) . '" rows="3">' . esc_textarea($subval) . '</textarea>';
                                                break;

                                            case 'image':
                                                $img_url = esc_url($subval);
                                                $input_id = "repeater_{$field->id}_{$i}_" . esc_attr($subname);
                                                $button_label = $img_url ? 'Change Image' : 'Select Image';
                                                echo <<<HTML
                                                    <img src="{$img_url}" alt="" id="{$input_id}_preview" style="max-width: 100px; display: block;" />
                                                    <input type="hidden" name="{$input_name}" id="{$input_id}" value="{$img_url}" />
                                                    <button type="button" class="button sdb-upload-image" data-target="{$input_id}"> {$button_label} </button>
                                                    <button type="button" class="button sdb-remove-image" data-target="{$input_id}">Remove Image</button>
                                                HTML;
                                                break;

                                            case 'editor':
                                                $editor_id = 'sdb_editor_' . $field->id . '_' . $i . '_' . sanitize_key($subname);
                                                wp_editor(
                                                    $subval,
                                                    $editor_id,
                                                    [
                                                        'textarea_name' => $input_name,
                                                        'media_buttons' => true,
                                                        'teeny'         => false,
                                                        'quicktags'     => true,
                                                    ]
                                                );
                                                break;

                                            default: // text
                                                echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($subval) . '" />';
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
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!isset($_POST['sdb_fields']) || !is_array($_POST['sdb_fields'])) return;

        global $wpdb;

        foreach ($_POST['sdb_fields'] as $field_id => $value) {
            $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sdb_fields WHERE id = %d", intval($field_id)));
            if (!$field) continue;

            $config = json_decode($field->config, true);
            $type = $config['type'] ?? 'text';

            switch ($type) {
                case 'text':
                case 'textarea':
                    // Allow HTML for textarea fields using wp_kses_post
                    $sanitized = wp_kses_post($value);
                    update_post_meta($post_id, 'sdb_field_' . intval($field_id), $sanitized);
                    break;

                case 'editor':
                    // For the editor field, allow HTML tags using wp_kses_post
                    $sanitized_editor_value = wp_kses_post($value); // Allow HTML in the editor field
                    update_post_meta($post_id, 'sdb_field_' . intval($field_id), $sanitized_editor_value);
                    break;

                case 'image':
                    $url = esc_url_raw($value); // Sanitize URL
                    update_post_meta($post_id, 'sdb_field_' . intval($field_id), $url);
                    break;

                case 'repeater':
                    if (is_array($value)) {
                        $sanitized_items = [];
                        foreach ($value as $item) {
                            $sanitized_item = [];
                            foreach ($config['sub_fields'] as $sub) {
                                $key = $sub['name'];
                                $sanitized_item[$key] = wp_kses_post($item[$key] ?? '');
                            }
                            $sanitized_items[] = $sanitized_item;
                        }
                        $json_data = wp_json_encode($sanitized_items);
                        update_post_meta($post_id, 'sdb_field_' . intval($field_id), $json_data);
                    } else {
                        update_post_meta($post_id, 'sdb_field_' . intval($field_id), '');
                    }
                    break;

                default:
                    // Default sanitization, just in case you want to use sanitize_text_field for other field types
                    update_post_meta($post_id, 'sdb_field_' . intval($field_id), sanitize_text_field($value));
                    break;
            }
        }
    }
}

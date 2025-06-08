<?php
// \includes\ajax.php

// Register AJAX handlers
add_action('wp_ajax_sdb_fetch_location_values', function () {
    check_ajax_referer('sdb_admin_nonce', 'nonce');

    $param = sanitize_text_field($_POST['param']);

    $values = [];

    if ($param === 'post_type') {
        $post_types = get_post_types(['public' => true], 'objects');
        foreach ($post_types as $pt) {
            $values[$pt->name] = $pt->label;
        }
    } elseif ($param === 'post') {
        // List of pages + posts, or just pages if you want
        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        foreach ($posts as $post) {
            $values[$post->ID] = $post->post_title;
        }
    } elseif ($param === 'page_template') {
        // Get page templates available in theme
        $templates = wp_get_theme()->get_page_templates(null, 'page');
        foreach ($templates as $file => $name) {
            $values[$file] = $name;
        }
    }

    wp_send_json_success($values);
});



add_action('wp_ajax_sdb_delete_field', function () {
    check_ajax_referer('sdb_admin_nonce', 'nonce');

    $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;

    if ($field_id > 0) {
        global $wpdb;

        // Custom table se delete
        $deleted = $wpdb->delete("{$wpdb->prefix}sdb_fields", ['id' => $field_id]);

        // Agar successfully delete ho gaya toh postmeta se bhi hatao
        if ($deleted !== false) {
            $args = [
                'meta_key' => 'sdb_field_' . $field_id
            ];

            // Pehle post_id dhundho jisme ye meta key exist karta hai
            $posts = get_posts([
                'post_type'   => 'any',
                'meta_query'  => [['key' => $args['meta_key']]],
                'fields'      => 'ids', // Return Only Post IDS
                'numberposts' => -1
            ]);

            // Har post se meta delete karo
            foreach ($posts as $post_id) {
                delete_post_meta($post_id, $args['meta_key']);
            }

            wp_send_json_success();
        }
    }

    wp_send_json_error();
});

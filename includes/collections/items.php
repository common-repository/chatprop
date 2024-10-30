<?php
if (!defined('ABSPATH')) {
    exit;
}

function chatprop_send_all_posts_to_api_in_batches($batch_size = 100) {
    global $wpdb;

    $paged = 1;
    $has_posts = true;

    $excluded_post_types = array(
        'wp_navigation', 'attachment', 'revision', 'nav_menu_item',
        'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles'
    );

    while ($has_posts) {
        $query = new WP_Query(array(
            'post_type'      => 'any',
            'posts_per_page' => $batch_size,
            'paged'          => $paged,
            'post_status'    => 'any',
            'post_type__not_in' => $excluded_post_types,
        ));

        if ($query->have_posts()) {
            $posts_data = [];

            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                // Get the featured image (if available)
                $featured_image_id = get_post_thumbnail_id($post_id);
                $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : null;

                $post_data = array(
                    'ID'             => $post_id,
                    'post_title'     => get_the_title(),
                    'post_content'   => get_the_content(),
                    'post_type'      => get_post_type(),
                    'post_status'    => get_post_status(),
                    'post_url'       => get_permalink($post_id),
                    'post_date'      => get_the_date('c', $post_id),
                    'post_modified'  => get_post_modified_time('c', false, $post_id),
                    'meta'           => get_post_meta($post_id),
                    'featured_image' => $featured_image_url, // Add featured image URL
                );

                $posts_data[] = $post_data;
            }

            chatprop_send_posts_batch_to_api($posts_data);

            $paged++;
        } else {
            $has_posts = false;
        }

        wp_reset_postdata();
    }
}

function chatprop_send_posts_batch_to_api($posts_data) {
    $data = ['data' => $posts_data];
    $json_data = wp_json_encode($data);

    $url = CHATPROP_FUNCTIONS_URL . '/webhooks/wordpress/items';

    $request_args = array(
        'body' => $json_data,
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-access-token' => get_option(CHATPROP_OAUTH_TOKEN),
        ),
        'method' => 'POST',
    );

    $response = wp_remote_post($url, $request_args);

    if (is_wp_error($response)) {
        error_log('~ API request failed: ' . $response->get_error_message());
    } else {
        error_log('~ API request successful: ' . print_r($response, true));
    }
}

/**
 * Hook into the save_post action to send data to the API whenever a post is created or updated.
 */
add_action('save_post', 'chatprop_send_single_post_to_api', 10, 3);

function chatprop_send_single_post_to_api($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $excluded_post_types = array(
        'wp_navigation', 'attachment', 'revision', 'nav_menu_item',
        'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles'
    );

    if (in_array($post->post_type, $excluded_post_types)) {
        return;
    }

    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : null;

    $post_data = array(
        'ID'             => $post_id,
        'post_title'     => get_the_title($post_id),
        'post_content'   => get_the_content(null, false, $post_id),
        'post_type'      => $post->post_type,
        'post_status'    => $post->post_status,
        'post_url'       => get_permalink($post_id),
        'post_date'      => get_the_date('c', $post_id),
        'post_modified'  => get_post_modified_time('c', false, $post_id),
        'meta'           => get_post_meta($post_id),
        'featured_image' => $featured_image_url, // Add featured image URL
    );

    chatprop_send_posts_batch_to_api(array($post_data));
}

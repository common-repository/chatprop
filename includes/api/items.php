<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'chatprop_register_items_route');
function chatprop_register_items_route()
{
    try {
        register_rest_route('chatprop/v1', '/items', array(
            'methods' => 'POST',
            'callback' => 'chatprop_handle_items_route',
            'permission_callback' => '__return_true',
        ));
    } catch (Exception $e) {
        error_log('Error registering ChatProp route: ' . $e->getMessage());
    }
}


function chatprop_handle_items_route(WP_REST_Request $request)
{
    ob_start();
    $response_data = array(
        'message' => 'Items processing started'
    );

    ob_end_flush();
    flush();

    ignore_user_abort(true);
    chatprop_send_all_posts_to_api_in_batches(50);

    return new WP_REST_Response($response_data, 200);
}

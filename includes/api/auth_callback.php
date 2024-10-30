<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', 'chatprop_register_auth_callback');
function chatprop_register_auth_callback() {
    try {
        register_rest_route('chatprop/v1', '/auth/callback', array(
            'methods'  => 'POST',
            'callback' => 'chatprop_handle_auth_callback',
            'permission_callback' => '__return_true',
        ));
    } catch (Exception $e) {
        error_log('Error registering ChatProp route: ' . $e->getMessage());
    }
}



function chatprop_handle_auth_callback(WP_REST_Request $request ) {
    if ( $request->get_method() !== 'POST' ) {
        return new WP_Error( 'invalid_method', 'Only POST requests are allowed.', array( 'status' => 405 ) );
    }

    $params = $request->get_json_params();
    $token = isset( $params['token'] ) ? sanitize_text_field( $params['token'] ) : '';
    $state = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';
    $saved_state = get_option(CHATPROP_OAUTH_STATE);

    if ( empty( $token ) ) {
        return new WP_Error( 'missing_token', 'The "token" parameter is missing.', array( 'status' => 400 ) );
    }

    if ( empty( $state )  ) {
        return new WP_Error( 'missing_state', 'The "state" parameter is missing.', array( 'status' => 400 ) );
    }

    if ( $state != $saved_state  ) {
        return new WP_Error( 'Error', 'The "state" parameter is invalid.', array( 'status' => 400 ) );
    }

    update_option(CHATPROP_OAUTH_TOKEN, $token);
    delete_option(CHATPROP_OAUTH_STATE);

    chatprop_send_all_posts_to_api_in_batches(50);

    $response_data = array(
        'message' => 'Authentication successful'
    );
    return new WP_REST_Response( $response_data, 200 );
}

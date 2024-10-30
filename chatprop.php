<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
Plugin Name: ChatProp
Plugin URI: http://chatprop.io/wordpress
Description: ChatProp helps you to engage with your website visitors and convert them into customers. It is a chatbot that can be easily integrated into your website.
Version: 1.0
Author: ChatProp
Author URI: http://chatprop.io
License: GPL2
*/

require_once(plugin_dir_path(__FILE__) . 'includes/api/auth_callback.php');
require_once(plugin_dir_path(__FILE__) . 'includes/api/items.php');
require_once(plugin_dir_path(__FILE__) . 'includes/config.php');

register_activation_hook(__FILE__, 'chatprop_activation');

function chatprop_activation()
{
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to activate this plugin.');
    }

    update_option('chatprop_redirect_after_activation', true);

    // Initialize REST API and register auth callback
    do_action('rest_api_init');
    chatprop_register_auth_callback();
    chatprop_register_items_route();
}


add_action('admin_init', 'chatprop_redirect_after_activation');
function chatprop_redirect_after_activation()
{
    if (get_option('chatprop_redirect_after_activation')) {

        $chatprop_oauth_state = wp_generate_password(20, false);
        update_option(CHATPROP_OAUTH_STATE, $chatprop_oauth_state);

        $plugins_url = admin_url('plugins.php');
        $encoded_plugins_url = urlencode($plugins_url);

        $auth_callback_url = rest_url('chatprop/v1/auth/callback');
        $encoded_auth_callback_url = urlencode($auth_callback_url);

        $pull_items_route_url = rest_url('chatprop/v1/items');
        $encoded_pull_items_route_url = urlencode($pull_items_route_url);

        $site_url = get_site_url();
        $encoded_site_url = urlencode($site_url);

        $site_name = get_bloginfo('name');

        $external_url = CHATPROP_WEB_APP_URL
            . '/api/integrations/wordpress/oauth/install?origin=wordpress&state=' . urlencode($chatprop_oauth_state)
            . '&site_name=' . $site_name
            . '&redirect_url=' . $encoded_plugins_url
            . '&auth_callback_url=' . $encoded_auth_callback_url
            . '&site_url=' . $encoded_site_url
            . '&pull_items_route_url=' . $encoded_pull_items_route_url;

        delete_option('chatprop_redirect_after_activation');

        wp_redirect($external_url);
        exit;
    }
}

register_deactivation_hook(__FILE__, 'chatprop_flush_rewrite_rules_on_deactivation');

function chatprop_flush_rewrite_rules_on_deactivation()
{
    delete_option(CHATPROP_OAUTH_STATE);
    delete_option(CHATPROP_OAUTH_TOKEN);
}

// Require all files in the includes directory
function chatprop_require_all_files($dir)
{
    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        require_once $file;
    }

    $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        chatprop_require_all_files($subdir);
    }
}

chatprop_require_all_files(plugin_dir_path(__FILE__) . 'includes');

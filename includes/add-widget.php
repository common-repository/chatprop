<?php

if (!defined('ABSPATH')) {
    exit;
}

function chatprop_add_script_and_styles() {
    $home_url = home_url();

    wp_enqueue_style('chatprop-styles', 'https://app.chatprop.io/chatbot/scripts/chatprop.styles.css', array(), CHATPROP_VERSION);
    wp_enqueue_script('chatprop-script', 'https://app.chatprop.io/chatbot/scripts/chatprop.js', array(), CHATPROP_VERSION, true);
    wp_localize_script('chatprop-script', 'chatpropData', array(
        'channelId' => esc_url($home_url)
    ));
}
add_action('wp_enqueue_scripts', 'chatprop_add_script_and_styles');

function chatprop_add_root_div() {
    ?>
    <!-- Add the root div for the chatbot -->
    <div id="propChatRootDiv"></div>
    <?php
}
add_action('wp_footer', 'chatprop_add_root_div');

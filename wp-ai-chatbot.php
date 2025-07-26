<?php
/*
Plugin Name: WP AI Chatbot
Description: AI-powered chatbot for WordPress using OpenAI and on-site resources.
Version: 0.0.0
Author: CDIE
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/content-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/chat-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/embedding-storage.php';

register_activation_hook(__FILE__, 'wpai_chatbot_activate');
function wpai_chatbot_activate() {
    require_once plugin_dir_path(__FILE__) . 'includes/embedding-storage.php';
    if (function_exists('wpai_create_embeddings_table')) {
        wpai_create_embeddings_table();
    }
}

function wpai_chatbot_shortcode() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/chatbox.php';
    return ob_get_clean();
}
add_shortcode('wp_ai_chatbot', 'wpai_chatbot_shortcode');

function wpai_chatbot_enqueue_assets() {
    wp_enqueue_style('wpai-chatbot-css', plugins_url('assets/chatbot.css', __FILE__));
    wp_enqueue_script('wpai-chatbot-js', plugins_url('assets/chatbot.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('wpai-chatbot-js', 'wpaiChatbotAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wpai_chatbot_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'wpai_chatbot_enqueue_assets');

// AJAX Handlers
add_action('wp_ajax_wpai_chatbot_ask', 'wpai_chatbot_handle_ajax');
add_action('wp_ajax_nopriv_wpai_chatbot_ask', 'wpai_chatbot_handle_ajax'); 
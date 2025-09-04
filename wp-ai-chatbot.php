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

function wpai_chatbot_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'inline',
        'title' => 'AI Assistant',
        'height' => '400px',
        'width' => '100%'
    ), $atts);
    
    ob_start();
    
    //global variables for template
    global $wpai_chatbot_type, $wpai_chatbot_title, $wpai_chatbot_height, $wpai_chatbot_width;
    $wpai_chatbot_type = sanitize_text_field($atts['type']);
    $wpai_chatbot_title = sanitize_text_field($atts['title']);
    $wpai_chatbot_height = sanitize_text_field($atts['height']);
    $wpai_chatbot_width = sanitize_text_field($atts['width']);
    
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

// Auto-indexing hooks
add_action('publish_post', 'wpai_auto_index_post');
add_action('publish_page', 'wpai_auto_index_post');
add_action('wp_insert_post', 'wpai_auto_index_post');

function wpai_auto_index_post($post_id) {
    $auto_index = get_option('wpai_auto_index', 0);
    if (!$auto_index) return;
    
    $post = get_post($post_id);
    if (!$post) return;
    
    $selected_types = get_option('wpai_post_types', array('post'));
    if (!in_array($post->post_type, $selected_types)) return;
    
    if ($post->post_status !== 'publish') return;
    
    wpai_index_post_content($post_id);
} 
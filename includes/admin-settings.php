<?php
// Admin settings tab
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'wpai_admin_menu');
function wpai_admin_menu() {
    add_menu_page(
        'WP AI Chatbot',
        'WP AI Chatbot',
        'manage_options',
        'wpai-chatbot-settings',
        'wpai_admin_settings_page',
        'dashicons-format-chat',
        80
    );
}

// Settings page
function wpai_admin_settings_page() {
    if (!current_user_can('manage_options')) return;
    $message = '';
    //Save settings
    if (isset($_POST['wpai_save_settings'])) {
        check_admin_referer('wpai_save_settings');
        update_option('wpai_openai_api_key', sanitize_text_field($_POST['wpai_openai_api_key']));
        update_option('wpai_post_types', array_map('sanitize_text_field', (array)($_POST['wpai_post_types'] ?? [])));
       
        // extra styling settings
        update_option('wpai_chat_bg_color', sanitize_hex_color($_POST['wpai_chat_bg_color']));
        update_option('wpai_chat_text_color', sanitize_hex_color($_POST['wpai_chat_text_color']));
        update_option('wpai_chat_font_size', sanitize_text_field($_POST['wpai_chat_font_size']));
        update_option('wpai_chat_border_radius', sanitize_text_field($_POST['wpai_chat_border_radius']));
        $message = '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Re-index
    if (isset($_POST['wpai_reindex'])) {
        check_admin_referer('wpai_save_settings');
        $post_types = get_option('wpai_post_types', array('post'));
        $total = 0;
        foreach ($post_types as $pt) {
            $total += wpai_reindex_all($pt);
        }
        $message = '<div class="updated"><p>Re-index complete! ' . intval($total) . ' chunks indexed.</p></div>';
    }
    $api_key = esc_attr(get_option('wpai_openai_api_key', ''));
    $selected_types = get_option('wpai_post_types', array('post'));
    $post_types = get_post_types(array('public' => true), 'objects');

    // Styling settings
    $chat_bg_color = esc_attr(get_option('wpai_chat_bg_color', '#fff'));
    $chat_text_color = esc_attr(get_option('wpai_chat_text_color', '#222'));
    $chat_font_size = esc_attr(get_option('wpai_chat_font_size', '16px'));
    $chat_border_radius = esc_attr(get_option('wpai_chat_border_radius', '16px'));
    echo '<div class="wrap"><h1>WP AI Chatbot Settings</h1>';
    echo $message;
    echo '<form method="post">';

    wp_nonce_field('wpai_save_settings');

    echo '<table class="form-table">';
    echo '<tr><th scope="row">OpenAI API Key</th><td><input type="text" name="wpai_openai_api_key" value="' . $api_key . '" size="50" /></td></tr>';
    echo '<tr><th scope="row">Post Types to Index</th><td>';

    foreach ($post_types as $type) {
        $checked = in_array($type->name, $selected_types) ? 'checked' : '';
        echo '<label><input type="checkbox" name="wpai_post_types[]" value="' . esc_attr($type->name) . '" ' . $checked . '> ' . esc_html($type->labels->singular_name) . '</label><br />';
    }
    
    echo '</td></tr>';
    echo '<tr><th scope="row">Chat Background Color</th><td><input type="color" name="wpai_chat_bg_color" value="' . $chat_bg_color . '" /></td></tr>';
    echo '<tr><th scope="row">Chat Text Color</th><td><input type="color" name="wpai_chat_text_color" value="' . $chat_text_color . '" /></td></tr>';
    echo '<tr><th scope="row">Chat Font Size</th><td><input type="text" name="wpai_chat_font_size" value="' . $chat_font_size . '" placeholder="e.g. 16px" /></td></tr>';
    echo '<tr><th scope="row">Chat Border Radius</th><td><input type="text" name="wpai_chat_border_radius" value="' . $chat_border_radius . '" placeholder="e.g. 16px" /></td></tr>';
    echo '</table>';
    echo '<p><input type="submit" name="wpai_save_settings" class="button-primary" value="Save Settings" /></p>';
    echo '<p><input type="submit" name="wpai_reindex" class="button-secondary" value="Re-index Content" onclick="return confirm(\'Are you sure? This may take a while.\');" /></p>';
    echo '</form></div>';
} 
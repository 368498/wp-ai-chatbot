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
    
    // Save settings
    if (isset($_POST['wpai_save_settings'])) {
        check_admin_referer('wpai_save_settings');
        update_option('wpai_openai_api_key', sanitize_text_field($_POST['wpai_openai_api_key']));
        update_option('wpai_post_types', array_map('sanitize_text_field', (array)($_POST['wpai_post_types'] ?? [])));
        update_option('wpai_chunk_size', intval($_POST['wpai_chunk_size'] ?? 400));
        update_option('wpai_include_excerpts', isset($_POST['wpai_include_excerpts']) ? 1 : 0);
        update_option('wpai_include_meta', isset($_POST['wpai_include_meta']) ? 1 : 0);
        update_option('wpai_auto_index', isset($_POST['wpai_auto_index']) ? 1 : 0);
        update_option('wpai_context_limit', intval($_POST['wpai_context_limit'] ?? 5));
       
        // Styling settings
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

    if (isset($_POST['wpai_clear_embeddings'])) {
        check_admin_referer('wpai_save_settings');
        wpai_clear_all_embeddings();
        $message = '<div class="updated"><p>All embeddings cleared.</p></div>';
    }

    if (isset($_POST['wpai_test_api'])) {
        check_admin_referer('wpai_save_settings');
        $api_key = get_option('wpai_openai_api_key');
        if ($api_key) {
            $test_result = wpai_test_openai_connection($api_key);
            if ($test_result) {
                $message = '<div class="updated"><p>API connection successful!</p></div>';
            } else {
                $message = '<div class="error"><p>API connection failed. Please check your API key.</p></div>';
            }
        } else {
            $message = '<div class="error"><p>Please enter an API key first.</p></div>';
        }
    }
    $api_key = esc_attr(get_option('wpai_openai_api_key', ''));
    $selected_types = get_option('wpai_post_types', array('post'));
    $post_types = get_post_types(array('public' => true), 'objects');
    $chunk_size = intval(get_option('wpai_chunk_size', 400));
    $include_excerpts = get_option('wpai_include_excerpts', 0);
    $include_meta = get_option('wpai_include_meta', 0);
    $auto_index = get_option('wpai_auto_index', 0);
    $context_limit = intval(get_option('wpai_context_limit', 5));

    // Styling settings
    $chat_bg_color = esc_attr(get_option('wpai_chat_bg_color', '#fff'));
    $chat_text_color = esc_attr(get_option('wpai_chat_text_color', '#222'));
    $chat_font_size = esc_attr(get_option('wpai_chat_font_size', '16px'));
    $chat_border_radius = esc_attr(get_option('wpai_chat_border_radius', '16px'));

    $embedding_count = wpai_get_embedding_count();
    
    echo '<div class="wrap">';
    echo '<h1>WP AI Chatbot Settings</h1>';
    echo $message;
    
    echo '<div class="card" style="max-width: 600px; margin-bottom: 20px;">';
    echo '<h2>Content Indexing Status</h2>';
    echo '<p><strong>Total Content Chunks Indexed:</strong> ' . intval($embedding_count) . '</p>';
    echo '<p><strong>Post Types Being Indexed:</strong> ' . implode(', ', $selected_types) . '</p>';
    echo '<p><strong>Chunk Size:</strong> ' . $chunk_size . ' words per chunk</p>';
    echo '<p><strong>Context Limit:</strong> ' . $context_limit . ' chunks per query</p>';
    echo '</div>';

    echo '<form method="post">';
    wp_nonce_field('wpai_save_settings');

    echo '<h2>API Configuration</h2>';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">OpenAI API Key</th><td><input type="password" name="wpai_openai_api_key" value="' . $api_key . '" size="50" placeholder="sk-..." /></td></tr>';
    echo '<tr><th scope="row">Test API Connection</th><td><input type="submit" name="wpai_test_api" class="button-secondary" value="Test Connection" /></td></tr>';
    echo '</table>';

    echo '<h2>Content Indexing</h2>';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Post Types to Index</th><td>';

    foreach ($post_types as $type) {
        $checked = in_array($type->name, $selected_types) ? 'checked' : '';
        echo '<label><input type="checkbox" name="wpai_post_types[]" value="' . esc_attr($type->name) . '" ' . $checked . '> ' . esc_html($type->labels->singular_name) . '</label><br />';
    }
    echo '</td></tr>';
    
    echo '<tr><th scope="row">Chunk Size (words)</th><td><input type="number" name="wpai_chunk_size" value="' . $chunk_size . '" min="100" max="1000" /> <small>Number of words per content chunk</small></td></tr>';
    echo '<tr><th scope="row">Context Limit</th><td><input type="number" name="wpai_context_limit" value="' . $context_limit . '" min="1" max="20" /> <small>Number of chunks to include in context</small></td></tr>';
    echo '<tr><th scope="row">Include Excerpts</th><td><input type="checkbox" name="wpai_include_excerpts" value="1" ' . checked($include_excerpts, 1, false) . ' /> <small>Include post excerpts in indexing</small></td></tr>';
    echo '<tr><th scope="row">Include Meta Data</th><td><input type="checkbox" name="wpai_include_meta" value="1" ' . checked($include_meta, 1, false) . ' /> <small>Include custom fields and meta data</small></td></tr>';
    echo '<tr><th scope="row">Auto-Index New Content</th><td><input type="checkbox" name="wpai_auto_index" value="1" ' . checked($auto_index, 1, false) . ' /> <small>Automatically index new posts when published</small></td></tr>';
    echo '</table>';

    // Chat Styling Section
    echo '<h2>Chat Appearance</h2>';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Background Color</th><td><input type="color" name="wpai_chat_bg_color" value="' . $chat_bg_color . '" /></td></tr>';
    echo '<tr><th scope="row">Text Color</th><td><input type="color" name="wpai_chat_text_color" value="' . $chat_text_color . '" /></td></tr>';
    echo '<tr><th scope="row">Font Size</th><td><input type="text" name="wpai_chat_font_size" value="' . $chat_font_size . '" placeholder="e.g. 16px" /></td></tr>';
    echo '<tr><th scope="row">Border Radius</th><td><input type="text" name="wpai_chat_border_radius" value="' . $chat_border_radius . '" placeholder="e.g. 16px" /></td></tr>';
    echo '</table>';

    echo '<p class="submit">';
    echo '<input type="submit" name="wpai_save_settings" class="button-primary" value="Save Settings" />';
    echo '</p>';
    echo '</form>';


    echo '<h2>Content Management</h2>';
    echo '<form method="post" style="display: inline;">';
    wp_nonce_field('wpai_save_settings');
    echo '<input type="submit" name="wpai_reindex" class="button-secondary" value="Re-index All Content" onclick="return confirm(\'Are you sure? This may take a while.\');" />';
    echo '</form>';
    
    echo ' <form method="post" style="display: inline;">';
    wp_nonce_field('wpai_save_settings');
    echo '<input type="submit" name="wpai_clear_embeddings" class="button-secondary" value="Clear All Embeddings" onclick="return confirm(\'Are you sure? This will delete all indexed content.\');" />';
    echo '</form>';
    
    echo '</div>';
} 
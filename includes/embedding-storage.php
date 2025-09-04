<?php
// storage and DB logic for WP AI Chatbot
if (!defined('ABSPATH')) exit;

function wpai_create_embeddings_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_embeddings';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        chunk LONGTEXT NOT NULL,
        embedding LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        FULLTEXT KEY chunk_ft (chunk)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

function wpai_insert_embedding($post_id, $chunk, $embedding) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_embeddings';
    return $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'chunk' => $chunk,
            'embedding' => maybe_serialize($embedding),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
}


function wpai_get_embeddings_by_post($post_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ai_embeddings';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE post_id = %d",
        $post_id
    ), ARRAY_A);

    foreach ($results as &$row) {
        $row['embedding'] = maybe_unserialize($row['embedding']);
    }
    return $results;
}

function wpai_delete_embeddings_by_post($post_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ai_embeddings';

    return $wpdb->delete($table_name, array('post_id' => $post_id), array('%d'));
}

// total count  embeddings
function wpai_get_embedding_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_embeddings';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

function wpai_clear_all_embeddings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_embeddings';
    return $wpdb->query("TRUNCATE TABLE $table_name");
}

function wpai_test_openai_connection($api_key) {
    $endpoint = 'https://api.openai.com/v1/models';
    $response = wp_remote_get($endpoint, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'timeout' => 10,
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    return $code === 200;
}

<?php
if (!defined('ABSPATH')) exit;

//Helper to Truncate text to a safe token length for OpenAI embeddings pproximate by word count
function wpai_truncate_text($text, $max_tokens = 8191) {
    // Approximate: 1 token ≈ 0.75 words (English)
    $max_words = intval($max_tokens * 0.75);
    $words = preg_split('/\s+/', $text);
    if (count($words) > $max_words) {
        $words = array_slice($words, 0, $max_words);
    }
    return implode(' ', $words);
}

// Retry logic for OpenAI API call
function wpai_openai_api_request($endpoint, $body, $api_key, $max_attempts = 5) {
    $attempt = 0;
    $delay = 1;
    while ($attempt < $max_attempts) {
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 30,
        ));
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                return json_decode(wp_remote_retrieve_body($response), true);
            }
        }

        // Exponential backoff
        sleep($delay);
        $delay = min($delay * 2, 20);
        $attempt++;
    }
    return false;
}


// Fetch and chunk post content
function wpai_get_content_chunks($post_id, $chunk_size = 400) {
    $post = get_post($post_id);
    if (!$post) return array();
    $content = strip_tags($post->post_content);
    $words = preg_split('/\s+/', $content);
    $chunks = array();
    for ($i = 0; $i < count($words); $i += $chunk_size) {
        $chunk = array_slice($words, $i, $chunk_size);
        $chunks[] = implode(' ', $chunk);
    }
    return $chunks;
}

// call OpenAI embedding API for a text chunk
function wpai_generate_embedding($text) {
    $api_key = get_option('wpai_openai_api_key');
    if (!$api_key) return false;
    $endpoint = 'https://api.openai.com/v1/embeddings';
    $safe_text = wpai_truncate_text($text, 8191);
    $body = array(
        'input' => $safe_text,
        'model' => 'text-embedding-3-small'
    );
    $data = wpai_openai_api_request($endpoint, $body, $api_key);
    if ($data && isset($data['data'][0]['embedding'])) {
        return $data['data'][0]['embedding'];
    }
    return false;
}

// Index a single post's content
function wpai_index_post_content($post_id) {
    $chunks = wpai_get_content_chunks($post_id);

    if (empty($chunks)) return 0;

    wpai_delete_embeddings_by_post($post_id);

    $count = 0;
    foreach ($chunks as $chunk) {
        $embedding = wpai_generate_embedding($chunk);
        if ($embedding) {
            wpai_insert_embedding($post_id, $chunk, $embedding);
            $count++;
        }
    }
    return $count;
}

// index all posts of a given type
function wpai_reindex_all($post_type = 'post') {
    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $posts = get_posts($args);
    $total = 0;
    foreach ($posts as $post_id) {
        $total += wpai_index_post_content($post_id);
    }
    return $total;
} 
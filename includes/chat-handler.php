<?php
// OpenAI Chat handler for the WP AI Chatbot
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_wpai_chatbot_ask', 'wpai_chatbot_handle_ajax');
add_action('wp_ajax_nopriv_wpai_chatbot_ask', 'wpai_chatbot_handle_ajax');

function wpai_chatbot_handle_ajax() {
    check_ajax_referer('wpai_chatbot_nonce', 'nonce');
    $question = sanitize_text_field($_POST['question'] ?? '');
    if (empty($question)) {
        wp_send_json_error(array('error' => 'No question provided.'));
    }
 
    $question_embedding = wpai_generate_embedding($question);
    if (!$question_embedding) {
        wp_send_json_error(array('error' => 'Embedding failed.'));
    }

    // Get all embeddings from DB
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_embeddings';

    $rows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    $scored = array();

    foreach ($rows as $row) {
        $embedding = maybe_unserialize($row['embedding']);
        $score = wpai_cosine_similarity($question_embedding, $embedding);
        $scored[] = array('score' => $score, 'chunk' => $row['chunk']);
    }

    // Sort by score descending
    usort($scored, function($a, $b) { return $b['score'] <=> $a['score']; });
    
    //configurable context limit
    $context_limit = intval(get_option('wpai_context_limit', 5));
    $top_scorers = array_slice($scored, 0, $context_limit);
    $context = implode("\n\n", array_column($top_scorers, 'chunk'));

    // Call OpenAI 
    $answer = wpai_generate_gpt_answer($question, $context);
    if (!$answer) {
        wp_send_json_error(array('error' => 'OpenAI completion failed.'));
    }

    wp_send_json_success(array('answer' => $answer));
}

//Call OpenAI GPT-4o with provided context
function wpai_generate_gpt_answer($question, $context) {
    $api_key = get_option('wpai_openai_api_key');
    if (!$api_key) return false;
    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $messages = array(
        array('role' => 'system', 'content' => 'You are a helpful assistant that answers questions using the provided context.'),
        array('role' => 'user', 'content' => "Context:\n" . $context),
        array('role' => 'user', 'content' => "Question: " . $question),
    );
    $body = array(
        'model' => 'gpt-4o',
        'messages' => $messages,
        'max_tokens' => 512,
        'temperature' => 0.2,
    );

    $response = wp_remote_post($endpoint, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body' => json_encode($body),
        'timeout' => 60,
    ));

    if (is_wp_error($response)) return false;

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    return false;
} 


// Helper function for score comparison - cosine similarity between two vectors
function wpai_cosine_similarity($a, $b) {
    
    if (count($a) !== count($b)) return 0;
    $dot = 0; $mag_a = 0; $mag_b = 0;
    for ($i = 0; $i < count($a); $i++) {
        $dot += $a[$i] * $b[$i];
        $mag_a += $a[$i] * $a[$i];
        $mag_b += $b[$i] * $b[$i];
    }
    if ($mag_a == 0 || $mag_b == 0) return 0;
    return $dot / (sqrt($mag_a) * sqrt($mag_b));
}
<?php
global $wpai_chatbot_type, $wpai_chatbot_title, $wpai_chatbot_height, $wpai_chatbot_width;

$chat_type = $wpai_chatbot_type ?? 'floating';
$chat_title = $wpai_chatbot_title ?? 'AI Assistant';
$chat_height = $wpai_chatbot_height ?? '400px';
$chat_width = $wpai_chatbot_width ?? '100%';

$instance_id = uniqid('wpai-chatbot-');
?>

<?php if ($chat_type === 'floating'): ?>
<!-- WordPress-style floating chatbot template -->

<!-- Chat Toggle Button -->
<button id="<?php echo esc_attr($instance_id); ?>-toggle" class="wpai-chatbot-toggle" type="button" aria-label="Open chat">
    💬
</button>

<!-- Chat Panel -->
<div id="<?php echo esc_attr($instance_id); ?>-box" class="wpai-chatbot-box wpai-chatbot-floating">
    <div class="wpai-chatbot-header">
        <h3 class="wpai-chatbot-title"><?php echo esc_html($chat_title); ?></h3>
        <button class="wpai-chatbot-close" type="button" aria-label="Close chat">
            ✕
        </button>
    </div>
    <div class="wpai-chatbot-messages"></div>
    <div class="wpai-chatbot-error"></div>
    <form class="wpai-chatbot-form" autocomplete="off">
        <input class="wpai-chatbot-input" type="text" placeholder="Ask me anything..." autocomplete="off" />
        <button class="wpai-chatbot-send" type="submit">Send</button>
        <span class="wpai-chatbot-loading" style="display:none;">Thinking...</span>
    </form>
</div>

<?php else: ?>

<div id="<?php echo esc_attr($instance_id); ?>-box" class="wpai-chatbot-box wpai-chatbot-inline" style="height: <?php echo esc_attr($chat_height); ?>; width: <?php echo esc_attr($chat_width); ?>;">
    <div class="wpai-chatbot-header">
        <h3 class="wpai-chatbot-title"><?php echo esc_html($chat_title); ?></h3>
    </div>
    <div class="wpai-chatbot-messages"></div>
    <div class="wpai-chatbot-error"></div>
    <form class="wpai-chatbot-form" autocomplete="off">
        <input class="wpai-chatbot-input" type="text" placeholder="Ask me anything..." autocomplete="off" />
        <button class="wpai-chatbot-send" type="submit">Send</button>
        <span class="wpai-chatbot-loading" style="display:none;">Thinking...</span>
    </form>
</div>

<?php endif; ?> 
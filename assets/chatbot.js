jQuery(document).ready(function($) {

    var $form = $('#wpai-chatbot-form');
    var $input = $('#wpai-chatbot-input');
    var $chat = $('#wpai-chatbot-messages');
    var $button = $('#wpai-chatbot-send');
    var $error = $('#wpai-chatbot-error');
    var $loading = $('#wpai-chatbot-loading');

    function appendMessage(role, text) {
        var cls = role === 'user' ? 'wpai-chatbot-user' : 'wpai-chatbot-bot';
        $chat.append('<div class="' + cls + '">' + $('<div>').text(text).html() + '</div>');
        $chat.scrollTop($chat[0].scrollHeight);
    }

    $form.on('submit', function(e) {
        e.preventDefault();

        var question = $input.val().trim();

        if (!question) return;

        $error.hide();

        appendMessage('user', question);
        $input.val('');
        $button.prop('disabled', true);
        $loading.show();
        
        $.ajax({
            url: wpaiChatbotAjax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpai_chatbot_ask',
                nonce: wpaiChatbotAjax.nonce,
                question: question
            },
            success: function(res) {
                $button.prop('disabled', false);
                $loading.hide();
                if (res.success) {
                    appendMessage('bot', res.data.answer);
                } else {
                    $error.text(res.data && res.data.error ? res.data.error : 'Error.').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $loading.hide();
                $error.text('Network error.').show();
            }
        });
    });
}); 
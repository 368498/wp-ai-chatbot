jQuery(document).ready(function($) {

    // Initialize all chatbot instances
    $('.wpai-chatbot-box').each(function() {
        var $chatBox = $(this);
        var $toggle = $chatBox.siblings('.wpai-chatbot-toggle');
        var $form = $chatBox.find('.wpai-chatbot-form');
        var $input = $chatBox.find('.wpai-chatbot-input');
        var $chat = $chatBox.find('.wpai-chatbot-messages');
        var $button = $chatBox.find('.wpai-chatbot-send');
        var $error = $chatBox.find('.wpai-chatbot-error');
        var $loading = $chatBox.find('.wpai-chatbot-loading');
        var $close = $chatBox.find('.wpai-chatbot-close');
        var isOpen = false;
        
        // Initialize this chatbot instance
        initChatbot($chatBox, $toggle, $form, $input, $chat, $button, $error, $loading, $close, isOpen);
    });
    
    function initChatbot($chatBox, $toggle, $form, $input, $chat, $button, $error, $loading, $close, isOpen) {
        
        // Toggle chat panel
        function toggleChat() {
            if (isOpen) {
                closeChat();
            } else {
                openChat();
            }
        }

        function openChat() {
            isOpen = true;
            $chatBox.addClass('wpai-chatbot-open');
            $toggle.addClass('wpai-chatbot-hidden');
            $input.focus();
        }

        function closeChat() {
            isOpen = false;
            $chatBox.removeClass('wpai-chatbot-open');
            $toggle.removeClass('wpai-chatbot-hidden');
        }

        function appendMessage(role, text) {
            var cls = role === 'user' ? 'wpai-chatbot-user' : 'wpai-chatbot-bot';
            $chat.append('<div class="' + cls + '">' + $('<div>').text(text).html() + '</div>');
            $chat.scrollTop($chat[0].scrollHeight);
        }

        // Event handlers
        $toggle.on('click', function(e) {
            e.preventDefault();
            toggleChat();
        });
        
        $close.on('click', function(e) {
            e.preventDefault();
            closeChat();
        });

        // Close chat when clicking outside (only for floating chat)
        if ($chatBox.hasClass('wpai-chatbot-floating')) {
            $(document).on('click', function(e) {
                if (isOpen && !$chatBox.is(e.target) && $chatBox.has(e.target).length === 0) {
                    closeChat();
                }
            });

            // Prevent chat from closing when clicking inside
            $chatBox.on('click', function(e) {
                e.stopPropagation();
            });
        }

    $form.on('submit', function(e) {
        e.preventDefault();

        var question = $input.val().trim();

        if (!question) return;

        // open chat if not already open
        if (!isOpen) {
            openChat();
        }

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
    
    } // End initChatbot function
    
}); // End document ready
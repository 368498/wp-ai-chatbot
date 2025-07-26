# WP AI Chatbot

WIP WordPress plugin to add an AI chatbot to a WP website with knowledge of site content

## Technologies
 The current version of the plugin uses an OpenAI API key to receive AI-generated responses for user queries. The plugin sends user questions, along with relevant site content, to that API and uses the AI’s answer in the chat interface. You must obtain your own OpenAI API key and enter it in the plugin settings.

## File Structure

```
wp-ai-chatbot/
├── wp-ai-chatbot.php          # Main plugin file
├── includes/
│   ├── admin-settings.php     # Admin interface
│   ├── chat-handler.php       # AJAX request handling
│   ├── content-processor.php  # Content processing
│   └── embedding-storage.php  # Database operations
├── templates/
│   └── chatbox.php           # Chat interface template
└── assets/
    ├── chatbot.css           # Styles
    └── chatbot.js            # Frontend JavaScript
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenAI API key
- MySQL database (for embedding storage)

## Support

For support and questions, please check the plugin documentation or contact the development team.

## Version

Current Version: 0.0.0

## License

This plugin is developed by CDIE.

---

**Note**: Make sure to keep your OpenAI API key secure and monitor your API usage to manage costs effectively. 
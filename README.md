# WP AI Chatbot

A WordPress plugin that integrates OpenAI's Assistant API to create an interactive chatbot for your website.

## Features

- Seamless integration with OpenAI Assistant API
- Persistent conversation threads
- Customizable chat interface colors
- Markdown support for formatted responses
- System instructions to control assistant behavior
- Admin test interface to verify assistant functionality
- Easy configuration through WordPress admin
- Shortcode for embedding the chatbot anywhere on your site

## Requirements

- WordPress 6.0 or higher
- PHP 8.1 or higher
- OpenAI API key with access to Assistants API
- OpenAI Assistant ID

## Installation

1. Download the zip with the latest release from Github https://github.com/agradecido/wp-ai-assistant-plugin/releases
2. Upload the plugin files to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings via the 'WP AI Chatbot' menu in your admin dashboard

## Configuration

1. Visit the 'WP AI Chatbot' settings page in your WordPress admin panel
2. Enable the chatbot functionality by checking the activation option
3. Enter your OpenAI API key and Assistant ID
4. Set custom system instructions (optional)
5. Customize the appearance with your preferred colors
6. Save the settings

## Usage

Add the chatbot to any page or post using the shortcode:

```
[wp_ai_chatbot]
```

You can also specify a different assistant ID for specific pages:

```
[wp_ai_chatbot assistant_id="your_assistant_id"]
```

## Development

### Dependencies

- `erusev/parsedown`: For Markdown parsing

### Setup

1. Clone the repository
2. Install PHP dependencies:
   ```
   composer install
   ```
3. Install JavaScript dependencies:
   ```
   npm install
   ```
4. Build assets:
   ```
   npm run build
   ```

### Development Commands

- `composer lint`: Check PHP code style
- `composer lint:fix`: Fix PHP code style issues
- `npm run dev`: Build assets in development mode
- `npm run watch`: Watch for changes and rebuild assets
- `npm run build`: Build assets for production

### Packaging

Run the provided script to create a distribution package:
```
./create-package.sh
```

This will compile assets, install production dependencies, and create a ZIP file ready for distribution.

## Translating the Plugin

This plugin is translation-ready!

To translate the plugin into your language, follow these steps:

1.  **Text Domain:** The text domain for this plugin is `wp-ai-assistant`.
2.  **Template File:** A `.pot` (Portable Object Template) file is available at `languages/wp-ai-assistant.pot`. This file contains all translatable strings from the plugin.
3.  **Create Translation Files:**
    *   Use a program like [Poedit](https://poedit.net/) (available for Windows, macOS, and Linux) or any other `.po` file editor.
    *   Open the `wp-ai-assistant.pot` file in your editor.
    *   Translate the strings into your desired language.
    *   Save your translation file. This will create a `.po` file (e.g., `wp-ai-assistant-es_ES.po` for Spanish from Spain).
    *   The editor should also automatically generate a `.mo` file (e.g., `wp-ai-assistant-es_ES.mo`). This is the compiled translation file that WordPress uses.
4.  **Upload Files:** Place both the `.po` and `.mo` files into the `languages/` directory of the plugin.
5.  **Set WordPress Language:** If your WordPress site's language is set to the language you translated (e.g., Spanish), the plugin should now display in that language.

If you create a translation, please consider sharing it with the plugin author or community!

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Javier Sierra

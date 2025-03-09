<?php
/**
 * Plugin Name: WP AI Chatbot
 * Description: Plugin for chat with an OpenAI Assistant.
 * Version: 1.0
 * Author: Javier Sierra
 *
 * @package WPAIChatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use WPAIChatbot\WPAIChatbotPlugin;

/**
 * Initialize the plugin.
 */
function wp_ai_chatbot_init() {
	$plugin = new WPAIChatbotPlugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'wp_ai_chatbot_init' );

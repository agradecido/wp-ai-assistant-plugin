<?php
/**
 * Plugin Name: Chatbot OpenAI Assistant
 * Description: Plugin for chat with a OpenAI Assistant.
 * Version: 1.0
 * Author: Javier Sierra
 *
 * @package ChatbotGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Dotenv\Dotenv;
use ChatbotGPT\ChatbotGPTPlugin;
use ChatbotGPT\ChatbotGPTSettings;

$dotenv = Dotenv::createImmutable( __DIR__ );
$dotenv->load();

/**
 * Initialize the plugin.
 */
function chatbot_gpt_init() {
	ChatbotGPTSettings::register();
	$plugin = new ChatbotGPTPlugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'chatbot_gpt_init' );

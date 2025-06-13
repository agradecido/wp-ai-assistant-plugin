<?php
/**
 * Plugin Name: WP AI Assistant
 * Description: Plugin for chat with an OpenAI Assistant.
 * Version: 1.0
 * Author: Javier Sierra
 * Text Domain: wp-ai-assistant
 * Domain Path: /languages
 *
 * @package WPAIS
 */

use WPAIS\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Handle plugin activation by checking for the appropriate class
 */
function wp_ai_assistant_activate() {
	if ( class_exists( '\\WPAIS\\Plugin' ) ) {
		call_user_func( array( '\\WPAIS\\Plugin', 'activate' ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// Log error and halt activation.
		Logger::error( __( 'WP AI Assistant Activation Error: No WPAIS\\Plugin classes found', 'wp-ai-assistant' ) );
		wp_die(
			__( 'Plugin could not be activated: Missing required class files. Please contact the plugin developer for support.', 'wp-ai-assistant' )
		);
	} else {
		wp_die(
			__( 'Plugin could not be activated: Missing required class files. Please contact the plugin developer for support.', 'wp-ai-assistant' )
		);
	}
}
register_activation_hook( __FILE__, 'wp_ai_assistant_activate' );

/**
 * Initialize the plugin.
 */
function wp_ai_assistant_init() {
	if ( class_exists( '\\WPAIS\\Plugin' ) ) {
		load_plugin_textdomain( 'wp-ai-assistant', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$plugin = new \WPAIS\Plugin();
		$plugin->init();
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Logger::error( __( 'WP AI Assistant Initialization Error: No WPAIS\\Plugin classes found', 'wp-ai-assistant' ) );
	} else {
		wp_die(
			__( 'Plugin could not be initialized: Missing required class files. Please contact the plugin developer for support.', 'wp-ai-assistant' )
		);
	}
}
add_action( 'plugins_loaded', 'wp_ai_assistant_init' );

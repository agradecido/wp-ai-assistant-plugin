<?php
namespace ChatbotGPT\Admin;

use ChatbotGPT\Api\ChatbotGPTAssistant;

/**
 * Class ChatbotGPTSettings
 *
 * Handles the settings page for configuring the chatbot in the WordPress admin panel.
 */
class ChatbotGPTSettings {
	/**
	 * Registers the settings page in the WordPress admin menu.
	 */
	public static function register() {
		add_action( 'admin_menu', array( self::class, 'add_settings_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue assets for admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'chatbot-gpt_page_chatbot-gpt-test' === $hook ) {
			wp_enqueue_style( 'chatbot-gpt-admin-style', plugin_dir_path( __DIR__ ) . 'assets/css/chatbot.css', array(), '1.0' );
			wp_enqueue_script( 'jquery' );
		}
	}

	/**
	 * Adds the settings page as a top-level menu in WordPress admin.
	 */
	public static function add_settings_page() {
		add_menu_page(
			'Chatbot GPT Settings',
			'Chatbot GPT',
			'manage_options',
			'chatbot-gpt-settings',
			array( self::class, 'render_settings_page' ),
			'dashicons-format-chat',
			25
		);

		add_submenu_page(
			'chatbot-gpt-settings',
			'Probar Asistente',
			'Probar Asistente',
			'manage_options',
			'chatbot-gpt-test',
			array( self::class, 'render_test_page' )
		);
	}

	/**
	 * Registers the settings fields.
	 */
	public static function register_settings() {
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_enable', array( 'default' => 0 ) );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_api_url' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_api_key' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_api_url' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_id' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_waiting_time_in_seconds' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_system_instructions' );
		register_setting(
			'chatbot_gpt_settings_group',
			'chatbot_gpt_main_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#93c462',
			)
		);
		register_setting(
			'chatbot_gpt_settings_group',
			'chatbot_gpt_secondary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#549626',
			)
		);
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_settings_page() {
		include_once plugin_dir_path( __DIR__ ) . 'Admin/templates/settings-page.php';
	}

	/**
	 * Renders the test page for the assistant.
	 */
	public static function render_test_page() {
		$assistant_info = ChatbotGPTAssistant::get_assistant_info();
		include_once plugin_dir_path( __DIR__ ) . 'Admin/templates/test-page.php';
	}
}

ChatbotGPTSettings::register();

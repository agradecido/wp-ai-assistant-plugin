<?php
namespace WPAIS\Admin;

use WPAIS\Api\Assistant;

/**
 * Class Settings
 *
 * Handles the settings page for configuring the chatbot in the WordPress admin panel.
 */
class Settings {

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
		if ( 'wp-ai-assistant_page_wp-ai-assistant-test' === $hook ) {
			$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
			$version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.1';

			wp_enqueue_style( 'wp-ai-assistant-admin-style', $plugin_url . 'assets/dist/css/admin.css', array(), $version );
			wp_enqueue_script( 'wp-ai-assistant-admin-js', $plugin_url . 'assets/dist/js/admin.js', array( 'jquery' ), $version, true );

			wp_localize_script(
				'wp-ai-assistant-admin-js',
				'wpAIAssistant',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_ai_assistant_admin_test_nonce' ),
				)
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WPAIS admin asset path: ' . $plugin_url . 'assets/dist/css/admin.css' );
			}
		}
	}

	/**
	 * Adds the settings page as a top-level menu in WordPress admin.
	 */
	public static function add_settings_page() {
		add_menu_page(
			__( 'WP AI Assistant Settings', 'wp-ai-assistant' ),
			__( 'WP AI Assistant', 'wp-ai-assistant' ),
			'manage_options',
			'wp-ai-assistant-settings',
			array( self::class, 'render_settings_page' ),
			'dashicons-format-chat',
			25
		);

		add_submenu_page(
			'wp-ai-assistant-settings',
			__( 'Test Assistant', 'wp-ai-assistant' ),
			__( 'Test Assistant', 'wp-ai-assistant' ),
			'manage_options',
			'wp-ai-assistant-test',
			array( self::class, 'render_test_page' )
		);
	}

	/**
	 * Registers the settings fields.
	 */
	public static function register_settings() {
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_enable', array( 'default' => 0 ) );
		register_setting(
			'wp_ai_assistant_settings_group',
			'wp_ai_assistant_disabled_message',
			array(
				'default'           => 'Chat desactivado temporalmente, vuelva más tarde o póngase en contacto con nosotros',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_api_url', array( 'default' => 'https://api.openai.com/v1' ) );
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_api_key' );
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_assistant_id' );
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_assistant_waiting_time_in_seconds' );
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_system_instructions' );
		register_setting(
			'wp_ai_assistant_settings_group',
			'wp_ai_assistant_main_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#93c462',
			)
		);
		register_setting(
			'wp_ai_assistant_settings_group',
			'wp_ai_assistant_secondary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#549626',
			)
		);
		register_setting( 'wp_ai_assistant_settings_group', 'wp_ai_assistant_daily_limit', array( 'default' => 20 ) );
		register_setting(
			'wp_ai_assistant_settings_group',
			'wp_ai_assistant_quota_exceeded_message',
			array(
				'default'           => 'Cuota diaria excedida. Vuelve mañana 🤖',
				'sanitize_callback' => 'sanitize_text_field',
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
		$assistant_info = Assistant::get_assistant_info();
		include_once plugin_dir_path( __DIR__ ) . 'Admin/templates/test-page.php';
	}
}

Settings::register();

<?php
namespace WPAIS\Frontend;

use WPAIS\Utils\Logger;

/**
 * Shortcode for the assistant.
 *
 * @package WPAIS
 * @since 1.0
 */
class ChatShortcode {

	/**
	 * Registers the shortcode.
	 */
	public static function register() {
		add_shortcode( 'wp_ai_assistant', array( self::class, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Checks if the chatbot is enabled.
	 *
	 * @return bool
	 */
	private static function is_enabled(): bool {
		return get_option( 'wp_ai_assistant_enable' ) === '1';
	}

	/**
	 * Retrieves an option with a default value.
	 *
	 * @param string $option_name Option key.
	 * @param mixed  $default_value Default value if the option is not set.
	 * @return mixed
	 */
	private static function get_option_with_default( string $option_name, $default_value ) {
		$value = get_option( $option_name, $default_value );
		return ! empty( $value ) ? $value : $default_value;
	}

	/**
	 * Gets the assistant ID from the shortcode or settings.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string|null
	 */
	private static function get_assistant_id( array $atts ): ?string {
		$assistant_id = $atts['assistant_id'] ?? '';
		return ! empty( $assistant_id ) ? $assistant_id : self::get_option_with_default( 'wp_ai_assistant_assistant_id', '' );
	}

	/**
	 * Enqueues styles and scripts for the chatbot.
	 */
	public static function enqueue_assets() {
		$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
		$version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.1';

		// Enqueue main chatbot styles and scripts.
		wp_enqueue_style( 'wp-ai-assistant-style', $plugin_url . 'assets/dist/css/chatbot.css', array(), $version );
		wp_enqueue_script( 'wp-ai-assistant-script', $plugin_url . 'assets/dist/js/chatbot.js', array( 'jquery' ), $version, true );

		// Also enqueue history-related assets if they exist.
		if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'assets/dist/js/history.js' ) ) {
			wp_enqueue_script( 'wp-ai-assistant-history-script', $plugin_url . 'assets/dist/js/history.js', array( 'jquery', 'wp-ai-assistant-script' ), $version, true );
		}

		// If history CSS exists, enqueue it too.
		if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'assets/dist/css/history.css' ) ) {
			wp_enqueue_style( 'wp-ai-assistant-history-style', $plugin_url . 'assets/dist/css/history.css', array( 'wp-ai-assistant-style' ), $version );
		}

		// Pass data to scripts.
		wp_localize_script(
			'wp-ai-assistant-script',
			'wpAIAssistant',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wp_ai_assistant_nonce' ),
			)
		);

		wp_add_inline_style( 'wp-ai-assistant-style', self::get_styles() );
	}

	/**
	 * Generates dynamic styles for the chatbot.
	 *
	 * @return string
	 */
	private static function get_styles(): string {
		$main_color      = self::get_option_with_default( 'wp_ai_assistant_main_color', '#93c462' );
		$secondary_color = self::get_option_with_default( 'wp_ai_assistant_secondary_color', '#549626' );

		return "
			#chatbot-container {
				--accent-color: {$main_color};
				--button-color: {$secondary_color};
			}";
	}

	/**
	 * Loads the chatbot HTML template.
	 *
	 * @param string $nonce Security nonce.
	 * @param bool   $is_enabled Whether the chatbot is enabled.
	 * @param string $disabled_message Message to show when chatbot is disabled.
	 * @return string
	 */
	private static function get_html(): string {
		ob_start();

		$template_path = dirname( dirname( __DIR__ ) ) . '/src/Frontend/templates/chatbot-template.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$is_enabled = self::is_enabled();

		$assistant_id = self::get_assistant_id( $atts );

		if ( empty( $assistant_id ) && $is_enabled ) {
			Logger::log( 'Error: No assistant ID' );
			return '<p>Error: No assistant ID</p>';
		}

		$nonce            = wp_create_nonce( 'wp_ai_assistant_nonce' );
		$disabled_message = self::get_option_with_default(
			'wp_ai_assistant_disabled_message',
			'Chat desactivado temporalmente, vuelva más tarde o póngase en contacto con nosotros'
		);

		return self::get_html( $nonce, $is_enabled, $disabled_message );
	}
}

<?php
namespace WPAIChatbot\Frontend;

use WPAIChatbot\Utils\WPAIChatbotLogger;

/**
 * Shortcode for the chatbot.
 *
 * @package WPAIChatbot
 * @since 1.0
 */
class WPAIChatbotShortcode {

	/**
	 * Registers the shortcode.
	 */
	public static function register() {
		add_shortcode( 'wp_ai_chatbot', array( self::class, 'render' ) );
	}

	/**
	 * Checks if the chatbot is enabled.
	 *
	 * @return bool
	 */
	private static function is_enabled(): bool {
		return get_option( 'wp_ai_chatbot_enable' ) === '1';
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
		return ! empty( $assistant_id ) ? $assistant_id : self::get_option_with_default( 'wp_ai_chatbot_assistant_id', '' );
	}

	/**
	 * Enqueues styles and scripts for the chatbot.
	 */
	private static function enqueue_assets() {
		$plugin_url = plugin_dir_url( __DIR__ );
		$version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.1';

		wp_enqueue_style( 'wp-ai-chatbot-style', $plugin_url . 'assets/dist/css/chatbot.css', array(), $version );
		wp_enqueue_script( 'wp-ai-chatbot-script', $plugin_url . 'assets/dist/js/chatbot.js', array( 'jquery' ), $version, true );

		wp_localize_script(
			'wp-ai-chatbot-script',
			'wpAIChatbot',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wp_ai_chatbot_nonce' ),
			)
		);

		wp_add_inline_style( 'wp-ai-chatbot-style', self::get_styles() );
	}

	/**
	 * Generates dynamic styles for the chatbot.
	 *
	 * @return string
	 */
	private static function get_styles(): string {
		$main_color      = self::get_option_with_default( 'wp_ai_chatbot_main_color', '#93c462' );
		$secondary_color = self::get_option_with_default( 'wp_ai_chatbot_secondary_color', '#549626' );

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
	 * @return string
	 */
	private static function get_html( string $nonce ): string {
		ob_start();

		$template_path = plugin_dir_path( __DIR__ ) . 'src/Frontend/templates/chatbot-template.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			return '<p>Error: Chatbot template not found.</p>';
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
		if ( ! self::is_enabled() ) {
			return '';
		}

		$assistant_id = self::get_assistant_id( $atts );

		if ( empty( $assistant_id ) ) {
			WPAIChatbotLogger::log( 'Error: No assistant ID' );
			return '<p>Error: No assistant ID</p>';
		}

		self::enqueue_assets();
		$nonce = wp_create_nonce( 'wp_ai_chatbot_nonce' );

		return self::get_html( $nonce );
	}
}

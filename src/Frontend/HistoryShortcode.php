<?php

namespace WPAIS\Frontend;

use WPAIS\Infrastructure\Persistence\WPThreadRepository;
use WPAIS\Utils\Session;
use Parsedown;
use WPAIS\Utils\Logger;

/**
 * Class HistoryShortcode
 *
 * Handles registration and rendering of the chatbot history shortcode.
 *
 * @package WPAIS\Frontend
 */
class HistoryShortcode {


	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register() {
		add_action(
			'init',
			function () {
				Logger::log( 'Registering WP AI Assistant history shortcode' );
				add_shortcode( 'wp_ai_assistant_history', array( self::class, 'render' ) );
			}
		);
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_history_assets' ) );
	}

	/**
	 * Enqueue necessary assets for the history display.
	 *
	 * @return void
	 */
	public static function enqueue_history_assets() {
		if ( is_admin() ) {
				return;
		}

			$post = get_post();
		if ( ! $post ) {
				return;
		}

			$post_content = $post->post_content;
		if ( ! has_shortcode( $post_content, 'wp_ai_assistant_history' ) ) {
				return;
		}

			$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
			$version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0';

			wp_enqueue_style( 'wp-ai-assistant-history-style', $plugin_url . 'assets/dist/css/history.css', array(), $version );
			wp_enqueue_script( 'wp-ai-assistant-history-js', $plugin_url . 'assets/dist/js/history.js', array( 'jquery' ), $version, true );

			wp_localize_script(
				'wp-ai-assistant-history-js',
				'wpAIAssistantHistory',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_ai_assistant_history_nonce' ),
					'i18n'    => array(
						'viewFullConversation'            => __( 'View full conversation', 'wp-ai-assistant' ),
						'hideConversation'                => __( 'Hide conversation', 'wp-ai-assistant' ),
						'continueConversationMessage'     => __( '<p>Continuing previous conversation... How can I help you further?</p>', 'wp-ai-assistant' ),
						'continueConversationPlaceholder' => __( 'Continue conversation...', 'wp-ai-assistant' ),
						'chatbotNotAvailableAlert'        => __( 'The chatbot is not available on this page. Please go to a page with the chatbot to continue the conversation.', 'wp-ai-assistant' ),
						'sessionStorageNotAvailable'      => __( 'Session storage not available', 'wp-ai-assistant' ),
					),
				)
			);
	}

	/**
	 * Get the repository for thread operations.
	 *
	 * @return WPThreadRepository
	 */
	private static function get_repository() {
		static $repository = null;
		if ( null === $repository ) {
			$repository = new WPThreadRepository();
		}
		return $repository;
	}

	/**
	 * Render the history shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'title'    => __( 'Conversation History', 'wp-ai-assistant' ),
				'redirect' => '', // URL to redirect to when continuing a chat.
			),
			$atts,
			'wp_ai_assistant_history'
		);

		// Get the current user or session.
		$user_id    = get_current_user_id();
		$session_id = Session::get_session_id();

		// Get threads from repository.
		$repository = self::get_repository();
		$threads    = $repository->getThreadsByUserOrSession(
			$user_id ? (string) $user_id : null,
			$session_id,
			(int) $atts['limit']
		);

		ob_start();

		// Set up Parsedown for Markdown rendering.
		$parsedown = new Parsedown();

		// Extract variables for template.
		$title    = $atts['title'];
		$redirect = $atts['redirect'];

		$template_path = dirname( dirname( __DIR__ ) ) . '/src/Frontend/templates/history-template.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}
}

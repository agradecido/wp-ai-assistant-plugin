<?php
namespace WPAIChatbot;

use WPAIChatbot\Admin\WPAIChatbotSettings;
use WPAIChatbot\Api\WPAIChatbotAssistant;
use WPAIChatbot\Frontend\WPAIChatbotShortcode;

/**
 * Class WPAIChatbotPlugin
 *
 * Initializes the plugin and registers the necessary components.
 *
 * @package WPAIChatbot
 * @since 1.0
 */
class WPAIChatbotPlugin {
	/**
	 * Initialize the plugin.
	 */
	public function init() {
		WPAIChatbotSettings::register();
		WPAIChatbotShortcode::register();

		add_action( 'wp_ajax_wp_ai_chatbot_request', array( $this, 'handle_chatbot_request' ) );
		add_action( 'wp_ajax_nopriv_wp_ai_chatbot_request', array( $this, 'handle_chatbot_request' ) );

		add_action( 'wp_ajax_wp_ai_chatbot_admin_test', array( $this, 'handle_admin_test_request' ) );
	}

	/**
	 * Handle the chatbot request and forward it to WPAIChatbotAssistant.
	 */
	public function handle_chatbot_request() {
		$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_ai_chatbot_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed' ), 403 );
			wp_die();
		}

		$query     = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

		$response = WPAIChatbotAssistant::query_assistant( $query, $thread_id );

		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Handle admin test requests and forward to WPAIChatbotAssistant.
	 */
	public function handle_admin_test_request() {
		$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_ai_chatbot_admin_test_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Verificación de seguridad fallida' ), 403 );
			wp_die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permisos insuficientes' ), 403 );
			wp_die();
		}

		$query     = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => 'La consulta está vacía' ), 400 );
			wp_die();
		}

		$response = WPAIChatbotAssistant::query_assistant( $query, $thread_id );

		if ( isset( $response['error'] ) ) {
			wp_send_json_error( array( 'message' => $response['error'] ), 500 );
		} else {
			wp_send_json_success( $response );
		}

		wp_die();
	}
}

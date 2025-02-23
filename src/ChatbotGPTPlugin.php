<?php
namespace ChatbotGPT;

/**
 * Class ChatbotGPTPlugin
 *
 * Initializes the plugin and registers the necessary components.
 *
 * @package ChatbotGPT
 * @since 1.0
 */
class ChatbotGPTPlugin {
	/**
	 * Initialize the plugin.
	 */
	public function init() {
		ChatbotGPTShortcode::register();

		add_action( 'wp_ajax_chatbot_gpt_request', array( $this, 'handle_chatbot_request' ) );
		add_action( 'wp_ajax_nopriv_chatbot_gpt_request', array( $this, 'handle_chatbot_request' ) );
	}

	/**
	 * Handle the chatbot request and forward it to ChatbotGPTAssistant.
	 */
	public function handle_chatbot_request() {
		$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'chatbot_gpt_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed' ), 403 );
			wp_die();
		}

		$query     = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

		$response = ChatbotGPTAssistant::query_assistant( $query, $thread_id );

		wp_send_json( $response );
		wp_die();
	}
}

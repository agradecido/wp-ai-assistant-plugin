<?php
// phpcs:ignoreFile
namespace WPAIS;

use WPAIS\Admin\Settings;
use WPAIS\Admin\ConversationMetaBox;
use WPAIS\Admin\SummaryMetaBox;
use WPAIS\Api\Assistant;
use WPAIS\Frontend\ChatShortcode;
use WPAIS\Frontend\HistoryShortcode;
use WPAIS\Infrastructure\Migration\CreateQuotaTable;
use WPAIS\Utils\Session;
use WPAIS\Infrastructure\Persistence\WPDBQuotaRepository;
use WPAIS\Infrastructure\Persistence\WPThreadRepository;
use WPAIS\Domain\Quota\QuotaManager;
use WPAIS\Utils\Logger;
use WPAIS\Domain\Thread\ChatThreadPostType;

/**
 * Class Plugin
 *
 * Initializes the plugin and registers the necessary components.
 *
 * @package WPAIS
 * @since 1.0
 */
class Plugin {

	private QuotaManager $quotaManager;

	/**
	 * Default daily limit queries per user.
	 */
	private const DEFAULT_DAILY_LIMIT = 3;
	
	/**
	 * Default multiplier for registered users.
	 * This is used to increase the daily limit for registered users.
	 */
	private const DEFAULT_RESGISTERED_MULTIPLIER = 5;

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		Logger::log( 'Initializing WP AI Assistant plugin' );
		
		Settings::register();
		ChatShortcode::register();
		HistoryShortcode::register();

		// Instance the repository and inject the manager.
		global $wpdb;
		$repo               = new WPDBQuotaRepository( $wpdb );
		// If logged in, increase the daily limit.
		if ( is_user_logged_in() ) {
			$dailyLimit = (int) get_option( 'wp_ai_assistant_daily_limit', self::DEFAULT_DAILY_LIMIT ) * self::DEFAULT_RESGISTERED_MULTIPLIER;
		} else {
			// If not logged in, use the default daily limit.
			$dailyLimit = (int) get_option( 'wp_ai_assistant_daily_limit', self::DEFAULT_DAILY_LIMIT );
		}

		$this->quotaManager = new QuotaManager( $repo, $dailyLimit );

		add_action( 'init', array( ChatThreadPostType::class, 'register' ) );
                // Register conversation meta box.
                ConversationMetaBox::register();
                SummaryMetaBox::register();

		// Initialize thread repository and connect with Assistant.
		$thread_repository = new WPThreadRepository();
		Assistant::set_thread_repository( $thread_repository );

		// Hooks AJAX.
		add_action( 'wp_ajax_wp_ai_assistant_request', array( $this, 'handle_chatbot_request' ) );
		add_action( 'wp_ajax_nopriv_wp_ai_assistant_request', array( $this, 'handle_chatbot_request' ) );
		add_action( 'wp_ajax_wp_ai_assistant_admin_test', array( $this, 'handle_admin_test_request' ) );
		add_action( 'wp_ajax_wp_ai_assistant_generate_summary', array( $this, 'handle_generate_summary_request' ) );
	}

	/**
	 * Register the plugin activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		CreateQuotaTable::up();
	}

	/**
	 * Handle the chatbot request and forward it to Assistant.
	 */
	public function handle_chatbot_request() {
		$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_ai_assistant_nonce' ) ) {
			Logger::error( 'Nonce verification failed' );
			wp_send_json_error( array( 'message' => 'Nonce verification failed' ), 403 );
			wp_die();
		}

		// Check if the chatbot is enabled.
		if ( get_option( 'wp_ai_assistant_enable' ) !== '1' ) {
			$disabled_message = get_option(
				'wp_ai_assistant_disabled_message',
				'Chat desactivado temporalmente, vuelva más tarde o póngase en contacto con nosotros'
			);

			wp_send_json(
				array(
					'success' => true,
					'message' => $disabled_message,
				)
			);
			wp_die();
		}

		// Check the quota.
		$sid = Session::get_session_id();
		try {
			$this->quotaManager->checkAndIncrement( $sid );
		} catch ( \RuntimeException $e ) {
			Logger::error( 'Quota exceeded: ' . $e->getMessage() );
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => 'quota_exceeded',
				),
				429
			);
			wp_die();
		}

		$query     = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
		$thread_id = sanitize_text_field( wp_unslash( $_POST['thread_id'] ?? '' ) );

		Logger::log( 'Query: ' . $query );

		try {
			$response = Assistant::query_assistant( $query, $thread_id );

			// Check if response is array and has content.
			if ( is_array( $response ) ) {
				Logger::log( 'Response received: ' . ( $response['error'] ? 'Error: ' . $response['message'] : 'Success' ) );
			} else {
				Logger::log( 'Response: ' . wp_remote_retrieve_body( $response ) );
			}
			wp_send_json( $response );
		} catch ( \Exception $e ) {
			Logger::error( 'Exception in Assistant: ' . $e->getMessage() );
			wp_send_json_error(
				array(
					'error'   => true,
					'message' => 'An error occurred while processing your request. Please try again.',
				),
				500
			);
		}
		wp_die();
	}

	/**
	 * Handle admin test requests and forward to Assistant.
	 */
    public function handle_admin_test_request() {
		$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_ai_assistant_admin_test_nonce' ) ) {
			wp_send_json_error(
				array( 'message' => 'Verificación de seguridad fallida' ),
				403
			);
			wp_die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => 'Permisos insuficientes' ),
				403
			);
			wp_die();
		}

		$query     = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

		if ( empty( $query ) ) {
			wp_send_json_error(
				array( 'message' => 'La consulta está vacía' ),
				400
			);
			wp_die();
		}

		try {
			$response = Assistant::query_assistant( $query, $thread_id );

			if ( isset( $response['error'] ) && $response['error'] ) {
				Logger::error( 'Error in admin test: ' . ( isset( $response['message'] ) ? $response['message'] : 'Unknown error' ) );
				wp_send_json_error(
					array( 'message' => isset( $response['message'] ) ? $response['message'] : 'Unknown error' ),
					500
				);
			} else {
				Logger::log( 'Admin test successful' );
				// Make the response compatible with existing code.
				if ( isset( $response['message'] ) && ! isset( $response['text'] ) ) {
					$response['text'] = $response['message'];
				}
				wp_send_json_success( $response );
			}
		} catch ( \Exception $e ) {
			Logger::error( 'Exception in admin test: ' . $e->getMessage() );
			wp_send_json_error(
				array( 'message' => 'Error processing request: ' . $e->getMessage() ),
				500
			);
		}

		wp_die();
    }

	/**
	 * AJAX handler to manually generate a summary for a chat thread.
	 */
	public function handle_generate_summary_request() {
		$nonce   = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_ai_assistant_generate_summary_nonce' ) ) {
				wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
				wp_die();
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
				wp_die();
		}

		$messages = get_post_meta( $post_id, 'messages', true );
		if ( empty( $messages ) || ! is_array( $messages ) ) {
				wp_send_json_error( array( 'message' => 'No messages found' ), 400 );
				wp_die();
		}

		$summary = \WPAIS\Api\Summarizer::generate_summary( $messages );

		if ( empty( $summary ) ) {
				wp_send_json_error( array( 'message' => 'Could not generate summary' ), 500 );
				wp_die();
		}

		wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => sanitize_text_field( $summary ) ) );

		wp_send_json_success( array( 'summary' => $summary ) );
		wp_die();
	}

}

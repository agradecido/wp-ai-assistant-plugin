<?php
namespace WPAIS\Api;

use Parsedown;
use WPAIS\Utils\Logger;
use WPAIS\Utils\Session;

/**
 * Class ChatCompletions
 *
 * Handles communication with OpenAI Chat Completions API with support for
 * Vector Stores (file_search) and web search tools.
 *
 * @package WPAIS\Api
 * @since 1.2.0
 */
class ChatCompletions {
	/**
	 * OpenAI API key.
	 *
	 * @var string
	 */
	private static $api_key;

	/**
	 * OpenAI API URL.
	 *
	 * @var string
	 */
	private static $api_url;

	/**
	 * Chat model to use.
	 *
	 * @var string
	 */
	private static $model;

	/**
	 * System instructions for the chat.
	 *
	 * @var string
	 */
	private static $system_instructions;

	/**
	 * Max messages to keep in history.
	 *
	 * @var int
	 */
	private const MAX_HISTORY_MESSAGES = 20;

	/**
	 * Initialize the Chat Completions settings.
	 */
	public static function init() {
		self::$api_key             = get_option( 'wp_ai_assistant_api_key' );
		self::$api_url             = get_option( 'wp_ai_assistant_api_url' ) ?? 'https://api.openai.com/v1';
		self::$model               = get_option( 'wp_ai_assistant_chat_model' ) ?? 'gpt-4o';
		self::$system_instructions = get_option( 'wp_ai_assistant_system_instructions' ) ?? '';

		Logger::log( 'Chat Completions initialized with model: ' . self::$model );
	}

	/**
	 * Query the Chat Completions API.
	 *
	 * @param string      $query User's query text.
	 * @param string      $session_id Session ID for conversation history.
	 * @param string|null $vector_store_id Optional Vector Store ID for file search.
	 * @param bool        $enable_web_search Whether to enable web search.
	 * @return array Response from the API or error.
	 */
	public static function query( string $query, string $session_id, ?string $vector_store_id = null, bool $enable_web_search = false ): array {
		self::init();

		Logger::log( 'Chat Completions query: ' . $query );
		Logger::log( 'Session ID: ' . $session_id );
		Logger::log( 'Vector Store ID: ' . ( $vector_store_id ?? 'none' ) );
		Logger::log( 'Web Search: ' . ( $enable_web_search ? 'enabled' : 'disabled' ) );

		if ( empty( self::$api_key ) ) {
			return array(
				'error'   => true,
				'message' => 'API key is missing.',
			);
		}

		if ( empty( $query ) ) {
			return array(
				'error'   => true,
				'message' => 'No query provided.',
			);
		}

		try {
			// Get conversation history from session.
			$history = self::getConversationHistory( $session_id );

			// Build messages array.
			$messages = self::buildMessages( $history, $query );

			// Build tools array if needed.
			$tools = self::buildTools( $vector_store_id, $enable_web_search );

			// Prepare request body.
			$body = array(
				'model'    => self::$model,
				'messages' => $messages,
			);

			// Add tools if any.
			if ( ! empty( $tools ) ) {
				$body['tools'] = $tools;
			}

			Logger::log( 'Request body: ' . wp_json_encode( $body ) );

			// Make API request.
			$response = wp_remote_post(
				self::$api_url . '/chat/completions',
				array(
					'headers'  => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . self::$api_key,
					),
					'body'     => wp_json_encode( $body ),
					'timeout'  => 90, // Longer timeout for tool usage.
					'blocking' => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				Logger::error( 'API error: ' . $response->get_error_message() );
				return array(
					'error'   => true,
					'message' => 'API error: ' . $response->get_error_message(),
				);
			}

			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
			$status_code   = wp_remote_retrieve_response_code( $response );

			Logger::log( 'API status code: ' . $status_code );
			Logger::log( 'API response: ' . wp_json_encode( $response_body ) );

			if ( 200 !== $status_code ) {
				$error_message = isset( $response_body['error']['message'] )
					? $response_body['error']['message']
					: 'Unknown API error';

				Logger::error( 'API returned error: ' . $error_message );
				return array(
					'error'   => true,
					'message' => 'API error: ' . $error_message,
				);
			}

			if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
				Logger::error( 'Invalid API response format' );
				return array(
					'error'   => true,
					'message' => 'Invalid API response format',
				);
			}

			$assistant_message = $response_body['choices'][0]['message']['content'];

			// Save conversation history.
			$history[] = array(
				'role'    => 'user',
				'content' => $query,
			);
			$history[] = array(
				'role'    => 'assistant',
				'content' => $assistant_message,
			);

			self::saveConversationHistory( $session_id, $history );

			// Format response with markdown.
			$parsedown = new Parsedown();

			return array(
				'error'     => false,
				'message'   => $parsedown->text( $assistant_message ),
				'raw'       => $response_body,
				'thread_id' => $session_id, // For compatibility with frontend.
			);

		} catch ( \Exception $e ) {
			Logger::error( 'Exception in Chat Completions: ' . $e->getMessage() );
			return array(
				'error'   => true,
				'message' => 'Error processing request: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Build messages array for API request.
	 *
	 * @param array  $history Conversation history.
	 * @param string $query Current user query.
	 * @return array Messages array.
	 */
	private static function buildMessages( array $history, string $query ): array {
		$messages = array();

		// Add system message if configured.
		if ( ! empty( self::$system_instructions ) ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => self::$system_instructions,
			);
		}

		// Add conversation history.
		foreach ( $history as $message ) {
			$messages[] = $message;
		}

		// Add current query.
		$messages[] = array(
			'role'    => 'user',
			'content' => $query,
		);

		return $messages;
	}

	/**
	 * Build tools array for API request.
	 *
	 * @param string|null $vector_store_id Vector Store ID.
	 * @param bool        $enable_web_search Enable web search.
	 * @return array Tools array.
	 */
	private static function buildTools( ?string $vector_store_id, bool $enable_web_search ): array {
		$tools = array();

		// Add file_search tool if vector store is configured.
		if ( ! empty( $vector_store_id ) ) {
			$tools[] = array(
				'type'        => 'file_search',
				'file_search' => array(
					'vector_store_ids' => array( $vector_store_id ),
				),
			);
			Logger::log( 'Added file_search tool with vector store: ' . $vector_store_id );
		}

		// Add web_search tool if enabled.
		if ( $enable_web_search ) {
			$tools[] = array(
				'type' => 'web_search',
			);
			Logger::log( 'Added web_search tool' );
		}

		return $tools;
	}

	/**
	 * Get conversation history from WordPress session/transient.
	 *
	 * @param string $session_id Session ID.
	 * @return array Conversation history.
	 */
	private static function getConversationHistory( string $session_id ): array {
		$history = get_transient( 'wp_ai_chat_history_' . $session_id );

		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Limit history size to prevent excessive token usage.
		if ( count( $history ) > self::MAX_HISTORY_MESSAGES ) {
			$history = array_slice( $history, -self::MAX_HISTORY_MESSAGES );
		}

		Logger::log( 'Retrieved conversation history: ' . count( $history ) . ' messages' );

		return $history;
	}

	/**
	 * Save conversation history to WordPress transient.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $messages Messages array.
	 */
	private static function saveConversationHistory( string $session_id, array $messages ): void {
		// Limit history size.
		if ( count( $messages ) > self::MAX_HISTORY_MESSAGES ) {
			$messages = array_slice( $messages, -self::MAX_HISTORY_MESSAGES );
		}

		// Save for 24 hours.
		set_transient( 'wp_ai_chat_history_' . $session_id, $messages, DAY_IN_SECONDS );

		Logger::log( 'Saved conversation history: ' . count( $messages ) . ' messages' );
	}
}

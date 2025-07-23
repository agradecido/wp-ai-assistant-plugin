<?php
namespace WPAIS\Api;

use Error;
use Parsedown;
use WPAIS\Utils\Logger;
use WPAIS\Domain\Thread\ThreadRepository;
use WPAIS\Utils\Session;

/**
 * Class Assistant
 *
 * Handles communication with OpenAI Assistant API.
 */
class Assistant {
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
	 * OpenAI Assistant ID.
	 *
	 * @var string
	 */
	private static $assistant_id;
	/**
	 * Sleep time
	 *
	 * @var int
	 */
	private static $sleep_time;
	/**
	 * System instructions for the assistant.
	 *
	 * @var string
	 */
	private static $system_instructions;

	/**
	 * Thread repository instance.
	 *
	 * @var ThreadRepository|null
	 */
	private static $thread_repository = null;

	/**
	 * Initialize the Assistant settings.
	 */
	public static function init() {
				self::$api_key      = get_option( 'wp_ai_assistant_api_key' );
				self::$api_url      = get_option( 'wp_ai_assistant_api_url' ) ?? 'https://api.openai.com/v1';
				self::$assistant_id = get_option( 'wp_ai_assistant_assistant_id' ) ?? '';

				// Default to 5 seconds if the option is not set or empty.
				$sleep_time_option = get_option( 'wp_ai_assistant_assistant_waiting_time_in_seconds', 5 );
				self::$sleep_time  = intval( $sleep_time_option );

				self::$system_instructions = get_option( 'wp_ai_assistant_system_instructions' ) ?? '';
				Logger::log( 'Assistant initialized with Assistant ID: ' . self::$assistant_id );
	}

	/**
	 * Set the thread repository.
	 *
	 * @param ThreadRepository $repository The repository instance.
	 */
	public static function set_thread_repository( ThreadRepository $repository ) {
		self::$thread_repository = $repository;
	}

	/**
	 * Get assistant information including the model being used.
	 *
	 * @return array Information about the assistant or error.
	 */
	public static function get_assistant_info(): array {
		self::init();

		if ( empty( self::$api_key ) || empty( self::$assistant_id ) ) {
			Logger::error( 'Error: API key or Assistant ID is missing.' );
			return array(
				'error'   => true,
				'message' => 'Error: API key or Assistant ID is missing.',
			);
		}

		$response = wp_remote_get(
			self::$api_url . '/assistants/' . self::$assistant_id,
			array(
				'headers'  => array(
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout'  => 30, // Increase timeout to 30 seconds.
				'blocking' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error'   => true,
				'message' => 'Error getting assistant info: ' . $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['id'] ) ) {
			return array(
				'error'   => true,
				'message' => 'Error getting assistant info: Invalid response',
			);
		}

		return array(
			'error'       => false,
			'id'          => $body['id'] ?? '',
			'name'        => $body['name'] ?? '',
			'model'       => $body['model'] ?? '',
			'description' => $body['description'] ?? '',
			'created_at'  => isset( $body['created_at'] ) ? gmdate( 'Y-m-d H:i:s', $body['created_at'] ) : '',
		);
	}

	/**
	 * Query the AI assistant with a user message.
	 *
	 * @param string $query The user's query text.
	 * @param string $thread_id Optional thread ID for continuing conversations.
	 * @return array Response from the assistant or error.
	 */
	public static function query_assistant( string $query, string $thread_id = '' ): array {
		self::init();

		Logger::log( 'Querying assistant with query: ' . $query );

		if ( empty( self::$api_key ) || empty( self::$assistant_id ) ) {
			return array(
				'error'   => true,
				'message' => 'API key or Assistant ID is missing.',
			);
		}

		if ( empty( $query ) ) {
			return array(
				'error'   => true,
				'message' => 'No query provided.',
			);
		}

		try {
			$session_id = Session::get_session_id();
			$user_id    = get_current_user_id();

			// Create a new thread if no thread_id is provided.
			if ( empty( $thread_id ) ) {
				$thread = self::create_thread();
				if ( isset( $thread['error'] ) && $thread['error'] ) {
					Logger::error( 'Error creating thread: ' . $thread['message'] );
					return $thread;
				}
				$thread_id = $thread['thread_id'];

				// Save new thread in WordPress
				if ( self::$thread_repository ) {
					$title = substr( $query, 0, 50 ) . ( strlen( $query ) > 50 ? '...' : '' );
					self::$thread_repository->saveThread( $thread_id, $session_id, $user_id ? (string) $user_id : null, $title );
					Logger::log( 'Thread saved to database with ID: ' . $thread_id );
				}
			}

			// Add the user message to the thread.
			$message_result = self::add_message_to_thread( $thread_id, $query );
			if ( isset( $message_result['error'] ) && $message_result['error'] ) {
				Logger::error( 'Error adding message: ' . $message_result['message'] );
				return $message_result;
			}
			Logger::log( 'Message added to thread' . ( isset( $message_result['message'] ) ? ': ' . $message_result['message'] : '' ) );

			// Save user message to WordPress
			if ( self::$thread_repository ) {
				self::$thread_repository->addMessage( $thread_id, $query, 'user' );
				Logger::log( 'User message added to thread: ' . $thread_id );
			}

			// Run the assistant on the thread.
			Logger::log( 'Running assistant on thread: ' . $thread_id );
			$run_result = self::run_assistant( $thread_id );
			if ( isset( $run_result['error'] ) && $run_result['error'] ) {
				return $run_result;
			}

			Logger::log( 'Assistant run completed successfully' );
			// Get the assistant's response.
			$messages = self::get_thread_messages( $thread_id );
			Logger::log( 'Messages retrieved for thread: ' . $thread_id );
			if ( isset( $messages['error'] ) && $messages['error'] ) {
				return $messages;
			}

			// Store the assistant's response in WordPress
			$assistant_message = '';
			if ( ! empty( $messages['messages'] ) ) {
				$assistant_message = $messages['messages'][0]['content'];

				if ( self::$thread_repository ) {
					self::$thread_repository->addMessage( $thread_id, $assistant_message, 'assistant' );
					Logger::log( 'Assistant message saved to thread: ' . $thread_id );
				}
			}

			// Format and return the response.
			$parsedown = new Parsedown();
			return array(
				'error'     => false,
				'thread_id' => $thread_id,
				'message'   => $assistant_message ? $parsedown->text( $assistant_message ) : '',
				'raw'       => $messages,
			);
		} catch ( \Exception $e ) {
			Logger::log( 'Error in query_assistant: ' . $e->getMessage() );
			return array(
				'error'   => true,
				'message' => 'Error processing request: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Create a new thread in the OpenAI API.
	 *
	 * @return array Thread information or error.
	 */
	private static function create_thread(): array {
		$response = wp_remote_post(
			self::$api_url . '/threads',
			array(
				'headers'  => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'     => wp_json_encode( array() ),
				'timeout'  => 30, // Increase timeout to 30 seconds.
				'blocking' => true,
			)
		);

		Logger::log( 'Creating thread with response: ' . wp_remote_retrieve_body( $response ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Error creating thread: ' . $response->get_error_message() );
			return array(
				'error'   => true,
				'message' => 'Error creating thread: ' . $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['id'] ) ) {
			Logger::log( 'Error creating thread: Invalid response' );
			return array(
				'error'   => true,
				'message' => 'Error creating thread: Invalid response',
			);
		}

		return array(
			'error'     => false,
			'thread_id' => $body['id'],
		);
	}

	/**
	 * Add a message to an existing thread.
	 *
	 * @param string $thread_id The thread ID.
	 * @param string $content The message content.
	 * @return array Success status or error.
	 */
	private static function add_message_to_thread( string $thread_id, string $content ): array {
		$response = wp_remote_post(
			self::$api_url . '/threads/' . $thread_id . '/messages',
			array(
				'headers'  => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'     => wp_json_encode(
					array(
						'role'    => 'user',
						'content' => $content,
					)
				),
				'timeout'  => 30, // Increase timeout to 30 seconds.
				'blocking' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Error adding message: ' . $response->get_error_message() );
			return array(
				'error'   => true,
				'message' => 'Error adding message: ' . $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['id'] ) ) {
			Logger::log( 'Error adding message: Invalid response' );
			return array(
				'error'   => true,
				'message' => 'Error adding message: Invalid response',
			);
		}

		Logger::log( 'Message added with response: ' . wp_remote_retrieve_body( $response ) );

		return array(
			'error'   => false,
			'message' => 'Message added successfully',
		);
	}

	/**
	 * Run the assistant on a thread.
	 *
	 * @param string $thread_id The thread ID.
	 * @return array Success status or error.
	 */
	private static function run_assistant( string $thread_id ): array {
		$response = wp_remote_post(
			self::$api_url . '/threads/' . $thread_id . '/runs',
			array(
				'headers'  => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'     => wp_json_encode(
					array(
						'assistant_id' => self::$assistant_id,
						'instructions' => self::$system_instructions,
					)
				),
				'timeout'  => 30, // Increase timeout to 30 seconds.
				'blocking' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::log( 'API Call Error: ' . $response->get_error_message() );
			return array(
				'error'   => true,
				'message' => 'API Call Error: ' . $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['id'] ) ) {
			Logger::log( 'Error starting run: ' . wp_remote_retrieve_body( $response ) );
			return array(
				'error'   => true,
				'message' => 'Error starting run: ' . wp_remote_retrieve_body( $response ),
			);
		}

		$run_id = $body['id'];
		return self::wait_for_run_completion( $thread_id, $run_id );
	}

	/**
	 * Wait for a run to complete.
	 *
	 * @param string $thread_id The thread ID.
	 * @param string $run_id The run ID.
	 * @return array Success status or error.
	 */
	private static function wait_for_run_completion( string $thread_id, string $run_id ): array {
		$max_attempts = 30;
		$attempt      = 0;

		while ( $attempt < $max_attempts ) {
			++$attempt;

			sleep( self::$sleep_time );

			$response = wp_remote_get(
				self::$api_url . '/threads/' . $thread_id . '/runs/' . $run_id,
				array(
					'headers'  => array(
						'Authorization' => 'Bearer ' . self::$api_key,
						'OpenAI-Beta'   => 'assistants=v2',
					),
					'timeout'  => 30, // Increase timeout to 30 seconds.
					'blocking' => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				Logger::error( 'Error checking run status: ' . $response->get_error_message() );
				return array(
					'error'   => true,
					'message' => 'Error checking run status: ' . $response->get_error_message(),
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! isset( $body['status'] ) ) {
				Logger::error( 'Error checking run status: Invalid response' );
				return array(
					'error'   => true,
					'message' => 'Error checking run status: Invalid response',
				);
			}

			Logger::log( 'Run status: ' . $body['status'] );
			Logger::log( 'Sleep time: ' . self::$sleep_time . ' seconds' );
			Logger::log( 'Response: ' . wp_remote_retrieve_body( $response ) );

			if ( 'completed' === $body['status'] ) {
				Logger::log( 'Run completed successfully' );
				return array(
					'error' => false,
				);
			}

			if ( in_array( $body['status'], array( 'failed', 'cancelled', 'expired' ), true ) ) {
				Logger::error( 'Run failed with status: ' . $body['status'] );
				return array(
					'error'   => true,
					'message' => 'Run failed with status: ' . $body['status'],
				);
			}

			Logger::log( 'Run status: ' . $body['status'] );
		}

		Logger::log( 'Run timed out after ' . $max_attempts . ' attempts' );
		return array(
			'error'   => true,
			'message' => 'Run timed out after ' . $max_attempts . ' attempts',
		);
	}

	/**
	 * Get the messages in a thread.
	 *
	 * @param string $thread_id The thread ID.
	 * @return array Messages or error.
	 */
	private static function get_thread_messages( string $thread_id ): array {
		$response = wp_remote_get(
			self::$api_url . '/threads/' . $thread_id . '/messages',
			array(
				'headers'  => array(
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout'  => 30, // Increase timeout to 30 seconds.
				'blocking' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Error getting messages: ' . $response->get_error_message() );
			return array(
				'error'   => true,
				'message' => 'Error getting messages: ' . $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			Logger::log( 'Error getting messages: Invalid response' );
			return array(
				'error'   => true,
				'message' => 'Error getting messages: Invalid response',
			);
		}

		$messages = array();
		foreach ( $body['data'] as $message ) {
			if ( 'assistant' === $message['role'] && isset( $message['content'][0]['text']['value'] ) ) {
				$messages[] = array(
					'role'    => 'assistant',
					'content' => $message['content'][0]['text']['value'],
				);
			}
		}

		return array(
			'error'    => false,
			'messages' => $messages,
		);
	}
}

<?php
namespace ChatbotGPT;

use ChatbotGPT\ChatbotGPTLogger;
use Parsedown;

/**
 * Class ChatbotGPTAssistant
 *
 * Handles communication with OpenAI Assistant API.
 */
class ChatbotGPTAssistant {
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
	 * Initialize the Assistant settings.
	 */
	public static function init() {
		self::$api_key             = get_option( 'chatbot_gpt_api_key' );
		self::$api_url             = get_option( 'chatbot_gpt_api_url' ) ?? 'https://api.openai.com/v1';
		self::$assistant_id        = get_option( 'chatbot_gpt_assistant_id' ) ?? '';
		self::$sleep_time          = intval( get_option( 'chatbot_gpt_assistant_waiting_time_in_seconds' ) ) ?? 5;
		self::$system_instructions = get_option( 'chatbot_gpt_system_instructions' ) ?? '';
	}

	/**
	 * Send a user query to the Assistant.
	 *
	 * @param string      $query User's input question.
	 * @param string|null $thread_id Existing thread ID if continuing a conversation.
	 * @return array Response from the Assistant.
	 */
	public static function query_assistant( string $query, ?string $thread_id = null ): array {
		self::init();

		if ( empty( $thread_id ) ) {
			$thread_id = self::create_thread();
			if ( empty( $thread_id ) ) {
				return array( 'error' => 'Error: Could not create a conversation thread.' );
			}
		}

		$message_status = self::send_message_to_thread( $thread_id, $query );
		if ( $message_status !== true ) {
			return array( 'error' => 'Error: Unable to send message to Assistant.' );
		}

		$run_id = self::run_assistant( $thread_id );
		if ( empty( $run_id ) ) {
			return array( 'error' => 'Error: Unable to Run the query process.' );
		}

		if ( ! self::wait_for_response( $thread_id, $run_id ) ) {
			return array( 'error' => 'Error: Failed to retrieve Assistant response.' );
		}

		$assistant_response = self::get_assistant_response( $thread_id );

		$assistant_response = self::format_response( $assistant_response );

		return array(
			'text'      => $assistant_response,
			'thread_id' => $thread_id,
		);
	}

	/**
	 * Create a new thread in OpenAI API.
	 *
	 * @return string|null Thread ID or null if failed.
	 */
	private static function create_thread(): ?string {
		$response = wp_remote_post(
			self::$api_url . '/threads',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			ChatbotGPTLogger::error( 'Error creating thread in OpenAI Assistant API.' );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['id'] ?? null;
	}

	/**
	 * Send user message to an existing thread.
	 *
	 * @param string $thread_id The unique identifier for the chat thread.
	 * @param string $query The user's input question.
	 * @return bool
	 */
	private static function send_message_to_thread( string $thread_id, string $query ): bool {
		$response = wp_remote_post(
			self::$api_url . "/threads/$thread_id/messages",
			array(
				'body'    => wp_json_encode(
					array(
						'role'    => 'user',
						'content' => $query,
					)
				),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout' => 20,
			)
		);

		return ! is_wp_error( $response );
	}

	/**
	 * Run the Assistant process.
	 *
	 * @param string $thread_id The unique identifier for the chat thread.
	 * @return string|null Run ID or null if failed.
	 */
	private static function run_assistant( string $thread_id ): ?string {
		$body = array( 'assistant_id' => self::$assistant_id );

		if ( ! empty( self::$system_instructions ) ) {
			$body['instructions'] = self::$system_instructions;
		}

		$response = wp_remote_post(
			self::$api_url . "/threads/$thread_id/runs",
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout' => 20,
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['id'] ?? null;
	}

	/**
	 * Wait for the Assistant to complete the response.
	 *
	 * @param string $thread_id The unique identifier for the chat thread.
	 * @param string $run_id The unique identifier for the run.
	 * @return bool
	 */
	private static function wait_for_response( string $thread_id, string $run_id ): bool {
		for ( $i = 0; $i < 10; $i++ ) {
			sleep( self::$sleep_time );

			$response = wp_remote_get(
				self::$api_url . "/threads/$thread_id/runs/$run_id",
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . self::$api_key,
						'OpenAI-Beta'   => 'assistants=v2',
						'Content-Type'  => 'application/json',
					),
				)
			);

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 'completed' === $body['status'] ) {
				return true;
			}

			if ( key_exists( 'response', $response ) && key_exists( 'response', $response['response'] ) && key_exists( 'code', $response['response']['response'] ) && 400 === $response['response']['response']['code'] ) {
				ChatbotGPTLogger::error( 'Error: ' . $response['response']['response']['message'] );
				return false;
			}
		}

		return false;
	}

	/**
	 * Get the Assistant's response.
	 *
	 * @param string $thread_id The unique identifier for the chat thread.
	 * @return string
	 */
	private static function get_assistant_response( string $thread_id ): string {
		$headers  = array(
			'Authorization' => 'Bearer ' . self::$api_key,
			'OpenAI-Beta'   => 'assistants=v2',
			'Content-Type'  => 'application/json',
		);
		$response = wp_remote_get(
			self::$api_url . "/threads/$thread_id/messages",
			array( 'headers' => $headers )
		);

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			ChatbotGPTLogger::error( 'Error: Unable to retrieve Assistant response.' );
			return 'No response from Assistant.';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		foreach ( $body['data'] as $message ) {
			if ( 'assistant' === $message['role'] ) {
				return $message['content'][0]['text']['value'] ?? 'No response from Assistant.';
			}
		}

		return 'No response from Assistant.';
	}

	/**
	 * Format the response text using Parsedown.
	 *
	 * @param string $text The response text.
	 * @return string
	 */
	private static function format_response( string $text ): string {
		$parsedown = new Parsedown();
		return $parsedown->text( $text );
	}

	/**
	 * Get assistant information including the model being used.
	 *
	 * @return array Information about the assistant or error.
	 */
	public static function get_assistant_info(): array {
		self::init();

		if ( empty( self::$api_key ) || empty( self::$assistant_id ) ) {
			return array(
				'error'   => true,
				'message' => 'Error: API key or Assistant ID is missing.',
			);
		}

		$response = wp_remote_get(
			self::$api_url . '/assistants/' . self::$assistant_id,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::$api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			ChatbotGPTLogger::error( 'Error retrieving assistant information: ' . $response->get_error_message() );
			return array(
				'error'   => true,
				'message' => 'Error: ' . $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			ChatbotGPTLogger::error( 'Error retrieving assistant info. Status code: ' . $status_code );
			return array(
				'error'   => true,
				'message' => 'Error: Unable to retrieve assistant information. Status code: ' . $status_code,
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body ) || ! isset( $body['model'] ) ) {
			return array(
				'error'   => true,
				'message' => 'Error: Retrieved assistant info is empty or invalid.',
			);
		}

		return array(
			'error'       => false,
			'id'          => $body['id'] ?? '',
			'name'        => $body['name'] ?? '',
			'model'       => $body['model'] ?? '',
			'description' => $body['description'] ?? '',
			'created_at'  => isset( $body['created_at'] ) ? date( 'Y-m-d H:i:s', $body['created_at'] ) : '',
		);
	}
}

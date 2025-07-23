<?php
namespace WPAIS\Api;

use WPAIS\Utils\Logger;

class Summarizer {
	public static function generate_summary( array $messages ): ?string {
			$api_key = get_option( 'wp_ai_assistant_api_key' );
			$api_url = get_option( 'wp_ai_assistant_api_url', 'https://api.openai.com/v1' );
			$model   = get_option( 'wp_ai_assistant_summary_model', 'gpt-3.5-turbo' );

		if ( empty( $api_key ) ) {
				return null;
		}

			$conversation = '';
		foreach ( $messages as $message ) {
				$role          = ucfirst( $message['role'] );
				$conversation .= "$role: {$message['content']}\n";
		}

			$payload = array(
				'model'      => $model,
				'messages'   => array(
					array(
						'role'    => 'system',
						'content' => __( 'Resume brevemente el siguiente chat.', 'wp-ai-assistant' ),
					),
					array(
						'role'    => 'user',
						'content' => $conversation,
					),
				),
				'max_tokens' => 50,
			);

			$response = wp_remote_post(
				trailingslashit( $api_url ) . 'chat/completions',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 30,
				)
			);

		if ( is_wp_error( $response ) ) {
				Logger::error( 'Summary error: ' . $response->get_error_message() );
				return null;
		}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
				Logger::error( 'Summary error: invalid response' );
				return null;
		}

			return trim( $body['choices'][0]['message']['content'] );
	}
}

<?php
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
namespace ChatbotGPT;

/**
 * Class ChatbotGPTLogger
 *
 * Handles logging for WP-CLI and file logs.
 */
class ChatbotGPTLogger {
	/**
	 * Logs a message to WP-CLI or the error log.
	 *
	 * @param string $message The message to log.
	 */
	public static function log( $message ) {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( '[Chatbot GPT]' . $message );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Chatbot GPT]' . $message );
		}
	}

	/**
	 * Logs a success message.
	 *
	 * @param string $message The success message.
	 */
	public static function success( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::success( '[Chatbot GPT]' . $message );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Chatbot GPT] SUCCESS: ' . $message );
		}
	}

	/**
	 * Logs an error message and optionally stops execution.
	 *
	 * @param string $message The error message.
	 * @param bool   $exit Whether to halt execution in WP-CLI.
	 */
	public static function error( $message, $exit = false ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( '[Chatbot GPT]' . $message, $exit );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Chatbot GPT] ERROR: ' . $message );
		}
	}
}

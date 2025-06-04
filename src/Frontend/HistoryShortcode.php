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
		if ( ! is_admin() && has_shortcode( get_post()->post_content ?? '', 'wp_ai_assistant_history' ) ) {
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
				)
			);
		}
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
	public static function render_history_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'title'    => 'Historial de Conversaciones',
				'redirect' => '', // URL to redirect to when continuing a chat.
			),
			$atts,
			'wp_ai_assistant_history'
		);

		// Get the current user or session.
		$user_id    = get_current_user_id();
		$session_id = Session::get_session_id();

		// Debug log session and user info
		error_log( 'WP AI Assistant: User ID: ' . ( $user_id ? $user_id : 'none' ) . ', Session ID: ' . $session_id );

		// Get threads.
		$repository = self::get_repository();
		$threads    = $repository->getThreadsByUserOrSession(
			$user_id ? (string) $user_id : null,
			$session_id,
			(int) $atts['limit']
		);

		ob_start();

		if ( empty( $threads ) ) {
			echo '<div class="wpai-history-container">';
			echo '<h2>' . esc_html( $atts['title'] ) . '</h2>';
			echo '<p class="wpai-no-history">No tienes conversaciones guardadas todavía.</p>';
			echo '</div>';
			return ob_get_clean();
		}

		// Set up Parsedown for Markdown rendering
		$parsedown = new Parsedown();
		?>
		<div class="wpai-history-container">
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>

			<div class="wpai-history-threads">
				<?php foreach ( $threads as $thread ) : ?>
					<div class="wpai-thread" data-thread-id="<?php echo esc_attr( $thread['thread_id'] ); ?>">
						<div class="wpai-thread-header">
							<h3><?php echo esc_html( $thread['title'] ); ?></h3>
							<span class="wpai-thread-date"><?php echo esc_html( $thread['date'] ); ?></span>
						</div>

						<div class="wpai-thread-preview">
							<?php if ( ! empty( $thread['last_message']['user_message'] ) ) : ?>
								<div class="wpai-preview-user">
									<strong>Pregunta:</strong> <?php echo esc_html( wp_trim_words( $thread['last_message']['user_message'], 15 ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $thread['last_message']['assistant_message'] ) ) : ?>
								<div class="wpai-preview-assistant">
									<strong>Respuesta:</strong> <?php echo esc_html( wp_trim_words( $thread['last_message']['assistant_message'], 20 ) ); ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="wpai-thread-actions">
							<button class="wpai-view-thread">Ver conversación completa</button>
							<button class="wpai-continue-chat"
								data-thread-id="<?php echo esc_attr( $thread['thread_id'] ); ?>"
								<?php if ( ! empty( $atts['redirect'] ) ) : ?>
								data-redirect-url="<?php echo esc_url( $atts['redirect'] ); ?>"
								<?php endif; ?>>Continuar chat</button>
						</div>

						<div class="wpai-thread-messages">
							<?php foreach ( $thread['messages'] as $message ) : ?>
								<div class="wpai-message wpai-message-<?php echo esc_attr( $message['role'] ); ?>">
									<div class="wpai-message-header">
										<strong><?php echo $message['role'] === 'user' ? 'Usuario' : 'Asistente'; ?></strong>
										<?php if ( ! empty( $message['timestamp'] ) ) : ?>
											<span class="wpai-message-time"><?php echo date( 'd/m/Y H:i', $message['timestamp'] ); ?></span>
										<?php endif; ?>
									</div>
									<div class="wpai-message-content">
										<?php echo $parsedown->text( $message['content'] ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

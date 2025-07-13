<?php
/**
 * History Template
 *
 * This template is used to render the conversation history.
 *
 * @var string $title Title of the history section.
 * @var array $threads Array of conversation threads.
 * @var string $redirect Optional URL to redirect to when continuing a chat.
 * @var Parsedown $parsedown Parsedown instance for Markdown rendering.
 * @package WPAIS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="wpai-history" class="wpai-history-container">
	<h2><?php echo esc_html( $title ); ?></h2>

	<?php if ( empty( $threads ) ) : ?>
		<p class="wpai-no-history"><?php esc_html_e( 'You have no saved conversations yet.', 'wp-ai-assistant' ); ?></p>
	<?php else : ?>
		<div class="wpai-history-threads">
			<?php foreach ( $threads as $thread ) : ?>
				<div class="wpai-thread" data-thread-id="<?php echo esc_attr( $thread['thread_id'] ); ?>">
										<div class="wpai-thread-header">
												<h3><?php echo esc_html( $thread['summary'] ); ?></h3>
												<span class="wpai-thread-date"><?php echo esc_html( $thread['date'] ); ?></span>
										</div>

					<div class="wpai-thread-preview">
						<?php if ( ! empty( $thread['last_message']['user_message'] ) ) : ?>
							<div class="wpai-preview-user">
								<span class="preview-label"><?php esc_html_e( 'You:', 'wp-ai-assistant' ); ?></span> <?php echo esc_html( wp_trim_words( $thread['last_message']['user_message'], 15 ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $thread['last_message']['assistant_message'] ) ) : ?>
							<div class="wpai-preview-assistant">
								<span class="preview-label"><?php esc_html_e( 'Assistant:', 'wp-ai-assistant' ); ?></span> <?php echo esc_html( wp_trim_words( $thread['last_message']['assistant_message'], 20 ) ); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="wpai-thread-actions">
						<button class="wpai-view-thread">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
							<?php esc_html_e( 'View conversation', 'wp-ai-assistant' ); ?>
						</button>
						<button class="wpai-continue-chat"
							data-thread-id="<?php echo esc_attr( $thread['thread_id'] ); ?>"
							<?php if ( ! empty( $redirect ) ) : ?>
							data-redirect-url="<?php echo esc_url( $redirect ); ?>"
							<?php endif; ?>>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="9 18 15 12 9 6"></polyline>
							</svg>
							<?php esc_html_e( 'Continue chat', 'wp-ai-assistant' ); ?>
						</button>
					</div>

					<div class="wpai-thread-messages">
						<?php foreach ( $thread['messages'] as $message ) : ?>
							<div class="wpai-message wpai-message-<?php echo esc_attr( $message['role'] ); ?>">
								<div class="wpai-message-header">
									<strong class="message-role"><?php echo esc_html( $message['role'] === 'user' ? __( 'You', 'wp-ai-assistant' ) : __( 'Assistant', 'wp-ai-assistant' ) ); ?></strong>
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
	<?php endif; ?>
</div>

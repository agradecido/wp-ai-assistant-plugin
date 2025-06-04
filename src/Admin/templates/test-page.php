<?php
/**
 * Template for the ChatbotGPT test page
 *
 * @package ChatbotGPT
 * @var array $assistant_info Information about the configured assistant
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'AI Assistant Test', 'wp-ai-assistant' ); ?></h1>
	<p><?php esc_html_e( 'Use this section to test queries with the configured assistant.', 'wp-ai-assistant' ); ?></p>
	
	<?php if ( ! $assistant_info['error'] ) : ?>
	<div class="assistant-info">
		<h3><?php esc_html_e( 'Assistant Information', 'wp-ai-assistant' ); ?></h3>
		<table class="widefat striped">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-ai-assistant' ); ?></th>
					<td><?php echo esc_html( $assistant_info['name'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Model', 'wp-ai-assistant' ); ?></th>
					<td><strong><?php echo esc_html( $assistant_info['model'] ); ?></strong></td>
				</tr>
				<?php if ( ! empty( $assistant_info['description'] ) ) : ?>
				<tr>
						<th><?php esc_html_e( 'Description', 'wp-ai-assistant' ); ?></th>
					<td><?php echo esc_html( $assistant_info['description'] ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><?php esc_html_e( 'ID', 'wp-ai-assistant' ); ?></th>
					<td><?php echo esc_html( $assistant_info['id'] ); ?></td>
				</tr>
				<?php if ( ! empty( $assistant_info['created_at'] ) ) : ?>
				<tr>
						<th><?php esc_html_e( 'Created', 'wp-ai-assistant' ); ?></th>
					<td><?php echo esc_html( $assistant_info['created_at'] ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php elseif ( isset( $assistant_info['message'] ) ) : ?>
	<div class="notice notice-error">
		<p><?php echo esc_html( $assistant_info['message'] ); ?></p>
	</div>
	<?php endif; ?>
	
	<div id="chatbot-admin-test">
		<div id="chat-output" class="admin-chat-output"></div>
		<div id="chat-spinner" class="admin-spinner">
			<div class="dot-spinner">
				<div class="dot1"></div>
				<div class="dot2"></div>
				<div class="dot3"></div>
			</div>
		</div>
		<textarea id="chat-input" rows="4" placeholder="<?php esc_attr_e( 'Type your query to the assistant here', 'wp-ai-assistant' ); ?>"></textarea>
		<button id="chat-submit" class="button button-primary"><?php esc_html_e( 'Send Query', 'wp-ai-assistant' ); ?></button>
	</div>
</div>

<?php
/**
 * Chatbot Template
 *
 * This template is used to render the chatbot.
 *
 * @var string $nonce Security nonce for AJAX requests.
 * @var bool $is_enabled Whether the chatbot is enabled.
 * @var string $disabled_message Message to show when chatbot is disabled.
 * @package WPAIS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="chatbot-container" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-enabled="<?php echo $is_enabled ? '1' : '0'; ?>" data-disabled-message="<?php echo esc_attr( $disabled_message ); ?>">
	<div class="chatgpt-style-container">
		<div id="chat-header">
				<div class="chat-title"><?php echo esc_html__( 'T16 Assistant', 'wp-ai-assistant' ); ?></div>
		</div>
		<div id="chat-messages-container">
			<div id="chat-output"></div>
			<div id="chat-spinner">
				<div class="typing-indicator">
					<span></span>
					<span></span>
					<span></span>
				</div>
			</div>
		</div>
		
		<div class="chat-input-container">
			<input type="text" id="chat-input" placeholder="<?php echo esc_attr__( 'Type your query to the assistant here', 'wp-ai-assistant' ); ?>" />
			<button id="chat-submit">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
	</div>
</div>

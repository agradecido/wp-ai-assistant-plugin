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
	<div id="chat-output"></div>
	<div id="chat-spinner">
		<svg class="spinner" width="50px" height="50px" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
			<circle cx="25" cy="25" r="10" stroke-width="2" stroke-dasharray="31.4 31.4" stroke-linecap="round">
				<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1.5s" repeatCount="indefinite" />
			</circle>
			<circle cx="25" cy="25" r="1.5">
				<animate attributeName="r" values="1.5;3;1.5" dur="2.5s" repeatCount="indefinite"/>
			</circle>
		</svg>
	</div>
	<input type="text" id="chat-input" placeholder='Escribe aquí tu consulta al asistente' />
	<button id="chat-submit">Enviar</button>
</div>

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
	<h1>Prueba del Asistente ChatGPT</h1>
	<p>Usa esta sección para probar consultas al asistente configurado.</p>
	
	<?php if ( ! $assistant_info['error'] ) : ?>
	<div class="assistant-info">
		<h3>Información del Asistente</h3>
		<table class="widefat striped">
			<tbody>
				<tr>
					<th>Nombre</th>
					<td><?php echo esc_html( $assistant_info['name'] ); ?></td>
				</tr>
				<tr>
					<th>Modelo</th>
					<td><strong><?php echo esc_html( $assistant_info['model'] ); ?></strong></td>
				</tr>
				<?php if ( ! empty( $assistant_info['description'] ) ) : ?>
				<tr>
					<th>Descripción</th>
					<td><?php echo esc_html( $assistant_info['description'] ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th>ID</th>
					<td><?php echo esc_html( $assistant_info['id'] ); ?></td>
				</tr>
				<?php if ( ! empty( $assistant_info['created_at'] ) ) : ?>
				<tr>
					<th>Creado</th>
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
		<textarea id="chat-input" rows="4" placeholder="Escribe aquí tu consulta al asistente"></textarea>
		<button id="chat-submit" class="button button-primary">Enviar Consulta</button>
	</div>
</div>

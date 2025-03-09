<?php
/**
 * Template for the ChatbotGPT settings page
 *
 * @package ChatbotGPT
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>Chatbot GPT Settings</h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'wp_ai_chatbot_settings_group' ); ?>
		<?php do_settings_sections( 'wp_ai_chatbot_settings_group' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="wp_ai_chatbot_api_url">¿Activar chat?</label></th>
				<td>
					<input type="checkbox" id="wp_ai_chatbot_enable" name="wp_ai_chatbot_enable" value="1" <?php checked( get_option( 'wp_ai_chatbot_enable' ), 1 ); ?> />
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_chatbot_system_instructions">Instrucciones del sistema</label></th>
				<td>
					<textarea id="wp_ai_chatbot_system_instructions" name="wp_ai_chatbot_system_instructions" rows="6" class="large-text" style="max-width: 600px; min-height: 300px;"><?php echo esc_textarea( get_option( 'wp_ai_chatbot_system_instructions' ) ); ?></textarea>
					<p class="description">Define las instrucciones del sistema para el asistente. Estas instrucciones controlan el comportamiento del chatbot.</p>
				</td>
			</tr>					
			<tr>
				<th><label for="wp_ai_chatbot_api_url">OpenAI API URL</label></th>
				<td><input type="text" id="wp_ai_chatbot_api_url" name="wp_ai_chatbot_api_url" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_api_url' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_chatbot_api_key">OpenAI API Key</label></th>
				<td><input type="password" id="wp_ai_chatbot_api_key" name="wp_ai_chatbot_api_key" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_api_key' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_chatbot_assistant_id">Assistant ID</label></th>
				<td><input type="text" id="wp_ai_chatbot_assistant_id" name="wp_ai_chatbot_assistant_id" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_assistant_id' ) ); ?>" class="regular-text" /></td>
			</tr>	
			<tr>
				<th><label for="wp_ai_chatbot_assistant_waiting_time_in_seconds">Tiempo de espera a la respuesta en segundos</label></th>
				<td><input type="number" id="wp_ai_chatbot_assistant_waiting_time_in_seconds" name="wp_ai_chatbot_assistant_waiting_time_in_seconds" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_assistant_waiting_time_in_seconds' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_chatbot_main_color">Color principal</label></th>
				<td><input type="color" id="wp_ai_chatbot_main_color" name="wp_ai_chatbot_main_color" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_main_color' ) ); ?>" class="regular-text" /></td>
			</tr>								
			<tr>
				<th><label for="wp_ai_chatbot_secondary_color">Color secundario</label></th>
				<td><input type="color" id="wp_ai_chatbot_secondary_color" name="wp_ai_chatbot_secondary_color" value="<?php echo esc_attr( get_option( 'wp_ai_chatbot_secondary_color' ) ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
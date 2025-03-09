<?php
/**
 * Template for the ChatbotGPT settings page
 *
 * @package ChatbotGPT
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>Chatbot GPT Settings</h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'chatbot_gpt_settings_group' ); ?>
		<?php do_settings_sections( 'chatbot_gpt_settings_group' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="chatbot_gpt_api_url">¿Activar chat?</label></th>
				<td>
					<input type="checkbox" id="chatbot_gpt_enable" name="chatbot_gpt_enable" value="1" <?php checked( get_option( 'chatbot_gpt_enable' ), 1 ); ?> />
				</td>
			</tr>
			<tr>
				<th><label for="chatbot_gpt_system_instructions">Instrucciones del sistema</label></th>
				<td>
					<textarea id="chatbot_gpt_system_instructions" name="chatbot_gpt_system_instructions" rows="6" class="large-text" style="max-width: 600px; min-height: 300px;"><?php echo esc_textarea( get_option( 'chatbot_gpt_system_instructions' ) ); ?></textarea>
					<p class="description">Define las instrucciones del sistema para el asistente. Estas instrucciones controlan el comportamiento del chatbot.</p>
				</td>
			</tr>					
			<tr>
				<th><label for="chatbot_gpt_api_url">OpenAI API URL</label></th>
				<td><input type="text" id="chatbot_gpt_api_url" name="chatbot_gpt_api_url" value="<?php echo esc_attr( get_option( 'chatbot_gpt_api_url' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="chatbot_gpt_api_key">OpenAI API Key</label></th>
				<td><input type="password" id="chatbot_gpt_api_key" name="chatbot_gpt_api_key" value="<?php echo esc_attr( get_option( 'chatbot_gpt_api_key' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="chatbot_gpt_assistant_api_url">Assistant API URL</label></th>
				<td><input type="text" id="chatbot_gpt_assistant_api_url" name="chatbot_gpt_assistant_api_url" value="<?php echo esc_attr( get_option( 'chatbot_gpt_assistant_api_url' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="chatbot_gpt_assistant_id">Assistant ID</label></th>
				<td><input type="text" id="chatbot_gpt_assistant_id" name="chatbot_gpt_assistant_id" value="<?php echo esc_attr( get_option( 'chatbot_gpt_assistant_id' ) ); ?>" class="regular-text" /></td>
			</tr>	
			<tr>
				<th><label for="chatbot_gpt_assistant_waiting_time_in_seconds">Tiempo de espera a la respuesta en segundos</label></th>
				<td><input type="number" id="chatbot_gpt_assistant_waiting_time_in_seconds" name="chatbot_gpt_assistant_waiting_time_in_seconds" value="<?php echo esc_attr( get_option( 'chatbot_gpt_assistant_waiting_time_in_seconds' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="chatbot_gpt_main_color">Color principal</label></th>
				<td><input type="color" id="chatbot_gpt_main_color" name="chatbot_gpt_main_color" value="<?php echo esc_attr( get_option( 'chatbot_gpt_main_color' ) ); ?>" class="regular-text" /></td>
			</tr>								
			<tr>
				<th><label for="chatbot_gpt_secondary_color">Color secundario</label></th>
				<td><input type="color" id="chatbot_gpt_secondary_color" name="chatbot_gpt_secondary_color" value="<?php echo esc_attr( get_option( 'chatbot_gpt_secondary_color' ) ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
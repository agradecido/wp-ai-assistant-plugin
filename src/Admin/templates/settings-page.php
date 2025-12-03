<?php
/**
 * Template for the Assistant settings page
 *
 * @package WPAIS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'WP AI Assistant Settings', 'wp-ai-assistant' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'wp_ai_assistant_settings_group' ); ?>
		<?php do_settings_sections( 'wp_ai_assistant_settings_group' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="wp_ai_assistant_enable"><?php esc_html_e( 'Enable chat?', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="hidden" name="wp_ai_assistant_enable" value="0" />
					<input type="checkbox" id="wp_ai_assistant_enable" name="wp_ai_assistant_enable" value="true" <?php checked( get_option( 'wp_ai_assistant_enable' ), true ); ?> />
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_disabled_message"><?php esc_html_e( 'Message when chat is disabled', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="text" id="wp_ai_assistant_disabled_message" name="wp_ai_assistant_disabled_message" 
							value="<?php echo esc_attr( get_option( 'wp_ai_assistant_disabled_message', __( 'Chat temporarily disabled, please try again later or contact us', 'wp-ai-assistant' ) ) ); ?>" class="large-text" />
					<p class="description"><?php esc_html_e( 'This message is displayed when chat is disabled but the shortcode is still present on the page.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_system_instructions"><?php esc_html_e( 'System Instructions', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<textarea id="wp_ai_assistant_system_instructions" name="wp_ai_assistant_system_instructions" rows="6" class="large-text" style="max-width: 600px; min-height: 300px;"><?php echo esc_textarea( get_option( 'wp_ai_assistant_system_instructions' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Define the system instructions for the assistant. These instructions control the behavior of the chatbot.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>					
			<tr>
				<th><label for="wp_ai_assistant_api_url"><?php esc_html_e( 'OpenAI API URL', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="text" id="wp_ai_assistant_api_url" name="wp_ai_assistant_api_url" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_api_url' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_api_key"><?php esc_html_e( 'OpenAI API Key', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="password" id="wp_ai_assistant_api_key" name="wp_ai_assistant_api_key" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_api_key' ) ); ?>" class="regular-text" /></td>
			</tr>

			<!-- Mode Selection -->
			<tr>
				<th><label><?php esc_html_e( 'Operation Mode', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<?php $mode = get_option( 'wp_ai_assistant_mode', 'assistant' ); ?>
					<fieldset>
						<label>
							<input type="radio" name="wp_ai_assistant_mode" value="assistant" <?php checked( $mode, 'assistant' ); ?> />
							<strong><?php esc_html_e( 'Assistant API', 'wp-ai-assistant' ); ?></strong> - 
							<?php esc_html_e( 'Uses OpenAI Assistants with persistent threads', 'wp-ai-assistant' ); ?>
						</label>
						<br><br>
						<label>
							<input type="radio" name="wp_ai_assistant_mode" value="chat_completions" <?php checked( $mode, 'chat_completions' ); ?> />
							<strong><?php esc_html_e( 'Chat Completions', 'wp-ai-assistant' ); ?></strong> - 
							<?php esc_html_e( 'Supports Vector Stores and web search', 'wp-ai-assistant' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Choose the operation mode for the chatbot. Assistant API uses threads and runs, while Chat Completions offers web search and vector store integration.', 'wp-ai-assistant' ); ?>
					</p>
				</td>
			</tr>

			<!-- Assistant API Settings -->
			<tr class="mode-setting assistant-mode">
				<th><label for="wp_ai_assistant_assistant_id"><?php esc_html_e( 'Assistant ID', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="text" id="wp_ai_assistant_assistant_id" name="wp_ai_assistant_assistant_id" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_assistant_id' ) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Required for Assistant API mode', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr class="mode-setting assistant-mode">
				<th><label for="wp_ai_assistant_assistant_waiting_time_in_seconds"><?php esc_html_e( 'Response waiting time in seconds', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="number" id="wp_ai_assistant_assistant_waiting_time_in_seconds" name="wp_ai_assistant_assistant_waiting_time_in_seconds" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_assistant_waiting_time_in_seconds', 5 ) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Time to wait between status checks when running the assistant', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>

			<!-- Chat Completions Settings -->
			<tr class="mode-setting chat-completions-mode">
				<th><label for="wp_ai_assistant_chat_model"><?php esc_html_e( 'Chat Model', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<select id="wp_ai_assistant_chat_model" name="wp_ai_assistant_chat_model">
						<?php $chat_model = get_option( 'wp_ai_assistant_chat_model', 'gpt-4o' ); ?>
						<option value="gpt-4o" <?php selected( $chat_model, 'gpt-4o' ); ?>>gpt-4o</option>
						<option value="gpt-4o-mini" <?php selected( $chat_model, 'gpt-4o-mini' ); ?>>gpt-4o-mini</option>
						<option value="gpt-4-turbo" <?php selected( $chat_model, 'gpt-4-turbo' ); ?>>gpt-4-turbo</option>
						<option value="gpt-3.5-turbo" <?php selected( $chat_model, 'gpt-3.5-turbo' ); ?>>gpt-3.5-turbo</option>
					</select>
					<p class="description"><?php esc_html_e( 'Model to use for Chat Completions mode', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr class="mode-setting chat-completions-mode">
				<th><label for="wp_ai_assistant_vector_store_id"><?php esc_html_e( 'Vector Store ID', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="text" id="wp_ai_assistant_vector_store_id" name="wp_ai_assistant_vector_store_id" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_vector_store_id' ) ); ?>" class="regular-text" placeholder="vs_xxx" />
					<p class="description"><?php esc_html_e( 'Optional. Enter your OpenAI Vector Store ID for file search capabilities.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr class="mode-setting chat-completions-mode">
				<th><label for="wp_ai_assistant_enable_web_search"><?php esc_html_e( 'Enable Web Search', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="hidden" name="wp_ai_assistant_enable_web_search" value="0" />
					<input type="checkbox" id="wp_ai_assistant_enable_web_search" name="wp_ai_assistant_enable_web_search" value="1" <?php checked( get_option( 'wp_ai_assistant_enable_web_search' ), 1 ); ?> />
					<p class="description"><?php esc_html_e( 'Allow the chatbot to search the web for information using OpenAI\'s web search tool.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>

			<!-- Common Settings -->
			<tr>
				<th><label for="wp_ai_assistant_summary_model"><?php esc_html_e( 'Model for summaries', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<select id="wp_ai_assistant_summary_model" name="wp_ai_assistant_summary_model">
						<?php $summary_model = get_option( 'wp_ai_assistant_summary_model', 'gpt-3.5-turbo' ); ?>
						<option value="gpt-3.5-turbo" <?php selected( $summary_model, 'gpt-3.5-turbo' ); ?>>gpt-3.5-turbo</option>
						<option value="gpt-4o" <?php selected( $summary_model, 'gpt-4o' ); ?>>gpt-4o</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_daily_limit"><?php esc_html_e( 'Daily message limit per user', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="number" id="wp_ai_assistant_daily_limit" name="wp_ai_assistant_daily_limit" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_daily_limit', 20 ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_quota_exceeded_message"><?php esc_html_e( 'Quota exceeded message (English)', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<textarea id="wp_ai_assistant_quota_exceeded_message" name="wp_ai_assistant_quota_exceeded_message" rows="2" class="large-text"><?php echo esc_textarea( get_option( 'wp_ai_assistant_quota_exceeded_message', __( 'Daily quota exceeded. Please try again tomorrow 🤖', 'wp-ai-assistant' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Message to display when the user exceeds their daily message quota.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_quota_exceeded_message_es"><?php esc_html_e( 'Quota exceeded message (Spanish)', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<textarea id="wp_ai_assistant_quota_exceeded_message_es" name="wp_ai_assistant_quota_exceeded_message_es" rows="2" class="large-text"><?php echo esc_textarea( get_option( 'wp_ai_assistant_quota_exceeded_message_es', __( 'Has excedido tu cuota diaria de consultas. Por favor vuelve mañana 🤖', 'wp-ai-assistant' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Mensaje que se muestra cuando el usuario supera su cuota diaria de mensajes.', 'wp-ai-assistant' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_main_color"><?php esc_html_e( 'Main Color', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="color" id="wp_ai_assistant_main_color" name="wp_ai_assistant_main_color" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_main_color' ) ); ?>" class="regular-text" /></td>
			</tr>						
			<tr>
				<th><label for="wp_ai_assistant_secondary_color"><?php esc_html_e( 'Secondary Color', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="color" id="wp_ai_assistant_secondary_color" name="wp_ai_assistant_secondary_color" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_secondary_color' ) ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

<script>
(function() {
	function toggleModeSettings() {
		var mode = document.querySelector('input[name="wp_ai_assistant_mode"]:checked').value;
		document.querySelectorAll('.mode-setting').forEach(function(el) {
			el.style.display = 'none';
		});
		if (mode === 'assistant') {
			document.querySelectorAll('.assistant-mode').forEach(function(el) {
				el.style.display = '';
			});
		} else {
			document.querySelectorAll('.chat-completions-mode').forEach(function(el) {
				el.style.display = '';
			});
		}
	}
	document.querySelectorAll('input[name="wp_ai_assistant_mode"]').forEach(function(radio) {
		radio.addEventListener('change', toggleModeSettings);
	});
	toggleModeSettings();
})();
</script>
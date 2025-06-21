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
			<tr>
				<th><label for="wp_ai_assistant_assistant_id"><?php esc_html_e( 'Assistant ID', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="text" id="wp_ai_assistant_assistant_id" name="wp_ai_assistant_assistant_id" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_assistant_id' ) ); ?>" class="regular-text" /></td>
			</tr>	
			<tr>
				<th><label for="wp_ai_assistant_assistant_waiting_time_in_seconds"><?php esc_html_e( 'Response waiting time in seconds', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="number" id="wp_ai_assistant_assistant_waiting_time_in_seconds" name="wp_ai_assistant_assistant_waiting_time_in_seconds" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_assistant_waiting_time_in_seconds' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_daily_limit"><?php esc_html_e( 'Daily message limit per user', 'wp-ai-assistant' ); ?></label></th>
				<td><input type="number" id="wp_ai_assistant_daily_limit" name="wp_ai_assistant_daily_limit" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_daily_limit', 20 ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wp_ai_assistant_quota_exceeded_message"><?php esc_html_e( 'Quota exceeded message', 'wp-ai-assistant' ); ?></label></th>
				<td>
					<input type="text" id="wp_ai_assistant_quota_exceeded_message" name="wp_ai_assistant_quota_exceeded_message" value="<?php echo esc_attr( get_option( 'wp_ai_assistant_quota_exceeded_message', __( 'Daily quota exceeded. Please try again tomorrow 🤖', 'wp-ai-assistant' ) ) ); ?>" class="large-text" />
					<p class="description"><?php esc_html_e( 'Message to display when the user exceeds their daily message quota.', 'wp-ai-assistant' ); ?></p>
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
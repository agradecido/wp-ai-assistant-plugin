<?php
namespace ChatbotGPT;

/**
 * Class ChatbotGPTSettings
 *
 * Handles the settings page for configuring the chatbot in the WordPress admin panel.
 */
class ChatbotGPTSettings {
	/**
	 * Registers the settings page in the WordPress admin menu.
	 */
	public static function register() {
		add_action( 'admin_menu', array( self::class, 'add_settings_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/**
	 * Adds the settings page as a top-level menu in WordPress admin.
	 */
	public static function add_settings_page() {
		add_menu_page(
			'Chatbot GPT Settings',
			'Chatbot GPT',
			'manage_options',
			'chatbot-gpt-settings',
			array( self::class, 'render_settings_page' ),
			'dashicons-format-chat',
			25
		);
	}

	/**
	 * Registers the settings fields.
	 */
	public static function register_settings() {
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_enable', array( 'default' => 0 ) );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_api_url' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_api_key' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_api_url' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_id' );
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_assistant_waiting_time_in_seconds' );
		register_setting(
			'chatbot_gpt_settings_group',
			'chatbot_gpt_main_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#93c462',
			)
		);
		register_setting(
			'chatbot_gpt_settings_group',
			'chatbot_gpt_secondary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#549626',
			)
		);
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_settings_page() {
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
		<?php
	}
}

// Register the settings page.
ChatbotGPTSettings::register();

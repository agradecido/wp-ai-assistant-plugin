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
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue assets for admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( $hook === 'chatbot-gpt_page_chatbot-gpt-test' ) {
			wp_enqueue_style( 'chatbot-gpt-admin-style', plugin_dir_url( __DIR__ ) . 'assets/css/chatbot.css', array(), '1.0' );
			wp_enqueue_script( 'jquery' );
		}
	}

	/**
	 * Adds the settings page as a top-level menu in WordPress admin.
	 */
	public static function add_settings_page() {
		add_menu_page(
			'Chatbot GPT Settings',
			'Configuración Asistente',
			'manage_options',
			'chatbot-gpt-settings',
			array( self::class, 'render_settings_page' ),
			'dashicons-format-chat',
			25
		);

		// Añadir submenú para pruebas del asistente
		add_submenu_page(
			'chatbot-gpt-settings',
			'Probar Asistente',
			'Probar Asistente',
			'manage_options',
			'chatbot-gpt-test',
			array( self::class, 'render_test_page' )
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
		register_setting( 'chatbot_gpt_settings_group', 'chatbot_gpt_system_instructions' );
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
		<?php
	}

	/**
	 * Renders the test page for the assistant.
	 */
	public static function render_test_page() {
		?>
		<div class="wrap">
			<h1>Prueba del Asistente ChatGPT</h1>
			<p>Usa esta sección para probar consultas al asistente configurado.</p>
			
			<div id="chatbot-admin-test">
				<div id="chat-output" class="admin-chat-output"></div>
				<div id="chat-spinner" class="admin-spinner">
					<svg class="spinner" width="50px" height="50px" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
						<circle cx="25" cy="25" r="10" stroke-width="2" stroke-dasharray="31.4 31.4" stroke-linecap="round">
							<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1.5s" repeatCount="indefinite" />
						</circle>
						<circle cx="25" cy="25" r="1.5">
							<animate attributeName="r" values="1.5;3;1.5" dur="2.5s" repeatCount="indefinite"/>
						</circle>
					</svg>
				</div>
				<textarea id="chat-input" rows="4" placeholder="Escribe aquí tu consulta al asistente"></textarea>
				<button id="chat-submit" class="button button-primary">Enviar Consulta</button>
			</div>
			
			<script>
			jQuery(document).ready(function($) {
				let threadId = null;
				const chatInput = document.getElementById("chat-input");
				const chatSubmit = document.getElementById("chat-submit");
				const chatOutput = document.getElementById("chat-output");
				const chatSpinner = document.getElementById("chat-spinner");
				
				function sendChat() {
					let userInput = chatInput.value.trim();
					if (userInput === "") return;
					
					// Añadir mensaje del usuario
					addUserMessage(userInput);
					chatSpinner.style.display = "block";
					
					// Preparar datos para AJAX
					let data = {
						action: 'chatbot_gpt_admin_test',
						query: userInput,
						_ajax_nonce: '<?php echo wp_create_nonce( 'chatbot_gpt_admin_test_nonce' ); ?>'
					};
					
					if (threadId) {
						data.thread_id = threadId;
					}
					
					// Vaciar el input después de enviar
					chatInput.value = "";
					
					// Enviar petición AJAX
					$.post(ajaxurl, data, function(response) {
						chatSpinner.style.display = "none";
						
						if (response.success) {
							addAssistantMessage(response.data.text);
							if (response.data.thread_id) {
								threadId = response.data.thread_id;
							}
						} else {
							addAssistantMessage("<strong>Error:</strong> " + (response.data.message || "No se pudo obtener respuesta"));
						}
					}).fail(function() {
						chatSpinner.style.display = "none";
						addAssistantMessage("<strong>Error:</strong> No se pudo conectar con el servidor");
					});
				}
				
				function addUserMessage(message) {
					let userMessage = document.createElement("div");
					userMessage.classList.add("chat-message", "user");
					userMessage.textContent = message;
					chatOutput.appendChild(userMessage);
					chatOutput.scrollTop = chatOutput.scrollHeight;
				}
				
				function addAssistantMessage(message) {
					let assistantMessage = document.createElement("div");
					assistantMessage.classList.add("chat-message", "assistant");
					assistantMessage.innerHTML = message;
					chatOutput.appendChild(assistantMessage);
					chatOutput.scrollTop = chatOutput.scrollHeight;
				}
				
				// Event Listeners
				chatSubmit.addEventListener("click", sendChat);
				chatInput.addEventListener("keydown", function(e) {
					if (e.key === "Enter" && e.ctrlKey) {
						e.preventDefault();
						sendChat();
					}
				});
			});
			</script>
			
			<style>
			#chatbot-admin-test {
				margin-top: 20px;
				max-width: 800px;
			}
			.admin-chat-output {
				height: 400px;
				overflow-y: auto;
				border: 1px solid #ddd;
				padding: 15px;
				margin-bottom: 15px;
				background-color: #f9f9f9;
			}
			.chat-message {
				margin-bottom: 10px;
				padding: 10px;
				border-radius: 5px;
				max-width: 80%;
			}
			.chat-message.user {
				background-color: #e1f3d8;
				margin-left: auto;
				text-align: right;
			}
			.chat-message.assistant {
				background-color: #f0f0f0;
			}
			#chat-input {
				width: 100%;
				margin-bottom: 10px;
			}
			.admin-spinner {
				display: none;
				text-align: center;
				margin: 10px 0;
			}
			.spinner circle {
				stroke: #0073aa;
			}
			</style>
		</div>
		<?php
	}
}

// Register the settings page.
ChatbotGPTSettings::register();
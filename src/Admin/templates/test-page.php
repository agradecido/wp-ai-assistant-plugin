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
		<table class="widefat striped" style="max-width: 800px; margin-bottom: 20px;">
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
		
		addUserMessage(userInput);
		chatSpinner.style.display = "block";
		
		let data = {
			action: 'chatbot_gpt_admin_test',
			query: userInput,
			_ajax_nonce: '<?php echo wp_create_nonce( 'chatbot_gpt_admin_test_nonce' ); ?>'
		};
		
		if (threadId) {
			data.thread_id = threadId;
		}
		
		chatInput.value = "";
		
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
	position: relative;
}
.admin-chat-output {
	height: 400px;
	overflow-y: auto;
	border: 1px solid #ddd;
	padding: 15px;
	margin-bottom: 15px;
	background-color: #f9f9f9;
	position: relative;
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
	background-color: rgba(255, 255, 255, 0.8);
	padding: 20px;
	border-radius: 5px;
	position: absolute;
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
	z-index: 100;
	box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.spinner circle {
	stroke: #0073aa;
	stroke-width: 3px;
}
</style>
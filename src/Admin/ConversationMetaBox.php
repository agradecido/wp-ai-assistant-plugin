<?php
declare(strict_types=1);

namespace WPAIS\Admin;

/**
 * Adds a meta box to display conversation history in the AI thread post type.
 */
class ConversationMetaBox {

	/**
	 * Register the meta box.
	 */
	public static function register(): void {
		add_action( 'add_meta_boxes', array( self::class, 'add_meta_box' ) );
	}

	/**
	 * Add meta box to the AI thread post type.
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'ai_conversation_history',
			'Historial de Conversación',
			array( self::class, 'render_meta_box' ),
			'ai_chat_thread',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		// Get conversation messages
		$messages = get_post_meta( $post->ID, 'messages', true );

		if ( empty( $messages ) ) {
			echo '<p>No hay mensajes en esta conversación.</p>';
			return;
		}

		echo '<div class="ai-conversation-history">';

		foreach ( $messages as $index => $message ) {
			$role      = $message['role'];
			$content   = $message['content'];
			$timestamp = isset( $message['timestamp'] ) ? date( 'Y-m-d H:i:s', $message['timestamp'] ) : '';

			$role_class = $role === 'user' ? 'user-message' : 'assistant-message';
			$role_label = $role === 'user' ? 'Usuario' : 'Asistente';

			echo '<div class="message ' . esc_attr( $role_class ) . '">';
			echo '<div class="message-header">';
			echo '<strong>' . esc_html( $role_label ) . '</strong>';
			if ( $timestamp ) {
				echo ' <span class="message-time">(' . esc_html( $timestamp ) . ')</span>';
			}
			echo '</div>';
			echo '<div class="message-content">' . wp_kses_post( wpautop( $content ) ) . '</div>';
			echo '</div>';
		}

		echo '</div>';

		// Add some basic styling
		echo '<style>
            .ai-conversation-history {
                max-height: 500px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 10px;
                background: #f9f9f9;
            }
            .message {
                margin-bottom: 15px;
                padding: 10px;
                border-radius: 5px;
            }
            .user-message {
                background-color: #e9f7fe;
                border-left: 4px solid #2271b1;
            }
            .assistant-message {
                background-color: #f1f1f1;
                border-left: 4px solid #46b450;
            }
            .message-header {
                margin-bottom: 5px;
                color: #666;
            }
            .message-time {
                font-size: 0.85em;
                color: #888;
            }
            .message-content p {
                margin: 0 0 10px;
            }
            .message-content p:last-child {
                margin-bottom: 0;
            }
        </style>';
	}
}

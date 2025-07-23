<?php
declare(strict_types=1);

namespace WPAIS\Admin;

use WPAIS\Api\Summarizer;
use WPAIS\Utils\Logger;

class SummaryMetaBox {
	public static function register(): void {
			add_action( 'add_meta_boxes', array( self::class, 'add_meta_box' ) );
			add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	public static function add_meta_box(): void {
			add_meta_box(
				'ai_chat_summary',
				__( 'Chat Summary', 'wp-ai-assistant' ),
				array( self::class, 'render_meta_box' ),
				'ai_chat_thread',
				'side',
				'default'
			);
	}

	public static function enqueue_assets( $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
				return;
		}
			global $post_type;
		if ( 'ai_chat_thread' !== $post_type ) {
				return;
		}
			$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
			$version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0';
			wp_enqueue_script( 'wpai-summary', $plugin_url . 'assets/dist/js/summary.js', array( 'jquery' ), $version, true );
			wp_localize_script(
				'wpai-summary',
				'wpAIGenerateSummary',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_ai_assistant_generate_summary_nonce' ),
					'i18n'    => array(
						'generating' => __( 'Generating...', 'wp-ai-assistant' ),
						'generate'   => __( 'Generate summary', 'wp-ai-assistant' ),
						'regenerate' => __( 'Regenerate summary', 'wp-ai-assistant' ),
						'error'      => __( 'Error generating summary', 'wp-ai-assistant' ),
					),
				)
			);
	}

	public static function render_meta_box( \WP_Post $post ): void {
			$summary = has_excerpt( $post ) ? $post->post_excerpt : '';
			echo '<p id="wpai-summary-text">' . ( $summary ? esc_html( $summary ) : esc_html__( 'No summary yet.', 'wp-ai-assistant' ) ) . '</p>';
			echo '<p><button type="button" class="button" id="wpai-generate-summary" data-post-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Generate summary', 'wp-ai-assistant' ) . '</button></p>';
	}
}

<?php
/**
 * DownloadShortcode
 *
 * @package WPAIS\Frontend
 * @since 1.0
 */

namespace WPAIS\Frontend;

/**
 * Class DownloadShortcode
 *
 * @package WPAIS\Frontend
 */
class DownloadShortcode {

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'wp_ai_assistant_download', array( self::class, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'file' => '',
				'text' => esc_html__( 'Download', 'wp-ai-assistant' ),
			),
			$atts,
			'wp_ai_assistant_download'
		);

		if ( empty( $atts['file'] ) ) {
			return '';
		}

		$file_path = sanitize_text_field( $atts['file'] );
		$nonce = wp_create_nonce( 'wp_ai_assistant_download_nonce' );

		$download_url = add_query_arg(
			array(
				'action' => 'wp_ai_assistant_download',
				'file'   => rawurlencode($file_path),
				'_wpnonce' => $nonce,
			),
			admin_url( 'admin-ajax.php' )
		);

		return '<a href="' . esc_url( $download_url ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}
}

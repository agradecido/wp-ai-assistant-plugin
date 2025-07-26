<?php
/**
 * DownloadController
 *
 * @package WPAIS\Downloads
 * @since 1.0
 */

namespace WPAIS\Downloads;

use WPAIS\Domain\Download\Download;
use WPAIS\Domain\Download\DownloadRepository;

/**
 * Class DownloadController
 *
 * @package WPAIS\Downloads
 */
class DownloadController {

	private DownloadRepository $download_repository;

	/**
	 * DownloadController constructor.
	 *
	 * @param DownloadRepository $download_repository
	 */
	public function __construct( DownloadRepository $download_repository ) {
		$this->download_repository = $download_repository;
	}

	/**
	 * Handle the download request.
	 *
	 * @param string $file_path
	 * @return void
	 */
	public function handle_download( string $file_path ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'wp-ai-assistant' ) );
		}

		$upload_dir = wp_upload_dir();
		$file_path = realpath( $upload_dir['basedir'] . '/' . $file_path );

		if ( strpos( $file_path, realpath( $upload_dir['basedir'] ) ) !== 0 ) {
			wp_die( esc_html__( 'Invalid file path.', 'wp-ai-assistant' ) );
		}

		if ( ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-ai-assistant' ) );
		}

		$this->log_download( $file_path );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		readfile( $file_path );
		exit;
	}

	/**
	 * Log the download.
	 *
	 * @param string $file_path
	 * @return void
	 */
	private function log_download( string $file_path ) {
		$user_id = get_current_user_id();
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		$download = new Download(
			basename( $file_path ),
			$file_path,
			$ip_address,
			$user_id
		);

		$this->download_repository->save( $download );
	}
}

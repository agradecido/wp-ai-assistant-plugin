<?php
namespace WPAIS\PdfDownload;

use WPAIS\PdfDownload\Controller\DownloadController;
use WPAIS\PdfDownload\Repository\DownloadRepository;
use WPAIS\PdfDownload\Service\DownloadService;
use WPAIS\PdfDownload\Admin\StatsPage;
use WPAIS\PdfDownload\CLI\Command;

/**
 * Registers PDF download services.
 */
class ServiceProvider {
	private DownloadController $controller;

	/**
	 * Register hooks and services.
	 */
	public function register(): void {
		global $wpdb;
		$repository       = new DownloadRepository( $wpdb );
		$service          = new DownloadService( $repository );
		$this->controller = new DownloadController( $service );

		( new StatsPage( $repository ) )->register();
		Command::register();

		add_action( 'init', array( $this, 'register_rewrites' ) );
		add_action( 'template_redirect', $this->controller );
	}

	/**
	 * Register rewrite rules.
	 */
	public function register_rewrites(): void {
		add_rewrite_tag( '%download_file%', '([^/]+)' );
		add_rewrite_rule( '^download/([^/]+)/?$', 'index.php?download_file=$matches[1]', 'top' );
	}
}

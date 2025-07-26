<?php
/**
 * WPDBDownloadRepository Implementation
 *
 * @package WPAIS\Infrastructure\Persistence
 * @since 1.0
 */

namespace WPAIS\Infrastructure\Persistence;

use WPAIS\Domain\Download\Download;
use WPAIS\Domain\Download\DownloadRepository;
use wpdb;

/**
 * Class WPDBDownloadRepository
 *
 * @package WPAIS\Infrastructure\Persistence
 */
class WPDBDownloadRepository implements DownloadRepository {

	private wpdb $wpdb;
	private string $table_name;

	/**
	 * WPDBDownloadRepository constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $this->wpdb->prefix . 'ai_assistant_downloads';
	}

	/**
	 * Save a download record.
	 *
	 * @param Download $download
	 * @return void
	 */
	public function save( Download $download ): void {
		$this->wpdb->insert(
			$this->table_name,
			array(
				'file_name'  => $download->get_file_name(),
				'file_path'  => $download->get_file_path(),
				'user_id'    => $download->get_user_id(),
				'ip_address' => $download->get_ip_address(),
			)
		);
	}
}

<?php
/**
 * CreateDownloadsTable Migration
 *
 * @package WPAIS\Infrastructure\Migration
 * @since 1.0
 */

namespace WPAIS\Infrastructure\Migration;

/**
 * Class CreateDownloadsTable
 *
 * Manages the creation of the database table for downloads.
 *
 * @package WPAIS\Infrastructure\Migration
 */
class CreateDownloadsTable {

	/**
	 * Create the downloads table.
	 *
	 * @return void
	 */
	public static function up() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ai_assistant_downloads';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id INT NOT NULL AUTO_INCREMENT,
			file_name VARCHAR(255) NOT NULL,
			file_path VARCHAR(255) NOT NULL,
			user_id BIGINT(20) UNSIGNED,
			ip_address VARCHAR(100) NOT NULL,
			downloaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

<?php
namespace WPAIS\PdfDownload\Database;

/**
 * Database schema management for PDF downloads.
 */
final class Schema {
	/**
	 * Create the downloads table if it does not exist.
	 */
	public static function create_table(): void {
		global $wpdb;
		$table   = "{$wpdb->prefix}pdf_downloads";
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            downloaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            INDEX  (user_id),
            INDEX  (file_name)
        ) $charset;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

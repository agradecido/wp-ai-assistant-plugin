<?php
declare(strict_types=1);

namespace WPAIChatbot\Infrastructure\Migration;

/**
 * Creates the wp_ai_quota table on plugin activation.
 */
final class CreateQuotaTable {

	/**
	 * Creates the wp_ai_quota table.
	 *
	 * @return void
	 */
	public static function up(): void {
		global $wpdb;

		$table   = "{$wpdb->prefix}ai_quota";
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
            session_id CHAR(36) NOT NULL,
            window_start DATE NOT NULL,
            used_messages INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (session_id, window_start)
        ) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

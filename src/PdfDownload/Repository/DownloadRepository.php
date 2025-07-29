<?php
namespace WPAIS\PdfDownload\Repository;

use wpdb;

/**
 * Handles persistence of download records.
 */
class DownloadRepository {
	private string $table;

	public function __construct( private wpdb $db ) {
		$this->table = "{$db->prefix}pdf_downloads";
	}

	/**
	 * Store a new download record.
	 */
	public function record( int $user_id, string $file_name, string $ip ): void {
		$this->db->insert(
			$this->table,
			array(
				'user_id'       => $user_id,
				'file_name'     => $file_name,
				'ip_address'    => $ip,
				'downloaded_at' => current_time( 'mysql', 1 ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Find records by user ID.
	 */
	public function find_by_user( int $user_id ): array {
		$sql = $this->db->prepare(
			$this->db->prepare(
				'SELECT * FROM %s WHERE user_id = %d ORDER BY downloaded_at DESC',
				$this->table,
				$user_id
			),
			$user_id
		);
		return $this->db->get_results( $sql, ARRAY_A ) ?: array();
	}

	/**
	 * Return all download records with pagination.
	 */
	public function all( int $offset, int $limit ): array {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} ORDER BY downloaded_at DESC LIMIT %d, %d",
			$offset,
			$limit
		);
		return $this->db->get_results( $sql, ARRAY_A ) ?: array();
	}

	/**
	 * Count total download records.
	 */
	public function count(): int {
		return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}
}

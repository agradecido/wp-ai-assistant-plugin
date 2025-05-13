<?php
declare(strict_types=1);

namespace WPAIChatbot\Infrastructure\Persistence;

use WPAIChatbot\Domain\Quota\QuotaRepository;
use wpdb;

/**
 * WPDB implementation of the QuotaRepository interface.
 *
 * This class is responsible for persisting and retrieving quota data
 * from the WordPress database using the wpdb class.
 */
final class WPDBQuotaRepository implements QuotaRepository {

	/**
	 * The table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 *
	 * @param wpdb $db The WordPress database object.
	 */
	public function __construct( private wpdb $db ) {
		$this->table = "{$this->db->prefix}ai_quota";
	}

	/**
	 * Returns how many messages this session has used today (UTC).
	 *
	 * @param string $sessionId The session ID.
	 *
	 * @return int The number of messages used today.
	 */
	public function getTodayUsage( string $sessionId ): int {
		$today = gmdate( 'Y-m-d' );

		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT used_messages
                 FROM {$this->table}
                 WHERE session_id = %s AND window_start = %s",
				$sessionId,
				$today
			)
		) ?: 0;
	}

	/**
	 * Increments the message counter for this session today.
	 *
	 * @param string $sessionId The session ID.
	 *
	 * @return void
	 */
	public function increment( string $sessionId ): void {
		$today = gmdate( 'Y-m-d' );

		$this->db->query(
			$this->db->prepare(
				"INSERT INTO {$this->table} (session_id, window_start, used_messages)
                 VALUES (%s, %s, 1)
                 ON DUPLICATE KEY UPDATE used_messages = used_messages + 1",
				$sessionId,
				$today
			)
		);
	}
}

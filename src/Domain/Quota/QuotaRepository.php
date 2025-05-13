<?php
declare(strict_types=1);

namespace WPAIChatbot\Domain\Quota;

/**
 * Persistence contract for quota data.
 */
interface QuotaRepository {

	/**
	 * Returns how many messages this session has used today (UTC).
	 *
	 * @param string $sessionId The session ID.
	 *
	 * @return int The number of messages used today.
	 */
	public function getTodayUsage( string $sessionId ): int;

	/**
	 * Increments the message counter for this session today.
	 *
	 * @param string $sessionId The session ID.
	 *
	 * @return void
	 */
	public function increment( string $sessionId ): void;
}

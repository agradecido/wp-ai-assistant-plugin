<?php
declare(strict_types=1);

namespace WPAIChatbot\Domain\Quota;

use WPAIChatbot\Domain\Quota\QuotaRepository;
use RuntimeException;

/**
 * Business logic: decide if a session can talk to the bot.
 */
class QuotaManager {

	/**
	 * Constructor.
	 *
	 * @param QuotaRepository $repo The repository to use for quota data.
	 * @param int             $dailyLimit The daily limit of messages.
	 */
	public function __construct(
		private QuotaRepository $repo,
		private int $dailyLimit
	) {}

	/**
	 * Throws when the quota is exhausted; otherwise increments usage.
	 *
	 * @param string $sessionId The session ID.
	 *
	 * @throws RuntimeException When the quota is exhausted.
	 */
	public function checkAndIncrement( string $sessionId ): void {
		$used = $this->repo->getTodayUsage( $sessionId );

		if ( $used >= $this->dailyLimit ) {
			throw new RuntimeException(
				__( 'Daily quota exceeded. Come back tomorrow 🤖', 'wp-ai-chatbot' )
			);
		}

		$this->repo->increment( $sessionId );
	}
}

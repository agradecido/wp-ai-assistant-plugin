<?php
declare(strict_types=1);

namespace WPAIS\Domain\Quota;

use WPAIS\Domain\Quota\QuotaRepository;
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
			$message = get_option( 
				'wp_ai_assistant_quota_exceeded_message', 
				'Cuota diaria excedida. Vuelve mañana 🤖'
			);
			throw new RuntimeException( $message );
		}

		$this->repo->increment( $sessionId );
	}
}

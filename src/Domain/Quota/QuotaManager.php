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
		error_log( "QuotaManager Debug: Checking quota for sessionId={$sessionId}, dailyLimit={$this->dailyLimit}" );

		$used = $this->repo->getTodayUsage( $sessionId );

		error_log( "QuotaManager Debug: Used messages today: {$used}" );

		if ( $used >= $this->dailyLimit ) {
				$option  = 'wp_ai_assistant_quota_exceeded_message';
				$default = __( 'Daily quota exceeded. Please try again tomorrow 🤖', 'wp-ai-assistant' );

			if ( function_exists( 'pll_current_language' ) && 'es' === pll_current_language() ) {
						$option  = 'wp_ai_assistant_quota_exceeded_message_es';
						$default = __( 'Has excedido tu cuota diaria de consultas. Por favor vuelve mañana 🤖', 'wp-ai-assistant' );
			}

				$message = get_option( $option, $default );

				throw new RuntimeException( $message );
		}

		$this->repo->increment( $sessionId );
	}
}

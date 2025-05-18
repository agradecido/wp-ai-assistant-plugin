<?php
declare(strict_types=1);

namespace WPAIS\Domain\Thread;

/**
 * Interface for thread storage operations.
 */
interface ThreadRepository {
	/**
	 * Saves a thread to the database.
	 *
	 * @param string      $thread_id The OpenAI thread ID.
	 * @param string      $session_id The session ID of the user.
	 * @param string|null $user_id The WordPress user ID if logged in.
	 * @param string      $title Optional thread title.
	 *
	 * @return int The post ID of the saved thread.
	 */
	public function saveThread( string $thread_id, string $session_id, ?string $user_id = null, string $title = '' ): int;

	/**
	 * Updates a thread with new message content.
	 *
	 * @param string $thread_id The OpenAI thread ID.
	 * @param string $message The latest message content.
	 * @param string $role The role (user or assistant).
	 *
	 * @return bool Whether the update was successful.
	 */
	public function addMessage( string $thread_id, string $message, string $role = 'user' ): bool;

	/**
	 * Gets a thread by its OpenAI thread ID.
	 *
	 * @param string $thread_id The OpenAI thread ID.
	 *
	 * @return array|null The thread data or null if not found.
	 */
	public function getThreadByExternalId( string $thread_id ): ?array;

	/**
	 * Get threads for a specific user or session ID.
	 *
	 * @param string|null $user_id The WordPress user ID.
	 * @param string|null $session_id The session ID.
	 * @param int         $limit Maximum number of threads to return.
	 * @return array List of threads.
	 */
	public function getThreadsByUserOrSession( ?string $user_id = null, ?string $session_id = null, int $limit = 10 ): array;
}

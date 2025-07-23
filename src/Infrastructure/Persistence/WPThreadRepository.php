<?php
declare(strict_types=1);

namespace WPAIS\Infrastructure\Persistence;

use WPAIS\Domain\Thread\ThreadRepository;
use WPAIS\Utils\Logger;
use WPAIS\Api\Summarizer;

/**
 * WordPress implementation of the ThreadRepository interface.
 */
class WPThreadRepository implements ThreadRepository {

	/**
	 * {@inheritdoc}
	 */
	public function saveThread( string $thread_id, string $session_id, ?string $user_id = null, string $title = '' ): int {
		// Generate a title if none provided.
		if ( empty( $title ) ) {
			$title = 'Conversación ' . date( 'Y-m-d H:i:s' );
		}

		// Check if thread already exists.
		$existing = $this->getThreadByExternalId( $thread_id );
		if ( $existing !== null ) {
			return (int) $existing['post_id'];
		}

		// Create post.
		$post_id = wp_insert_post(
			array(
				'post_title'  => sanitize_text_field( $title ),
				'post_status' => 'publish',
				'post_type'   => 'ai_chat_thread',
				'post_author' => $user_id ? (int) $user_id : 1, // Default to admin if no user.
			)
		);

		if ( is_wp_error( $post_id ) ) {
			Logger::error( 'Error creating thread post: ' . $post_id->get_error_message() );
			return 0;
		}

		// Save thread metadata.
		update_post_meta( $post_id, 'thread_external_id', $thread_id );
		update_post_meta( $post_id, 'session_id', $session_id );
		update_post_meta( $post_id, 'messages', array() );

		Logger::log( 'Created new thread post #' . $post_id . ' for thread ' . $thread_id );

		return (int) $post_id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addMessage( string $thread_id, string $message, string $role = 'user' ): bool {
		$thread = $this->getThreadByExternalId( $thread_id );

		if ( $thread === null ) {
			Logger::error( 'Cannot add message: Thread ' . $thread_id . ' not found' );
			return false;
		}

		$post_id  = (int) $thread['post_id'];
		$messages = $thread['messages'] ?? array();

		// Add the new message
		$messages[] = array(
			'role'      => sanitize_text_field( $role ),
			'content'   => $message,
			'timestamp' => time(),
		);

		// Update the post content with the latest complete conversation for search purposes.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $this->generatePostContent( $messages ),
			)
		);

				// Save updated messages.
				update_post_meta( $post_id, 'messages', $messages );

		if ( 'assistant' === $role ) {
				$assistant_count = 0;
			foreach ( $messages as $msg ) {
				if ( 'assistant' === $msg['role'] ) {
					++$assistant_count;
				}
			}
			if ( $assistant_count >= 3 && ! has_excerpt( $post_id ) ) {
							$summary = Summarizer::generate_summary( $messages );
				if ( $summary ) {
						wp_update_post(
							array(
								'ID'           => $post_id,
								'post_excerpt' => sanitize_text_field( $summary ),
							)
						);
				}
			}
		}

				return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getThreadByExternalId( string $thread_id ): ?array {
		$args = array(
			'post_type'      => 'ai_chat_thread',
			'meta_query'     => array(
				array(
					'key'   => 'thread_external_id',
					'value' => $thread_id,
				),
			),
			'posts_per_page' => 1,
		);

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return null;
		}

		$post    = $posts[0];
		$post_id = $post->ID;

		return array(
			'post_id'    => $post_id,
			'title'      => $post->post_title,
			'thread_id'  => $thread_id,
			'session_id' => get_post_meta( $post_id, 'session_id', true ),
			'messages'   => get_post_meta( $post_id, 'messages', true ) ?: array(),
		);
	}

	/**
	 * Get threads for a specific user or session ID.
	 *
	 * @param string|null $user_id The WordPress user ID.
	 * @param string|null $session_id The session ID.
	 * @param int         $limit Maximum number of threads to return.
	 * @return array List of threads.
	 */
	public function getThreadsByUserOrSession( ?string $user_id = null, ?string $session_id = null, int $limit = 10 ): array {
		$args = array(
			'post_type'      => 'ai_chat_thread',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// If user ID is provided and valid
		if ( $user_id && $user_id > 0 ) {
			$args['author'] = $user_id;
		}
		// Otherwise, look for session ID if provided
		elseif ( $session_id ) {
			$args['meta_query'] = array(
				array(
					'key'   => 'session_id',
					'value' => $session_id,
				),
			);
		}

		$posts   = get_posts( $args );
		$threads = array();

		foreach ( $posts as $post ) {
			$summary   = has_excerpt( $post ) ? get_the_excerpt( $post ) : $this->getFirstUserMessage( $post->ID );
			$threads[] = array(
				'post_id'      => $post->ID,
				'title'        => $post->post_title,
				'summary'      => $summary,
				'date'         => get_the_date( 'Y-m-d H:i:s', $post->ID ),
				'thread_id'    => get_post_meta( $post->ID, 'thread_external_id', true ),
				'session_id'   => get_post_meta( $post->ID, 'session_id', true ),
				'messages'     => get_post_meta( $post->ID, 'messages', true ) ?: array(),
				'last_message' => $this->getLastMessages( $post->ID ),
			);
		}

		return $threads;
	}

	/**
	 * Get the last message exchange from a thread.
	 *
	 * @param int $post_id The post ID.
	 * @return array Last user question and assistant response.
	 */
	private function getLastMessages( int $post_id ): array {
			$messages = get_post_meta( $post_id, 'messages', true ) ?: array();
		$result       = array(
			'user_message'      => '',
			'assistant_message' => '',
		);

		// Find the last user message and assistant response
		for ( $i = count( $messages ) - 1; $i >= 0; $i-- ) {
			if ( $messages[ $i ]['role'] === 'user' && empty( $result['user_message'] ) ) {
				$result['user_message'] = $messages[ $i ]['content'];
			} elseif ( $messages[ $i ]['role'] === 'assistant' && empty( $result['assistant_message'] ) ) {
				$result['assistant_message'] = $messages[ $i ]['content'];
			}

			// If we have both, we can stop
			if ( ! empty( $result['user_message'] ) && ! empty( $result['assistant_message'] ) ) {
				break;
			}
		}

			return $result;
	}

	/**
	 * Get the first user message from a thread.
	 *
	 * @param int $post_id The post ID.
	 * @return string The first user message or empty string if none.
	 */
	private function getFirstUserMessage( int $post_id ): string {
		$messages = get_post_meta( $post_id, 'messages', true ) ?: array();

		foreach ( $messages as $message ) {
			if ( isset( $message['role'] ) && 'user' === $message['role'] ) {
				return $message['content'];
			}
		}

		return '';
	}

	/**
	 * Generates readable post content from messages for search functionality.
	 *
	 * @param array $messages The array of message data.
	 * @return string Formatted content.
	 */
	private function generatePostContent( array $messages ): string {
		$content = '';

		foreach ( $messages as $message ) {
			$role     = ucfirst( $message['role'] );
			$content .= "{$role}: {$message['content']}\n\n";
		}

		return $content;
	}
}

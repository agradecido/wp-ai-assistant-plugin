<?php
declare(strict_types=1);

namespace WPAIS\Domain\Thread;

/**
 * Registers and configures the Chat Thread post type.
 */
class ChatThreadPostType {

	/**
	 * Register the custom post type.
	 */
	public static function register(): void {
		register_post_type(
			'ai_chat_thread',
			array(
				'labels'              => array(
					'name'               => __( 'AI Conversations', 'wp-ai-assistant' ),
					'singular_name'      => __( 'AI Conversation', 'wp-ai-assistant' ),
					'menu_name'          => __( 'AI Conversations', 'wp-ai-assistant' ),
					'name_admin_bar'     => __( 'AI Conversation', 'wp-ai-assistant' ),
					'add_new'            => __( 'Add New', 'wp-ai-assistant' ),
					'add_new_item'       => __( 'Add New Conversation', 'wp-ai-assistant' ),
					'new_item'           => __( 'New Conversation', 'wp-ai-assistant' ),
					'edit_item'          => __( 'Edit Conversation', 'wp-ai-assistant' ),
					'view_item'          => __( 'View Conversation', 'wp-ai-assistant' ),
					'all_items'          => __( 'All Conversations', 'wp-ai-assistant' ),
					'search_items'       => __( 'Search Conversations', 'wp-ai-assistant' ),
					'not_found'          => __( 'No conversations found.', 'wp-ai-assistant' ),
					'not_found_in_trash' => __( 'No conversations found in Trash.', 'wp-ai-assistant' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-format-chat',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'supports'            => array( 'title', 'author' ),
				'rewrite'             => array( 'slug' => _x( 'ai-conversations', 'URL slug', 'wp-ai-assistant' ) ),
				'show_in_rest'        => true,
			)
		);

		// Register custom meta fields for REST API
		register_meta(
			'post',
			'thread_external_id',
			array(
				'object_subtype' => 'ai_chat_thread',
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);

		register_meta(
			'post',
			'session_id',
			array(
				'object_subtype' => 'ai_chat_thread',
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);

		register_meta(
			'post',
			'messages',
			array(
				'object_subtype' => 'ai_chat_thread',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'role'      => array(
									'type' => 'string',
								),
								'content'   => array(
									'type' => 'string',
								),
								'timestamp' => array(
									'type' => 'integer',
								),
							),
						),
					),
				),
			)
		);
	}
}

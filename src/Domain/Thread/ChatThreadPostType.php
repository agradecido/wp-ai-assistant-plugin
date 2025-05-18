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
					'name'               => 'Conversaciones AI',
					'singular_name'      => 'Conversación AI',
					'menu_name'          => 'Conversaciones AI',
					'name_admin_bar'     => 'Conversación AI',
					'add_new'            => 'Añadir nueva',
					'add_new_item'       => 'Añadir nueva conversación',
					'new_item'           => 'Nueva conversación',
					'edit_item'          => 'Editar conversación',
					'view_item'          => 'Ver conversación',
					'all_items'          => 'Todas las conversaciones',
					'search_items'       => 'Buscar conversaciones',
					'not_found'          => 'No se encontraron conversaciones',
					'not_found_in_trash' => 'No hay conversaciones en la papelera',
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
				'rewrite'             => array( 'slug' => 'ai-conversaciones' ),
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

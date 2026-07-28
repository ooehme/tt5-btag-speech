<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Speech_Post_Type {
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type(): void {
		register_post_type(
			Speech_Repository::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Bundestagsreden', 'mdb-bundestag-speeches' ),
					'singular_name' => __( 'Bundestagsrede', 'mdb-bundestag-speeches' ),
					'add_new_item'  => __( 'Bundestagsrede hinzufügen', 'mdb-bundestag-speeches' ),
					'edit_item'     => __( 'Bundestagsrede bearbeiten', 'mdb-bundestag-speeches' ),
					'view_item'     => __( 'Bundestagsrede ansehen', 'mdb-bundestag-speeches' ),
					'search_items'  => __( 'Bundestagsreden durchsuchen', 'mdb-bundestag-speeches' ),
					'not_found'     => __( 'Keine Bundestagsreden gefunden.', 'mdb-bundestag-speeches' ),
					'menu_name'     => __( 'Bundestagsreden', 'mdb-bundestag-speeches' ),
				),
				'public'           => true,
				'show_in_rest'     => true,
				'has_archive'      => true,
				'rewrite'          => array( 'slug' => 'bundestagsreden' ),
				'menu_icon'        => 'dashicons-video-alt3',
				'supports'         => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'taxonomies'       => array(),
				'delete_with_user' => false,
			)
		);

		foreach ( $this->metadata() as $key => $type ) {
			$rest_visible = ! in_array(
				$key,
				array(
					'_mdb_download_url',
					'_mdb_attachment_id',
					'_mdb_article_image_id',
					'_mdb_article_image_error',
					'_mdb_sync_status',
					'_mdb_last_seen',
					'_mdb_last_error',
				),
				true
			);
			register_post_meta(
				Speech_Repository::POST_TYPE,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => $rest_visible,
					'sanitize_callback' => 'integer' === $type ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
				)
			);
		}
	}

	/**
	 * @return array<string,string>
	 */
	private function metadata(): array {
		return array(
			'_mdb_video_id'           => 'string',
			'_mdb_source_title'       => 'string',
			'_mdb_source_url'         => 'string',
			'_mdb_embed_url'          => 'string',
			'_mdb_download_url'       => 'string',
			'_mdb_attachment_id'      => 'integer',
			'_mdb_article_url'        => 'string',
			'_mdb_article_title'      => 'string',
			'_mdb_article_image_url'  => 'string',
			'_mdb_article_image_id'   => 'integer',
			'_mdb_article_image_error' => 'string',
			'_mdb_session'            => 'string',
			'_mdb_topic'              => 'string',
			'_mdb_source_date'        => 'string',
			'_mdb_sync_status'        => 'string',
			'_mdb_last_seen'          => 'string',
			'_mdb_last_error'         => 'string',
		);
	}
}

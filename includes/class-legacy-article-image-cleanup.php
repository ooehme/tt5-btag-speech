<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Legacy_Article_Image_Cleanup {
	public const OPTION = 'mdb_speeches_legacy_article_images_cleaned';

	public function register(): void {
		add_action( 'init', array( $this, 'cleanup' ) );
	}

	public function cleanup(): void {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$speech_ids     = $this->speech_ids();
		$attachment_ids = $this->attachment_ids();

		foreach ( $speech_ids as $post_id ) {
			$legacy_attachment_id = (int) get_post_meta( $post_id, '_mdb_article_image_id', true );
			if (
				$legacy_attachment_id > 0
				&& 'attachment' === get_post_type( $legacy_attachment_id )
				&& '' !== (string) get_post_meta( $legacy_attachment_id, '_mdb_article_image_source_url', true )
			) {
				$attachment_ids[] = $legacy_attachment_id;
			}
		}
		$attachment_ids = array_values( array_unique( $attachment_ids ) );

		foreach ( $speech_ids as $post_id ) {
			if ( in_array( (int) get_post_thumbnail_id( $post_id ), $attachment_ids, true ) ) {
				delete_post_thumbnail( $post_id );
			}
			delete_post_meta( $post_id, '_mdb_article_image_id' );
			delete_post_meta( $post_id, '_mdb_article_image_error' );
		}

		$complete = true;
		foreach ( $attachment_ids as $attachment_id ) {
			if ( false === wp_delete_attachment( $attachment_id, true ) ) {
				$complete = false;
			}
		}

		if ( $complete ) {
			update_option( self::OPTION, MDB_SPEECHES_VERSION, false );
		}
	}

	/**
	 * @return array<int,int>
	 */
	private function speech_ids(): array {
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'              => Speech_Repository::POST_TYPE,
					'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft' ),
					'fields'                 => 'ids',
					'posts_per_page'         => -1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			)
		);
	}

	/**
	 * @return array<int,int>
	 */
	private function attachment_ids(): array {
		return array_values(
			array_unique(
				array_map(
					'intval',
					get_posts(
						array(
							'post_type'              => 'attachment',
							'post_status'            => array( 'inherit', 'private', 'publish', 'trash' ),
							'fields'                 => 'ids',
							'posts_per_page'         => -1,
							'no_found_rows'          => true,
							'update_post_meta_cache' => false,
							'update_post_term_cache' => false,
							'meta_key'               => '_mdb_article_image_source_url',
							'meta_compare'           => 'EXISTS',
						)
					)
				)
			)
		);
	}
}

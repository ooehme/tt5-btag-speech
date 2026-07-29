<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Wipe_Service {
	public const PAUSE_OPTION = 'mdb_speeches_wipe_paused';

	/**
	 * @return array{posts:int,attachments:int,category:int,failed:int}
	 */
	public function wipe(): array {
		$post_ids       = $this->speech_ids();
		$attachment_ids = $this->attachment_ids( $post_ids );
		$summary        = array(
			'posts'       => 0,
			'attachments' => 0,
			'category'    => 0,
			'failed'      => 0,
		);

		$download_post_ids = array_unique( array_merge( $post_ids, $this->clear_download_events() ) );
		foreach ( $download_post_ids as $post_id ) {
			delete_option( 'mdb_speeches_download_lock_' . $post_id );
		}

		foreach ( $attachment_ids as $attachment_id ) {
			if ( false === wp_delete_attachment( $attachment_id, true ) ) {
				++$summary['failed'];
			} else {
				++$summary['attachments'];
			}
		}

		foreach ( $post_ids as $post_id ) {
			if ( false === wp_delete_post( $post_id, true ) ) {
				++$summary['failed'];
			} else {
				++$summary['posts'];
			}
		}

		$term = get_term_by( 'slug', 'bundestagsrede', 'category' );
		if ( is_object( $term ) && 0 === (int) ( $term->count ?? 0 ) ) {
			$deleted = wp_delete_term( (int) $term->term_id, 'category' );
			if ( is_wp_error( $deleted ) || false === $deleted ) {
				++$summary['failed'];
			} else {
				$summary['category'] = 1;
			}
		}

		update_option( Settings::OPTION, Settings::defaults(), false );
		update_option( self::PAUSE_OPTION, true, false );
		wp_clear_scheduled_hook( Cron::SYNC_HOOK );
		delete_option( 'mdb_speeches_last_sync' );
		delete_option( 'mdb_speeches_sync_lock' );
		delete_option( Legacy_Article_Image_Cleanup::OPTION );
		delete_site_transient( 'mdb_speeches_github_release' );
		delete_site_transient( 'mdb_speeches_speaker_catalog' );

		return $summary;
	}

	/**
	 * @return array<int,int> Post IDs referenced by current or orphaned download events.
	 */
	private function clear_download_events(): array {
		$argument_sets = array();
		$post_ids      = array();
		$cron_events   = _get_cron_array();
		if ( ! is_array( $cron_events ) ) {
			return $post_ids;
		}

		foreach ( $cron_events as $hooks ) {
			if ( ! is_array( $hooks ) || ! isset( $hooks[ Cron::DOWNLOAD_HOOK ] ) || ! is_array( $hooks[ Cron::DOWNLOAD_HOOK ] ) ) {
				continue;
			}
			foreach ( $hooks[ Cron::DOWNLOAD_HOOK ] as $event ) {
				$args = is_array( $event['args'] ?? null ) ? $event['args'] : array();
				$argument_sets[ md5( serialize( $args ) ) ] = $args;
				if ( isset( $args[0] ) && is_numeric( $args[0] ) && (int) $args[0] > 0 ) {
					$post_ids[] = (int) $args[0];
				}
			}
		}

		foreach ( $argument_sets as $args ) {
			wp_clear_scheduled_hook( Cron::DOWNLOAD_HOOK, $args );
		}

		return array_values( array_unique( $post_ids ) );
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
	 * @param array<int,int> $post_ids Speech post IDs.
	 * @return array<int,int>
	 */
	private function attachment_ids( array $post_ids ): array {
		$ids = get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => array( 'inherit', 'private', 'publish', 'trash' ),
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => '_mdb_speech_id',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_mdb_article_image_source_url',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_mdb_video_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( array() !== $post_ids ) {
			$ids = array_merge(
				$ids,
				get_posts(
					array(
						'post_type'              => 'attachment',
						'post_status'            => array( 'inherit', 'private', 'publish', 'trash' ),
						'fields'                 => 'ids',
						'posts_per_page'         => -1,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'post_parent__in'        => $post_ids,
					)
				)
			);
		}

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
}

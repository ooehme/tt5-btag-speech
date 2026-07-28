<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use DateTimeImmutable;
use WP_Error;
use WP_Query;

final class Speech_Repository {
	public const POST_TYPE = 'mdb_speech';

	/**
	 * @var array<string,string>
	 */
	private const META_MAP = array(
		'title'             => '_mdb_source_title',
		'source_url'        => '_mdb_source_url',
		'embed_url'         => '_mdb_embed_url',
		'download_url'      => '_mdb_download_url',
		'article_url'       => '_mdb_article_url',
		'article_title'     => '_mdb_article_title',
		'article_image_url' => '_mdb_article_image_url',
		'session'           => '_mdb_session',
		'topic'             => '_mdb_topic',
		'date'              => '_mdb_source_date',
	);

	public function find_by_video_id( string $video_id ): int {
		if ( ! preg_match( '/^\d+$/', $video_id ) ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_mdb_video_id',
						'value'   => $video_id,
						'compare' => '=',
					),
				),
			)
		);
		return isset( $query->posts[0] ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * @param array<string,string> $speech Normalized source record.
	 * @return int|WP_Error
	 */
	public function upsert( array $speech, string $download_mode ): int|WP_Error {
		$video_id = isset( $speech['video_id'] ) ? (string) $speech['video_id'] : '';
		if ( ! preg_match( '/^\d+$/', $video_id ) ) {
			return new WP_Error( 'mdb_invalid_video_id', __( 'Die Rede enthält keine gültige Video-ID.', 'mdb-bundestag-speeches' ) );
		}

		$post_id = $this->find_by_video_id( $video_id );
		if ( 0 === $post_id ) {
			$postarr = array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $speech['title'] ?? sprintf( __( 'Bundestagsrede %s', 'mdb-bundestag-speeches' ), $video_id ) ),
			);
			$date    = $this->post_date( (string) ( $speech['date'] ?? '' ) );
			if ( null !== $date ) {
				$postarr['post_date']     = $date;
				$postarr['post_date_gmt'] = get_gmt_from_date( $date );
			}

			$inserted = wp_insert_post( wp_slash( $postarr ), true );
			if ( is_wp_error( $inserted ) ) {
				return $inserted;
			}
			$post_id = (int) $inserted;
			update_post_meta( $post_id, '_mdb_video_id', $video_id );
		}

		foreach ( self::META_MAP as $field => $meta_key ) {
			$value = in_array(
				$field,
				array( 'source_url', 'embed_url', 'download_url', 'article_url', 'article_image_url' ),
				true
			)
				? esc_url_raw( (string) ( $speech[ $field ] ?? '' ) )
				: sanitize_text_field( (string) ( $speech[ $field ] ?? '' ) );
			update_post_meta( $post_id, $meta_key, $value );
		}

		$attachment_id = (int) get_post_meta( $post_id, '_mdb_attachment_id', true );
		$previous      = (string) get_post_meta( $post_id, '_mdb_sync_status', true );
		update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::after_seen( $download_mode, $attachment_id, $previous ) );
		update_post_meta( $post_id, '_mdb_last_seen', current_time( 'mysql', true ) );
		if ( Sync_Status::DOWNLOAD_FAILED !== $previous ) {
			delete_post_meta( $post_id, '_mdb_last_error' );
		}
		return $post_id;
	}

	public function mark_all_not_seen(): void {
		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $query->posts as $post_id ) {
			update_post_meta( (int) $post_id, '_mdb_sync_status', Sync_Status::NOT_SEEN );
		}
	}

	public function mark_error( string $video_id, string $message ): void {
		$post_id = $this->find_by_video_id( $video_id );
		if ( $post_id > 0 ) {
			update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::SYNC_ERROR );
			update_post_meta( $post_id, '_mdb_last_error', sanitize_text_field( $message ) );
		}
	}

	/**
	 * @param array<int,string> $statuses Status filter.
	 * @return array<int,int>
	 */
	public function ids_by_status( array $statuses ): array {
		$statuses = array_values( array_intersect( Sync_Status::all(), $statuses ) );
		if ( array() === $statuses ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_mdb_sync_status',
						'value'   => $statuses,
						'compare' => 'IN',
					),
				),
			)
		);
		return array_map( 'intval', $query->posts );
	}

	/**
	 * @return array<int,\WP_Post>
	 */
	public function recent( int $limit = 50 ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => max( 1, min( 100, $limit ) ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		return $query->posts;
	}

	/**
	 * @param array<int,array<string,mixed>> $records Records to deduplicate.
	 * @return array<int,array<string,mixed>>
	 */
	public static function unique_by_video_id( array $records ): array {
		$unique = array();
		foreach ( $records as $record ) {
			$video_id = isset( $record['video_id'] ) ? (string) $record['video_id'] : '';
			if ( preg_match( '/^\d+$/', $video_id ) && ! isset( $unique[ $video_id ] ) ) {
				$unique[ $video_id ] = $record;
			}
		}
		return array_values( $unique );
	}

	private function post_date( string $date ): ?string {
		$parsed = DateTimeImmutable::createFromFormat( '!d.m.Y', $date, wp_timezone() );
		if ( false === $parsed ) {
			return null;
		}
		return $parsed->setTime( 12, 0 )->format( 'Y-m-d H:i:s' );
	}
}

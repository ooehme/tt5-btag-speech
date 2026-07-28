<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Synchronizer {
	public function __construct(
		private Settings $settings,
		private Source_Client $source,
		private Speech_Repository $repository,
		private Sync_Lock $lock
	) {}

	/**
	 * @return array{created:int,updated:int,errors:int,queued:int,total:int}|WP_Error
	 */
	public function sync(): array|WP_Error {
		$token = $this->lock->acquire();
		if ( false === $token ) {
			return new WP_Error( 'mdb_sync_locked', __( 'Eine Synchronisierung läuft bereits.', 'mdb-bundestag-speeches' ) );
		}

		$summary = array(
			'created' => 0,
			'updated' => 0,
			'errors'  => 0,
			'queued'  => 0,
			'total'   => 0,
		);
		$error_messages  = array();

		try {
			$speaker_id     = (string) $this->settings->get( 'speaker_id' );
			$speaker_filter = (string) $this->settings->get( 'speaker_filter' );
			$mode           = (string) $this->settings->get( 'download_mode' );
			$quality        = (string) $this->settings->get( 'quality' );
			$list           = $this->source->speeches( $speaker_id, $speaker_filter );
			if ( is_wp_error( $list ) ) {
				$this->log_error( $list );
				$this->remember_result( $summary, array( $list->get_error_message() ) );
				return $list;
			}

			$list             = Speech_Repository::unique_by_video_id( $list );
			$summary['total'] = count( $list );
			$this->repository->mark_all_not_seen();

			foreach ( $list as $list_record ) {
				$video_id = (string) $list_record['video_id'];
				$existing = $this->repository->find_by_video_id( $video_id );
				$details  = $this->source->speech( $video_id, $quality );
				if ( is_wp_error( $details ) ) {
					++$summary['errors'];
					$error_messages[] = $video_id . ': ' . $details->get_error_message();
					$this->repository->mark_error( $video_id, $details->get_error_message() );
					$this->log_error( $details, $video_id );
					continue;
				}

				$record = array_merge( $list_record, array_filter( $details, static fn ( mixed $value ): bool => '' !== $value ) );
				$result = $this->repository->upsert( $record, $mode );
				if ( is_wp_error( $result ) ) {
					++$summary['errors'];
					$error_messages[] = $video_id . ': ' . $result->get_error_message();
					$this->log_error( $result, $video_id );
					continue;
				}

				if ( $existing > 0 ) {
					++$summary['updated'];
				} else {
					++$summary['created'];
				}

				if ( 'automatic' === $mode && Sync_Status::DOWNLOAD_PENDING === get_post_meta( $result, '_mdb_sync_status', true ) ) {
					$this->queue_download( $result );
					++$summary['queued'];
				}
			}

			$this->remember_result( $summary, $error_messages );
			return $summary;
		} finally {
			$this->lock->release( $token );
		}
	}

	/**
	 * @param array<string,int> $summary Summary counters.
	 * @param array<int,string> $errors Error messages.
	 */
	private function remember_result( array $summary, array $errors ): void {
		update_option(
			'mdb_speeches_last_sync',
			array(
				'time'    => current_time( 'mysql', true ),
				'summary' => $summary,
				'errors'  => array_slice( array_map( 'sanitize_text_field', $errors ), 0, 10 ),
			),
			false
		);
	}

	private function queue_download( int $post_id ): void {
		if ( false === wp_next_scheduled( 'mdb_speeches_download_one', array( $post_id ) ) ) {
			wp_schedule_single_event( time() + 5, 'mdb_speeches_download_one', array( $post_id ) );
		}
	}

	private function log_error( WP_Error $error, string $video_id = '' ): void {
		$context = '' !== $video_id ? ' (Video ' . $video_id . ')' : '';
		$message = preg_replace( '/[\r\n]+/', ' ', $error->get_error_message() ) ?? '';
		error_log( 'MDB Bundestagsreden' . $context . ': ' . $message );
	}
}

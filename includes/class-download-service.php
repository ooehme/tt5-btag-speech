<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Download_Service {
	public function __construct(
		private MP4_Validator $validator,
		private Speech_Repository $repository,
		private Download_Lock $lock,
		private Settings $settings,
		private URL_Resolver $urls,
		private Subtitle_Service $subtitles
	) {}

	/**
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function download( int $post_id ): int|WP_Error {
		if ( Speech_Repository::POST_TYPE !== get_post_type( $post_id ) ) {
			return new WP_Error( 'mdb_invalid_speech', __( 'Die angegebene Bundestagsrede existiert nicht.', 'mdb-bundestag-speeches' ) );
		}

		$token = $this->lock->acquire( $post_id );
		if ( false === $token ) {
			return new WP_Error( 'mdb_download_locked', __( 'Diese Rede wird bereits heruntergeladen.', 'mdb-bundestag-speeches' ) );
		}

		try {
			return $this->download_unlocked( $post_id );
		} finally {
			$this->lock->release( $post_id, $token );
		}
	}

	/**
	 * @return int|WP_Error Attachment ID or error.
	 */
	private function download_unlocked( int $post_id ): int|WP_Error {
		$video_id = (string) get_post_meta( $post_id, '_mdb_video_id', true );
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_attachment_id', true );
		if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
			$this->download_subtitle( $post_id, $video_id );
			update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::DOWNLOADED );
			return $attachment_id;
		}

		$resolved = $this->resolve_download( $post_id, $video_id );
		if ( is_wp_error( $resolved ) ) {
			return $this->failure( $post_id, $resolved );
		}
		$url = $resolved['url'];

		$this->load_media_dependencies();
		$tmp = download_url( $url, 300 );
		if ( is_wp_error( $tmp ) ) {
			return $this->failure( $post_id, $tmp );
		}

		$max_bytes = $this->validator->max_bytes();
		$file_size = is_file( $tmp ) ? (int) filesize( $tmp ) : 0;
		if ( $file_size <= 0 || $file_size > $max_bytes ) {
			wp_delete_file( $tmp );
			return $this->failure( $post_id, new WP_Error( 'mdb_download_size', __( 'Die heruntergeladene Datei ist leer oder überschreitet das Größenlimit.', 'mdb-bundestag-speeches' ) ) );
		}

		$filename = sanitize_file_name( 'bundestagsrede-' . $video_id . '.mp4' );
		$checked  = wp_check_filetype_and_ext( $tmp, $filename, array( 'mp4' => 'video/mp4' ) );
		if ( 'video/mp4' !== (string) ( $checked['type'] ?? '' ) ) {
			wp_delete_file( $tmp );
			return $this->failure( $post_id, new WP_Error( 'mdb_download_mime', __( 'Die heruntergeladene Datei ist keine gültige MP4-Datei.', 'mdb-bundestag-speeches' ) ) );
		}

		$file     = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);
		$result   = media_handle_sideload( $file, $post_id, (string) get_the_title( $post_id ) );
		if ( is_wp_error( $result ) ) {
			wp_delete_file( $tmp );
			return $this->failure( $post_id, $result );
		}

		$attachment_id = (int) $result;
		update_post_meta( $attachment_id, '_mdb_speech_id', $post_id );
		update_post_meta( $attachment_id, '_mdb_video_id', $video_id );
		update_post_meta( $post_id, '_mdb_attachment_id', $attachment_id );
		update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::DOWNLOADED );
		delete_post_meta( $post_id, '_mdb_last_error' );
		$this->download_subtitle( $post_id, $video_id );
		return $attachment_id;
	}

	/**
	 * @return array{url:string,size:int,mime:string}|WP_Error
	 */
	private function resolve_download( int $post_id, string $video_id ): array|WP_Error {
		$stored_url = (string) get_post_meta( $post_id, '_mdb_download_url', true );
		try {
			$candidates = $this->urls->download_urls( $video_id, (string) $this->settings->get( 'quality' ) );
		} catch ( \InvalidArgumentException $exception ) {
			return new WP_Error( 'mdb_invalid_quality', __( 'Die konfigurierte Videoqualität ist ungültig.', 'mdb-bundestag-speeches' ) );
		}

		if ( '' !== $stored_url ) {
			$candidates[] = $stored_url;
		}
		$candidates = array_values( array_unique( $candidates ) );
		$last_error = new WP_Error( 'mdb_mp4_missing', __( 'Für dieses Video wurde keine erreichbare MP4-Variante gefunden.', 'mdb-bundestag-speeches' ) );

		foreach ( $candidates as $url ) {
			$validated = $this->validator->validate( $url );
			if ( is_wp_error( $validated ) ) {
				$last_error = $validated;
				continue;
			}

			update_post_meta( $post_id, '_mdb_download_url', $url );
			return array(
				'url'  => $url,
				'size' => $validated['size'],
				'mime' => $validated['mime'],
			);
		}

		return $last_error;
	}

	private function download_subtitle( int $post_id, string $video_id ): void {
		$result = $this->subtitles->download( $post_id, $video_id );
		if ( is_wp_error( $result ) ) {
			error_log( 'MDB Bundestagsreden (Untertitel ' . $post_id . '): ' . $result->get_error_message() );
		}
	}

	/**
	 * @return array{queued:int}
	 */
	public function queue_failed(): array {
		return $this->queue( array( Sync_Status::DOWNLOAD_FAILED ) );
	}

	/**
	 * @return array{queued:int}
	 */
	public function queue_available(): array {
		return $this->queue(
			array(
				Sync_Status::DOWNLOAD_AVAILABLE,
				Sync_Status::DOWNLOAD_PENDING,
				Sync_Status::DOWNLOAD_FAILED,
				Sync_Status::DOWNLOADED,
			)
		);
	}

	/**
	 * @param array<int,string> $statuses Downloadable statuses.
	 * @return array{queued:int}
	 */
	private function queue( array $statuses ): array {
		$ids = $this->repository->ids_by_status( $statuses );
		foreach ( $ids as $post_id ) {
			update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::DOWNLOAD_PENDING );
			if ( false === wp_next_scheduled( 'mdb_speeches_download_one', array( $post_id ) ) ) {
				wp_schedule_single_event( time() + 5, 'mdb_speeches_download_one', array( $post_id ) );
			}
		}
		return array( 'queued' => count( $ids ) );
	}

	private function failure( int $post_id, WP_Error $error ): WP_Error {
		update_post_meta( $post_id, '_mdb_sync_status', Sync_Status::DOWNLOAD_FAILED );
		update_post_meta( $post_id, '_mdb_last_error', sanitize_text_field( $error->get_error_message() ) );
		error_log( 'MDB Bundestagsreden (Download ' . $post_id . '): ' . $error->get_error_message() );
		return $error;
	}

	private function load_media_dependencies(): void {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}
}

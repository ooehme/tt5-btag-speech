<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Subtitle_Service {
	private const MAX_BYTES = 2_000_000;

	public function __construct( private URL_Resolver $urls ) {}

	public function register(): void {
		add_filter( 'upload_mimes', array( $this, 'mime_types' ) );
	}

	/**
	 * @param array<string,string> $mimes Allowed upload MIME types.
	 * @return array<string,string>
	 */
	public function mime_types( array $mimes ): array {
		$mimes['vtt'] = 'text/vtt';
		return $mimes;
	}

	/**
	 * Downloads the Bundestag SRT file and stores a browser-compatible WebVTT attachment.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function download( int $post_id, string $video_id ): int|WP_Error {
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_subtitle_attachment_id', true );
		if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
			return $attachment_id;
		}

		try {
			$url = $this->urls->subtitle_url( $video_id );
		} catch ( \InvalidArgumentException $exception ) {
			return $this->failure( $post_id, new WP_Error( 'mdb_subtitle_video_id', __( 'Die Video-ID für den Untertitel ist ungültig.', 'mdb-bundestag-speeches' ) ) );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 20,
				'redirection'         => 0,
				'limit_response_size' => self::MAX_BYTES,
				'headers'             => array(
					'Accept'     => 'application/x-subrip,text/plain,application/octet-stream',
					'User-Agent' => 'MDB Bundestagsreden/' . MDB_SPEECHES_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		update_post_meta( $post_id, '_mdb_subtitle_checked_at', current_time( 'mysql', true ) );

		if ( is_wp_error( $response ) ) {
			return $this->failure(
				$post_id,
				new WP_Error( 'mdb_subtitle_unreachable', sprintf( __( 'Der Untertitel konnte nicht geladen werden: %s', 'mdb-bundestag-speeches' ), $response->get_error_message() ) )
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return $this->failure(
				$post_id,
				new WP_Error( 'mdb_subtitle_status', sprintf( __( 'Der Untertitel-Download endete mit HTTP-Status %d.', 'mdb-bundestag-speeches' ), $status ) )
			);
		}

		$srt = wp_remote_retrieve_body( $response );
		if ( '' === trim( $srt ) || strlen( $srt ) >= self::MAX_BYTES ) {
			return $this->failure( $post_id, new WP_Error( 'mdb_subtitle_size', __( 'Die Untertiteldatei ist leer oder zu groß.', 'mdb-bundestag-speeches' ) ) );
		}

		$vtt = $this->to_webvtt( $srt );
		if ( is_wp_error( $vtt ) ) {
			return $this->failure( $post_id, $vtt );
		}

		$filename = sanitize_file_name( 'bundestagsrede-' . $video_id . '-untertitel.vtt' );
		$tmp      = wp_tempnam( $filename );
		if ( false === $tmp || false === file_put_contents( $tmp, $vtt ) ) {
			if ( is_string( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return $this->failure( $post_id, new WP_Error( 'mdb_subtitle_temp', __( 'Die Untertiteldatei konnte nicht temporär gespeichert werden.', 'mdb-bundestag-speeches' ) ) );
		}

		$this->load_media_dependencies();
		$result = media_handle_sideload(
			array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			),
			$post_id,
			sprintf( __( 'Untertitel zur Bundestagsrede %s', 'mdb-bundestag-speeches' ), $video_id )
		);
		if ( is_wp_error( $result ) ) {
			wp_delete_file( $tmp );
			return $this->failure( $post_id, $result );
		}

		$attachment_id = (int) $result;
		update_post_meta( $attachment_id, '_mdb_speech_id', $post_id );
		update_post_meta( $attachment_id, '_mdb_video_id', $video_id );
		update_post_meta( $attachment_id, '_mdb_subtitle_source_url', $url );
		update_post_meta( $post_id, '_mdb_subtitle_attachment_id', $attachment_id );
		update_post_meta( $post_id, '_mdb_subtitle_source_url', $url );
		delete_post_meta( $post_id, '_mdb_subtitle_error' );

		return $attachment_id;
	}

	/**
	 * @return string|WP_Error
	 */
	public function to_webvtt( string $srt ): string|WP_Error {
		$srt = preg_replace( '/^\xEF\xBB\xBF/', '', $srt ) ?? $srt;
		$srt = str_replace( array( "\r\n", "\r" ), "\n", $srt );
		$count = 0;
		$body  = preg_replace_callback(
			'/^(\d{2,}:\d{2}:\d{2}),(\d{3})(\s+-->\s+)(\d{2,}:\d{2}:\d{2}),(\d{3})(.*)$/m',
			static function ( array $matches ) use ( &$count ): string {
				++$count;
				return $matches[1] . '.' . $matches[2] . $matches[3] . $matches[4] . '.' . $matches[5] . $matches[6];
			},
			$srt
		);
		if ( null === $body || 0 === $count ) {
			return new WP_Error( 'mdb_subtitle_format', __( 'Die SRT-Datei enthält keine gültigen Zeitmarken.', 'mdb-bundestag-speeches' ) );
		}

		return "WEBVTT\n\n" . trim( $body ) . "\n";
	}

	private function failure( int $post_id, WP_Error $error ): WP_Error {
		update_post_meta( $post_id, '_mdb_subtitle_error', sanitize_text_field( $error->get_error_message() ) );
		return $error;
	}

	private function load_media_dependencies(): void {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}
}

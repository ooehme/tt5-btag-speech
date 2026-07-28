<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class MP4_Validator {
	public function __construct(
		private Settings $settings,
		private URL_Resolver $urls
	) {}

	/**
	 * @return array{size:int,mime:string}|WP_Error
	 */
	public function validate( string $url ): array|WP_Error {
		if ( ! $this->urls->is_allowed_url( $url ) ) {
			return new WP_Error( 'mdb_disallowed_download', __( 'Die MP4-URL verwendet keinen erlaubten CDN-Host.', 'mdb-bundestag-speeches' ) );
		}

		$args = array(
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => array(
				'Accept'     => 'video/mp4',
				'User-Agent' => 'MDB Bundestagsreden/' . MDB_SPEECHES_VERSION . '; ' . home_url( '/' ),
			),
		);
		$response = wp_safe_remote_head( $url, $args );
		$status   = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || in_array( $status, array( 403, 405, 501 ), true ) ) {
			$args['headers']['Range']    = 'bytes=0-0';
			$args['limit_response_size'] = 1;
			$response                   = wp_safe_remote_get( $url, $args );
			$status                     = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'mdb_mp4_unreachable', sprintf( __( 'Die MP4-Datei konnte nicht geprüft werden: %s', 'mdb-bundestag-speeches' ), $response->get_error_message() ) );
		}
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error( 'mdb_mp4_status', sprintf( __( 'Die MP4-Prüfung endete mit HTTP-Status %d.', 'mdb-bundestag-speeches' ), $status ) );
		}

		$mime = strtolower( trim( explode( ';', (string) wp_remote_retrieve_header( $response, 'content-type' ) )[0] ) );
		if ( 'video/mp4' !== $mime ) {
			return new WP_Error( 'mdb_mp4_mime', __( 'Die Download-URL liefert nicht den MIME-Typ video/mp4.', 'mdb-bundestag-speeches' ) );
		}

		$size          = (int) wp_remote_retrieve_header( $response, 'content-length' );
		$content_range = (string) wp_remote_retrieve_header( $response, 'content-range' );
		if ( preg_match( '/\/(\d+)\s*$/', $content_range, $matches ) ) {
			$size = (int) $matches[1];
		}
		if ( $size <= 0 ) {
			return new WP_Error( 'mdb_mp4_length', __( 'Die MP4-Antwort enthält keine verwertbare Dateigröße.', 'mdb-bundestag-speeches' ) );
		}
		if ( $size > $this->max_bytes() ) {
			return new WP_Error( 'mdb_mp4_too_large', __( 'Die MP4-Datei überschreitet das konfigurierte Größenlimit.', 'mdb-bundestag-speeches' ) );
		}

		return array(
			'size' => $size,
			'mime' => $mime,
		);
	}

	public function max_bytes(): int {
		$megabytes = max( 1, (int) $this->settings->get( 'max_file_size' ) );
		return $megabytes * 1024 * 1024;
	}
}

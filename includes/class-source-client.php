<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Source_Client {
	private const MAX_HTML_BYTES = 5_000_000;

	public function __construct(
		private URL_Resolver $urls,
		private List_Parser $list_parser,
		private Video_Parser $video_parser
	) {}

	/**
	 * @return array<int,array<string,string>>|WP_Error
	 */
	public function speeches( string $speaker_id, string $speaker_filter = '' ): array|WP_Error {
		try {
			$url = $this->urls->list_url( $speaker_id, $speaker_filter );
		} catch ( \InvalidArgumentException $exception ) {
			return new WP_Error( 'mdb_invalid_speaker', __( 'Die konfigurierte Redner-ID oder der Redenlisten-Filter ist ungültig.', 'mdb-bundestag-speeches' ) );
		}

		$html = $this->get_html( $url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		try {
			return $this->list_parser->parse( $html );
		} catch ( Parser_Exception $exception ) {
			return new WP_Error( 'mdb_list_parser', $exception->getMessage() );
		}
	}

	/**
	 * @return array<string,string>|WP_Error
	 */
	public function speech( string $video_id, string $quality = '1080p_8000' ): array|WP_Error {
		try {
			$url  = $this->urls->video_url( $video_id );
			$html = $this->get_html( $url );
		} catch ( \InvalidArgumentException $exception ) {
			return new WP_Error( 'mdb_invalid_video', __( 'Die Video-ID ist ungültig.', 'mdb-bundestag-speeches' ) );
		}

		if ( is_wp_error( $html ) ) {
			return $html;
		}

		try {
			$metadata = $this->video_parser->parse( $html );
		} catch ( Parser_Exception $exception ) {
			return new WP_Error( 'mdb_video_parser', $exception->getMessage() );
		}
		if ( '' !== (string) ( $metadata['video_id'] ?? '' ) && $video_id !== (string) $metadata['video_id'] ) {
			return new WP_Error( 'mdb_video_mismatch', __( 'Die Bundestag-Videoseite gehört nicht zur angeforderten Video-ID.', 'mdb-bundestag-speeches' ) );
		}

		$metadata['video_id']     = $video_id;
		$metadata['source_url']   = $this->urls->video_url( $video_id );
		$metadata['embed_url']    = $this->urls->embed_url( $video_id );
		try {
			$metadata['download_url'] = $this->urls->download_url( $video_id, $quality );
		} catch ( \InvalidArgumentException $exception ) {
			return new WP_Error( 'mdb_invalid_quality', __( 'Die konfigurierte Videoqualität ist ungültig.', 'mdb-bundestag-speeches' ) );
		}
		return $metadata;
	}

	private function get_html( string $url ): string|WP_Error {
		if ( ! $this->urls->is_allowed_url( $url ) ) {
			return new WP_Error( 'mdb_disallowed_host', __( 'Die Quell-URL verwendet keinen erlaubten Bundestag-Host.', 'mdb-bundestag-speeches' ) );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 20,
				'redirection'         => 3,
				'limit_response_size' => self::MAX_HTML_BYTES,
				'headers'             => array(
					'Accept'     => 'text/html,application/xhtml+xml',
					'User-Agent' => 'MDB Bundestagsreden/' . MDB_SPEECHES_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'mdb_http_error', sprintf( __( 'Bundestag-Anfrage fehlgeschlagen: %s', 'mdb-bundestag-speeches' ), $response->get_error_message() ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error( 'mdb_http_status', sprintf( __( 'Der Bundestag antwortete mit HTTP-Status %d.', 'mdb-bundestag-speeches' ), $status ) );
		}

		$content_type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
		if ( '' !== $content_type && ! str_contains( $content_type, 'text/html' ) ) {
			return new WP_Error( 'mdb_unexpected_content', __( 'Der Bundestag lieferte kein HTML.', 'mdb-bundestag-speeches' ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === trim( $body ) ) {
			return new WP_Error( 'mdb_empty_response', __( 'Der Bundestag lieferte eine leere Antwort.', 'mdb-bundestag-speeches' ) );
		}
		return $body;
	}
}

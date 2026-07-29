<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Article_Image_Importer {
	private const MAX_BYTES = 15_000_000;

	public function __construct( private URL_Resolver $urls ) {}

	/**
	 * @return int|WP_Error Attachment ID, zero when no image is available, or error.
	 */
	public function import( int $post_id ): int|WP_Error {
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_article_image_id', true );
		$url           = (string) get_post_meta( $post_id, '_mdb_article_image_url', true );
		if ( $attachment_id > 0 ) {
			$attachment_source = (string) get_post_meta( $attachment_id, '_mdb_article_image_source_url', true );
			if (
				'' !== $url
				&& esc_url_raw( $url ) === $attachment_source
				&& 'attachment' === get_post_type( $attachment_id )
			) {
				return $attachment_id;
			}
			delete_post_meta( $post_id, '_mdb_article_image_id' );
			if ( $attachment_id === get_post_thumbnail_id( $post_id ) ) {
				delete_post_thumbnail( $post_id );
			}
		}

		if ( '' === $url ) {
			return 0;
		}
		if ( ! $this->urls->is_allowed_url( $url ) ) {
			return new WP_Error(
				'mdb_article_image_host',
				__( 'Das Artikelbild verwendet keinen erlaubten Bundestag-Host.', 'mdb-bundestag-speeches' )
			);
		}

		$shared_attachment_id = $this->find_existing( $url );
		if ( $shared_attachment_id > 0 ) {
			$this->connect( $post_id, $shared_attachment_id );
			return $shared_attachment_id;
		}

		$this->load_media_dependencies();
		$tmp = wp_tempnam( $url );
		if ( ! is_string( $tmp ) || '' === $tmp ) {
			return new WP_Error(
				'mdb_article_image_temp',
				__( 'Für das Artikelbild konnte keine temporäre Datei angelegt werden.', 'mdb-bundestag-speeches' )
			);
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 30,
				'redirection'         => 3,
				'stream'              => true,
				'filename'            => $tmp,
				'limit_response_size' => self::MAX_BYTES + 1,
				'headers'             => array(
					'Accept'     => 'image/jpeg,image/png,image/webp',
					'User-Agent' => 'MDB Bundestagsreden/' . MDB_SPEECHES_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			wp_delete_file( $tmp );
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			wp_delete_file( $tmp );
			return new WP_Error(
				'mdb_article_image_http',
				sprintf( __( 'Das Artikelbild antwortete mit HTTP-Status %d.', 'mdb-bundestag-speeches' ), $status )
			);
		}

		$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
		$header_mime  = strtolower( trim( explode( ';', $content_type )[0] ) );
		if ( 'image/jpg' === $header_mime ) {
			$header_mime = 'image/jpeg';
		}
		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);
		if ( '' !== $header_mime && ! in_array( $header_mime, $allowed_mimes, true ) ) {
			wp_delete_file( $tmp );
			return new WP_Error(
				'mdb_article_image_mime',
				__( 'Die Bundestag-Datei ist kein unterstütztes Artikelbild.', 'mdb-bundestag-speeches' )
			);
		}

		$file_size = is_file( $tmp ) ? (int) filesize( $tmp ) : 0;
		if ( $file_size <= 0 || $file_size > self::MAX_BYTES ) {
			wp_delete_file( $tmp );
			return new WP_Error(
				'mdb_article_image_size',
				__( 'Das Artikelbild ist leer oder überschreitet 15 MB.', 'mdb-bundestag-speeches' )
			);
		}

		$path     = (string) ( wp_parse_url( $url )['path'] ?? '' );
		$filename = sanitize_file_name( basename( $path ) );
		if ( '' === $filename ) {
			$filename = 'bundestag-artikelbild-' . $post_id . '.jpg';
		}
		$checked = wp_check_filetype_and_ext( $tmp, $filename, $allowed_mimes );
		if ( ! in_array( (string) ( $checked['type'] ?? '' ), $allowed_mimes, true ) ) {
			wp_delete_file( $tmp );
			return new WP_Error(
				'mdb_article_image_invalid',
				__( 'Das heruntergeladene Artikelbild ist ungültig.', 'mdb-bundestag-speeches' )
			);
		}

		$result = media_handle_sideload(
			array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			),
			$post_id,
			(string) get_post_meta( $post_id, '_mdb_article_title', true )
		);
		if ( is_wp_error( $result ) ) {
			wp_delete_file( $tmp );
			return $result;
		}

		$attachment_id = (int) $result;
		update_post_meta( $attachment_id, '_mdb_speech_id', $post_id );
		update_post_meta( $attachment_id, '_mdb_article_image_source_url', esc_url_raw( $url ) );
		$this->connect( $post_id, $attachment_id );

		return $attachment_id;
	}

	private function find_existing( string $url ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_key'       => '_mdb_article_image_source_url',
				'meta_value'     => esc_url_raw( $url ),
			)
		);
		return isset( $ids[0] ) && 'attachment' === get_post_type( (int) $ids[0] )
			? (int) $ids[0]
			: 0;
	}

	private function connect( int $post_id, int $attachment_id ): void {
		update_post_meta( $post_id, '_mdb_article_image_id', $attachment_id );
		if ( ! has_post_thumbnail( $post_id ) ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	private function load_media_dependencies(): void {
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}
}

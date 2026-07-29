<?php

declare(strict_types=1);

require_once __DIR__ . '/phpunit-shim.php';

define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'MDB_SPEECHES_VERSION', '2.0.3' );
define( 'MDB_SPEECHES_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'MDB_SPEECHES_URL', 'https://example.test/wp-content/plugins/mdb-bundestag-speeches/' );
define( 'MDB_SPEECHES_FILE', MDB_SPEECHES_DIR . 'mdb-bundestag-speeches.php' );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'MDB\\BundestagSpeeches\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = MDB_SPEECHES_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( private string $code = '', private string $message = '' ) {}
		public function get_error_message(): string {
			return $this->message;
		}
		public function get_error_code(): string {
			return $this->code;
		}
	}
}

if ( ! class_exists( 'WP_Block' ) ) {
	class WP_Block {
		/** @var array<string,mixed> */
		public array $context;
		/** @param array<string,mixed> $context */
		public function __construct( array $context = array() ) {
			$this->context = $context;
		}
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		/** @var array<int,int> */
		public array $posts = array();

		/** @param array<string,mixed> $args */
		public function __construct( array $args = array() ) {
			$meta_query = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
			$video_id   = (string) ( $meta_query[0]['value'] ?? '' );
			if ( '' === $video_id ) {
				return;
			}
			foreach ( $GLOBALS['mdb_test_meta'] as $post_id => $meta ) {
				if ( $video_id === (string) ( $meta['_mdb_video_id'] ?? '' ) ) {
					$this->posts[] = (int) $post_id;
					break;
				}
			}
		}
	}
}

$GLOBALS['mdb_test_meta']           = array();
$GLOBALS['mdb_test_attachments']    = array();
$GLOBALS['mdb_test_post_types']     = array();
$GLOBALS['mdb_test_thumbnails']     = array();
$GLOBALS['mdb_test_titles']         = array();
$GLOBALS['mdb_test_contents']       = array();
$GLOBALS['mdb_test_terms']          = array();
$GLOBALS['mdb_test_taxonomy_terms'] = array();
$GLOBALS['mdb_test_options']        = array();
$GLOBALS['mdb_http_head']           = null;
$GLOBALS['mdb_http_get']            = null;
$GLOBALS['mdb_http_get_queue']      = array();
$GLOBALS['mdb_test_get_posts_queue'] = array();
$GLOBALS['mdb_test_deleted_posts']   = array();
$GLOBALS['mdb_test_deleted_media']   = array();
$GLOBALS['mdb_test_deleted_options'] = array();
$GLOBALS['mdb_test_cleared_hooks']   = array();
$GLOBALS['mdb_test_cron_array']      = array();
$GLOBALS['mdb_site_transients']      = array();

function __( string $text, string $domain = '' ): string {
	return $text;
}
function esc_html__( string $text, string $domain = '' ): string {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}
function _n( string $single, string $plural, int $number, string $domain = '' ): string {
	return 1 === $number ? $single : $plural;
}
function wp_parse_url( string $url ): array|false {
	return parse_url( $url );
}
function esc_url_raw( string $url ): string {
	return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
}
function wp_parse_args( mixed $args, array $defaults = array() ): array {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}
function absint( mixed $value ): int {
	return abs( (int) $value );
}
function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ) ?? '';
}
function sanitize_text_field( mixed $value ): string {
	return trim( strip_tags( (string) $value ) );
}
function wp_slash( mixed $value ): mixed {
	return $value;
}
function wp_timezone(): \DateTimeZone {
	return new \DateTimeZone( 'Europe/Berlin' );
}
function get_gmt_from_date( string $date ): string {
	return ( new \DateTimeImmutable( $date, wp_timezone() ) )
		->setTimezone( new \DateTimeZone( 'UTC' ) )
		->format( 'Y-m-d H:i:s' );
}
function wp_insert_post( array $postarr, bool $wp_error = false ): int|WP_Error {
	$GLOBALS['mdb_last_wp_insert'] = $postarr;
	return (int) ( $GLOBALS['mdb_test_insert_id'] ?? 501 );
}
function wp_update_post( array $postarr, bool $wp_error = false ): int|WP_Error {
	$GLOBALS['mdb_last_wp_update'] = $postarr;
	return (int) ( $postarr['ID'] ?? 0 );
}
function current_time( string $type, bool $gmt = false ): string {
	return '2026-07-29 10:00:00';
}
function add_settings_error( string $setting, string $code, string $message ): void {
	$GLOBALS['mdb_settings_errors'][ $code ] = $message;
}
function get_the_ID(): int {
	return 0;
}
function get_post_meta( int $post_id, string $key, bool $single = false ): mixed {
	return $GLOBALS['mdb_test_meta'][ $post_id ][ $key ] ?? '';
}
function update_post_meta( int $post_id, string $key, mixed $value ): void {
	$GLOBALS['mdb_test_meta'][ $post_id ][ $key ] = $value;
}
function delete_post_meta( int $post_id, string $key ): void {
	unset( $GLOBALS['mdb_test_meta'][ $post_id ][ $key ] );
}
function update_option( string $option, mixed $value, bool|null $autoload = null ): bool {
	$GLOBALS['mdb_test_options'][ $option ] = $value;
	return true;
}
function delete_option( string $option ): bool {
	$GLOBALS['mdb_test_deleted_options'][] = $option;
	unset( $GLOBALS['mdb_test_options'][ $option ] );
	return true;
}
function delete_site_transient( string $transient ): bool {
	$GLOBALS['mdb_test_deleted_options'][] = '_site_transient_' . $transient;
	return true;
}
function wp_clear_scheduled_hook( string $hook, array $args = array(), bool $wp_error = false ): int|false|WP_Error {
	$GLOBALS['mdb_test_cleared_hooks'][] = array( $hook, $args );
	return 1;
}
function _get_cron_array(): array {
	return $GLOBALS['mdb_test_cron_array'];
}
function term_exists( int|string $term, string $taxonomy = '', int $parent = 0 ): array|int|null {
	foreach ( $GLOBALS['mdb_test_taxonomy_terms'][ $taxonomy ] ?? array() as $term_id => $stored ) {
		if ( (string) $term === (string) $term_id || (string) $term === $stored['name'] || (string) $term === $stored['slug'] ) {
			return array( 'term_id' => (string) $term_id, 'term_taxonomy_id' => (string) $term_id );
		}
	}
	return null;
}
function wp_insert_term( string $term, string $taxonomy, array $args = array() ): array|WP_Error {
	$term_id = count( $GLOBALS['mdb_test_taxonomy_terms'][ $taxonomy ] ?? array() ) + 1;
	$GLOBALS['mdb_test_taxonomy_terms'][ $taxonomy ][ $term_id ] = array(
		'name' => $term,
		'slug' => (string) ( $args['slug'] ?? sanitize_key( $term ) ),
	);
	return array( 'term_id' => $term_id, 'term_taxonomy_id' => $term_id );
}
function wp_set_object_terms( int $object_id, string|int|array $terms, string $taxonomy, bool $append = false ): array|WP_Error {
	$GLOBALS['mdb_test_terms'][ $object_id ][ $taxonomy ] = (array) $terms;
	return array_map( 'intval', (array) $terms );
}
function get_term_by( string $field, string|int $value, string $taxonomy = '', string $output = 'OBJECT', string $filter = 'raw' ): object|array|false {
	return $GLOBALS['mdb_test_term_by'] ?? false;
}
function wp_delete_term( int $term_id, string $taxonomy, array $args = array() ): bool|int|WP_Error {
	$GLOBALS['mdb_test_deleted_terms'][] = array( $term_id, $taxonomy );
	return true;
}
function get_post_type( int $post_id ): string|false {
	return $GLOBALS['mdb_test_post_types'][ $post_id ] ?? false;
}
function get_posts( array $args = array() ): array {
	if ( ! empty( $GLOBALS['mdb_test_get_posts_queue'] ) ) {
		return (array) array_shift( $GLOBALS['mdb_test_get_posts_queue'] );
	}
	return array();
}
function wp_delete_attachment( int $post_id, bool $force_delete = false ): object|false|null {
	$GLOBALS['mdb_test_deleted_media'][] = $post_id;
	return (object) array( 'ID' => $post_id );
}
function wp_delete_post( int $post_id, bool $force_delete = false ): object|false|null {
	$GLOBALS['mdb_test_deleted_posts'][] = $post_id;
	return (object) array( 'ID' => $post_id );
}
function wp_get_attachment_url( int $attachment_id ): string|false {
	return $GLOBALS['mdb_test_attachments'][ $attachment_id ] ?? false;
}
function get_permalink( int $post_id ): string {
	return 'https://example.test/?p=' . $post_id;
}
function get_the_title( mixed $post = 0 ): string {
	$post_id = is_object( $post ) ? (int) $post->ID : (int) $post;
	return $GLOBALS['mdb_test_titles'][ $post_id ] ?? 'Testrede';
}
function get_post_field( string $field, int $post_id ): mixed {
	return 'post_content' === $field ? ( $GLOBALS['mdb_test_contents'][ $post_id ] ?? '' ) : '';
}
function get_the_post_thumbnail_url( int $post_id, string|array $size = 'post-thumbnail' ): string|false {
	$attachment_id = (int) ( $GLOBALS['mdb_test_thumbnails'][ $post_id ] ?? 0 );
	return $attachment_id > 0 ? ( $GLOBALS['mdb_test_attachments'][ $attachment_id ] ?? false ) : false;
}
function get_block_wrapper_attributes( array $extra = array() ): string {
	return isset( $extra['class'] ) ? 'class="' . esc_attr( (string) $extra['class'] ) . '"' : '';
}
function esc_url( string $url ): string {
	return htmlspecialchars( filter_var( $url, FILTER_SANITIZE_URL ) ?: '', ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}
function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}
function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}
function add_query_arg( string $key, string $value, string $url ): string {
	return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . rawurlencode( $key ) . '=' . rawurlencode( $value );
}
function get_option( string $key, mixed $default = false ): mixed {
	return $GLOBALS['mdb_test_options'][ $key ] ?? $default;
}
function get_site_transient( string $transient ): mixed {
	return $GLOBALS['mdb_site_transients'][ $transient ] ?? false;
}
function set_site_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
	$GLOBALS['mdb_site_transients'][ $transient ] = $value;
	return true;
}
function plugin_basename( string $file ): string {
	$file = str_replace( '\\', '/', $file );
	return basename( dirname( $file ) ) . '/' . basename( $file );
}
function home_url( string $path = '' ): string {
	return 'https://example.test' . $path;
}
function is_wp_error( mixed $value ): bool {
	return $value instanceof WP_Error;
}
function wp_safe_remote_head( string $url, array $args = array() ): mixed {
	return $GLOBALS['mdb_http_head'];
}
function wp_safe_remote_get( string $url, array $args = array() ): mixed {
	$GLOBALS['mdb_last_http_get_args'] = $args;
	if ( ! empty( $GLOBALS['mdb_http_get_queue'] ) ) {
		$response = array_shift( $GLOBALS['mdb_http_get_queue'] );
	} else {
		$response = $GLOBALS['mdb_http_get'];
	}
	if ( ! empty( $args['stream'] ) && ! empty( $args['filename'] ) && is_array( $response ) ) {
		file_put_contents( (string) $args['filename'], (string) ( $response['body'] ?? '' ) );
	}
	return $response;
}
function wp_remote_retrieve_response_code( mixed $response ): int {
	return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
}
function wp_remote_retrieve_header( mixed $response, string $name ): string {
	return is_array( $response ) ? (string) ( $response['headers'][ strtolower( $name ) ] ?? '' ) : '';
}
function wp_remote_retrieve_body( mixed $response ): string {
	return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
}
function wp_tempnam( string $filename = '' ): string|false {
	return tempnam( sys_get_temp_dir(), 'mdb-' );
}
function wp_delete_file( string $file ): bool {
	return ! is_file( $file ) || unlink( $file );
}
function sanitize_file_name( string $filename ): string {
	return preg_replace( '/[^A-Za-z0-9._-]/', '-', basename( $filename ) ) ?? '';
}
function wp_check_filetype_and_ext( string $file, string $filename, array $mimes = array() ): array {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$type      = match ( $extension ) {
		'jpg', 'jpeg', 'jpe' => 'image/jpeg',
		'png'                => 'image/png',
		'webp'               => 'image/webp',
		'mp4'                => 'video/mp4',
		default              => false,
	};
	return array( 'ext' => $extension ?: false, 'type' => $type, 'proper_filename' => false );
}
function media_handle_sideload( array $file, int $post_id, string $description = '' ): int|WP_Error {
	if ( is_file( (string) ( $file['tmp_name'] ?? '' ) ) ) {
		unlink( (string) $file['tmp_name'] );
	}
	return (int) ( $GLOBALS['mdb_media_sideload_result'] ?? 501 );
}
function has_post_thumbnail( int $post_id ): bool {
	return isset( $GLOBALS['mdb_test_thumbnails'][ $post_id ] );
}
function set_post_thumbnail( int $post_id, int $attachment_id ): bool {
	$GLOBALS['mdb_test_thumbnails'][ $post_id ] = $attachment_id;
	return true;
}
function get_post_thumbnail_id( int $post_id ): int|false {
	return isset( $GLOBALS['mdb_test_thumbnails'][ $post_id ] )
		? (int) $GLOBALS['mdb_test_thumbnails'][ $post_id ]
		: false;
}
function delete_post_thumbnail( int $post_id ): bool {
	unset( $GLOBALS['mdb_test_thumbnails'][ $post_id ] );
	return true;
}

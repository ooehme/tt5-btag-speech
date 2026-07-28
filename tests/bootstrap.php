<?php

declare(strict_types=1);

require_once __DIR__ . '/phpunit-shim.php';

define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'MDB_SPEECHES_VERSION', '1.1.2' );
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

$GLOBALS['mdb_test_meta']        = array();
$GLOBALS['mdb_test_attachments'] = array();
$GLOBALS['mdb_test_post_types']  = array();
$GLOBALS['mdb_test_thumbnails']  = array();
$GLOBALS['mdb_test_titles']      = array();
$GLOBALS['mdb_test_options']     = array();
$GLOBALS['mdb_http_head']        = null;
$GLOBALS['mdb_http_get']         = null;
$GLOBALS['mdb_http_get_queue']   = array();

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
function get_post_type( int $post_id ): string|false {
	return $GLOBALS['mdb_test_post_types'][ $post_id ] ?? false;
}
function get_posts( array $args = array() ): array {
	return array();
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

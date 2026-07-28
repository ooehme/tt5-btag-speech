<?php

declare(strict_types=1);

require_once __DIR__ . '/phpunit-shim.php';

define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'MDB_SPEECHES_VERSION', '1.0.0' );
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
$GLOBALS['mdb_test_titles']      = array();
$GLOBALS['mdb_test_options']     = array();
$GLOBALS['mdb_http_head']        = null;
$GLOBALS['mdb_http_get']         = null;

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
	return $GLOBALS['mdb_http_get'];
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

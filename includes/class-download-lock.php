<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Download_Lock {
	private const TTL = HOUR_IN_SECONDS;

	public function acquire( int $post_id ): string|false {
		$key   = $this->key( $post_id );
		$token = wp_generate_uuid4();
		$value = array(
			'token'   => $token,
			'expires' => time() + self::TTL,
		);
		if ( add_option( $key, $value, '', false ) ) {
			return $token;
		}

		$existing = get_option( $key );
		if ( is_array( $existing ) && (int) ( $existing['expires'] ?? 0 ) < time() ) {
			delete_option( $key );
			return add_option( $key, $value, '', false ) ? $token : false;
		}
		return false;
	}

	public function release( int $post_id, string $token ): void {
		$key      = $this->key( $post_id );
		$existing = get_option( $key );
		if ( is_array( $existing ) && hash_equals( (string) ( $existing['token'] ?? '' ), $token ) ) {
			delete_option( $key );
		}
	}

	private function key( int $post_id ): string {
		return 'mdb_speeches_download_lock_' . $post_id;
	}
}

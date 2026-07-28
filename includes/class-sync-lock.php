<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Sync_Lock {
	private const OPTION = 'mdb_speeches_sync_lock';
	private const TTL = 15 * MINUTE_IN_SECONDS;

	public function acquire(): string|false {
		$token = wp_generate_uuid4();
		$value = array(
			'token'   => $token,
			'expires' => time() + self::TTL,
		);
		if ( add_option( self::OPTION, $value, '', false ) ) {
			return $token;
		}

		$existing = get_option( self::OPTION );
		if ( is_array( $existing ) && (int) ( $existing['expires'] ?? 0 ) < time() ) {
			delete_option( self::OPTION );
			return add_option( self::OPTION, $value, '', false ) ? $token : false;
		}
		return false;
	}

	public function release( string $token ): void {
		$existing = get_option( self::OPTION );
		if ( is_array( $existing ) && hash_equals( (string) ( $existing['token'] ?? '' ), $token ) ) {
			delete_option( self::OPTION );
		}
	}
}

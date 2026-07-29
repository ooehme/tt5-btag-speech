<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Speaker_Catalog {
	private const SOURCE_URL = 'https://www.bundestag.de/static/appdata/filter/rednerNamen.json';
	private const CACHE_KEY = 'mdb_speeches_speaker_catalog';
	private bool $uses_local_fallback = false;

	public function __construct( private ?string $file = null ) {}

	/**
	 * @return array<int,array{name:string,rednerId:string,filterIds:string}>
	 */
	public function all(): array {
		if ( null === $this->file ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && is_array( $cached['speakers'] ?? null ) && array() !== $cached['speakers'] ) {
				$this->uses_local_fallback = ! empty( $cached['uses_local_fallback'] );
				return $cached['speakers'];
			}

			$speakers = $this->remote();
			if ( array() !== $speakers ) {
				$this->cache( $speakers, false, 12 * HOUR_IN_SECONDS );
				return $speakers;
			}

			$this->uses_local_fallback = true;
			$speakers = $this->from_file( MDB_SPEECHES_DIR . 'data/rednerNamen.json' );
			if ( array() !== $speakers ) {
				$this->cache( $speakers, true, 15 * MINUTE_IN_SECONDS );
			}
			return $speakers;
		}

		return $this->from_file( $this->file );
	}

	public function uses_local_fallback(): bool {
		return $this->uses_local_fallback;
	}

	/**
	 * @param array<int,array{name:string,rednerId:string,filterIds:string}> $speakers
	 */
	private function cache( array $speakers, bool $uses_local_fallback, int $expiration ): void {
		set_site_transient(
			self::CACHE_KEY,
			array(
				'speakers'            => $speakers,
				'uses_local_fallback' => $uses_local_fallback,
			),
			$expiration
		);
	}

	/**
	 * @return array<int,array{name:string,rednerId:string,filterIds:string}>
	 */
	private function remote(): array {
		$response = wp_safe_remote_get(
			self::SOURCE_URL,
			array(
				'timeout'             => 15,
				'redirection'         => 0,
				'limit_response_size' => 1_000_000,
				'headers'             => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'MDB Bundestagsreden/' . MDB_SPEECHES_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		return $this->decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * @return array<int,array{name:string,rednerId:string,filterIds:string}>
	 */
	private function from_file( string $file ): array {
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$json = file_get_contents( $file );
		if ( false === $json ) {
			return array();
		}

		return $this->decode( $json );
	}

	/**
	 * Supports the bundled fallback format and the official Bundestag filter format.
	 *
	 * @return array<int,array{name:string,rednerId:string,filterIds:string}>
	 */
	private function decode( string $json ): array {
		$rows = json_decode( $json, true );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$speakers = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$name       = trim( (string) ( $row['name'] ?? $row['label'] ?? '' ) );
			$filter_ids = strtoupper( trim( (string) ( $row['filterIds'] ?? $row['value'] ?? '' ) ) );
			$filter_ids = preg_replace( '/\s+/', ' ', $filter_ids ) ?? '';
			$id         = trim( (string) ( $row['rednerId'] ?? '' ) );
			if ( '' === $id && preg_match( '/^\d+/', $filter_ids, $matches ) ) {
				$id = $matches[0];
			}
			if ( '' === $name || ! preg_match( '/^\d+$/', $id ) ) {
				continue;
			}
			if ( ! preg_match( '/^\d+(?: OR \d+)*$/', $filter_ids ) ) {
				$filter_ids = $id;
			}

			$speakers[ $id ] = array(
				'name'     => $name,
				'rednerId' => $id,
				'filterIds' => $filter_ids,
			);
		}

		$speakers = array_values( $speakers );
		usort(
			$speakers,
			static fn ( array $left, array $right ): int => strnatcasecmp( $left['name'], $right['name'] )
		);

		return $speakers;
	}
}

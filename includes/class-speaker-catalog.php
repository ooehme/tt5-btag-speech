<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Speaker_Catalog {
	public function __construct( private ?string $file = null ) {}

	/**
	 * @return array<int,array{name:string,rednerId:string}>
	 */
	public function all(): array {
		$file = $this->file ?? MDB_SPEECHES_DIR . 'data/redner.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$json = file_get_contents( $file );
		if ( false === $json ) {
			return array();
		}

		$rows = json_decode( $json, true );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$speakers = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$name = trim( (string) ( $row['name'] ?? '' ) );
			$id   = trim( (string) ( $row['rednerId'] ?? '' ) );
			if ( '' === $name || ! preg_match( '/^\d+$/', $id ) ) {
				continue;
			}

			$speakers[ $id ] = array(
				'name'     => $name,
				'rednerId' => $id,
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

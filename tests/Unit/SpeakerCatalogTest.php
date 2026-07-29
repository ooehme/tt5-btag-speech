<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Speaker_Catalog;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class SpeakerCatalogTest extends TestCase {
	public function test_bundled_catalog_contains_valid_speakers(): void {
		$GLOBALS['mdb_site_transients'] = array();
		$GLOBALS['mdb_http_get']        = new WP_Error( 'offline', 'Bundestag offline' );
		$catalog = new Speaker_Catalog();
		$speakers = $catalog->all();

		self::assertTrue( count( $speakers ) > 0 );
		self::assertTrue( $catalog->uses_local_fallback() );

		$janich = array_values(
			array_filter(
				$speakers,
				static fn ( array $speaker ): bool => '21244 OR 12404' === $speaker['filterIds']
			)
		);
		self::assertCount( 1, $janich );
		self::assertSame( 'Janich, Steffen', $janich[0]['name'] );
		self::assertSame( '21244', $janich[0]['rednerId'] );
		self::assertSame( '21244 OR 12404', $janich[0]['filterIds'] );
	}

	public function test_invalid_catalog_entries_are_ignored_and_ids_are_unique(): void {
		$file = tempnam( sys_get_temp_dir(), 'mdb-speakers-' );
		self::assertTrue( false !== $file );
		file_put_contents(
			$file,
			(string) json_encode(
				array(
					array( 'name' => 'Erste Person', 'rednerId' => '123' ),
					array( 'name' => 'Ungültige ID', 'rednerId' => '12x' ),
					array( 'name' => '', 'rednerId' => '456' ),
					array( 'name' => 'Aktualisierte Person', 'rednerId' => '123', 'filterIds' => '789 or 123' ),
				),
				JSON_UNESCAPED_UNICODE
			)
		);

		try {
			$speakers = ( new Speaker_Catalog( $file ) )->all();
			self::assertCount( 1, $speakers );
			self::assertSame( 'Aktualisierte Person', $speakers[0]['name'] );
			self::assertSame( '123', $speakers[0]['rednerId'] );
			self::assertSame( '789 OR 123', $speakers[0]['filterIds'] );
		} finally {
			unlink( $file );
		}
	}

	public function test_official_bundestag_format_is_supported(): void {
		$file = tempnam( sys_get_temp_dir(), 'mdb-speakers-official-' );
		self::assertTrue( false !== $file );
		file_put_contents(
			$file,
			(string) json_encode(
				array(
					array(
						'value' => '9674 OR 21594 OR 12832',
						'label' => 'Weidel, Dr. Alice ',
						'dep'   => array( array( 'wahlperiode' => array( 19, 20, 21 ) ) ),
					),
				),
				JSON_UNESCAPED_UNICODE
			)
		);

		try {
			$speakers = ( new Speaker_Catalog( $file ) )->all();
			self::assertCount( 1, $speakers );
			self::assertSame( 'Weidel, Dr. Alice', $speakers[0]['name'] );
			self::assertSame( '9674', $speakers[0]['rednerId'] );
			self::assertSame( '9674 OR 21594 OR 12832', $speakers[0]['filterIds'] );
		} finally {
			unlink( $file );
		}
	}
}

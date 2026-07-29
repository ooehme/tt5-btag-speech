<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Speaker_Catalog;
use PHPUnit\Framework\TestCase;

final class SpeakerCatalogTest extends TestCase {
	public function test_bundled_catalog_contains_valid_speakers(): void {
		$speakers = ( new Speaker_Catalog() )->all();

		self::assertTrue( count( $speakers ) > 0 );

		$janich = array_values(
			array_filter(
				$speakers,
				static fn ( array $speaker ): bool => '12404' === $speaker['rednerId']
			)
		);
		self::assertCount( 1, $janich );
		self::assertSame( 'Steffen Janich', $janich[0]['name'] );
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
					array( 'name' => 'Aktualisierte Person', 'rednerId' => '123' ),
				),
				JSON_UNESCAPED_UNICODE
			)
		);

		try {
			$speakers = ( new Speaker_Catalog( $file ) )->all();
			self::assertCount( 1, $speakers );
			self::assertSame( 'Aktualisierte Person', $speakers[0]['name'] );
			self::assertSame( '123', $speakers[0]['rednerId'] );
		} finally {
			unlink( $file );
		}
	}
}

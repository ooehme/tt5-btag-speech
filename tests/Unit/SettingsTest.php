<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase {
	public function test_verified_defaults_remain_configurable(): void {
		$defaults = Settings::defaults();
		self::assertSame( '12404', $defaults['speaker_id'] );
		self::assertSame( '21244 OR 12404', $defaults['speaker_filter'] );

		$result = ( new Settings() )->sanitize(
			array(
				'speaker_id'     => '99999',
				'speaker_filter' => '88888 or 99999',
				'interval'       => 'daily',
				'download_mode'  => 'local',
				'quality'        => '360p_1000',
				'max_file_size'  => '42',
			)
		);
		self::assertSame( '99999', $result['speaker_id'] );
		self::assertSame( '88888 OR 99999', $result['speaker_filter'] );
		self::assertSame( '360p_1000', $result['quality'] );
		self::assertSame( 42, $result['max_file_size'] );
	}

	public function test_invalid_ids_and_options_are_rejected(): void {
		$result = ( new Settings() )->sanitize(
			array(
				'speaker_id'     => '12x',
				'speaker_filter' => '1 OR evil',
				'interval'       => 'every_second',
				'download_mode'  => 'unsafe',
				'quality'        => 'arbitrary',
				'max_file_size'  => '99999',
			)
		);
		self::assertSame( '12404', $result['speaker_id'] );
		self::assertSame( '12404', $result['speaker_filter'] );
		self::assertSame( 'twicedaily', $result['interval'] );
		self::assertSame( 'automatic', $result['download_mode'] );
		self::assertSame( '1080p_8000', $result['quality'] );
		self::assertSame( 2048, $result['max_file_size'] );
	}

	public function test_legacy_embed_mode_migrates_to_automatic_downloads(): void {
		$GLOBALS['mdb_test_options'][ Settings::OPTION ] = array( 'download_mode' => 'embed_only' );

		self::assertSame( 'automatic', ( new Settings() )->get( 'download_mode' ) );
	}
}

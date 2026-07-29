<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Release_Updater;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WP_Error;

final class ReleaseUpdaterTest extends TestCase {
	public function test_current_version_response_advertises_update_support(): void {
		$GLOBALS['mdb_site_transients'] = array();
		$GLOBALS['mdb_http_get']        = new WP_Error( 'offline', 'Offline' );
		$plugin_file                    = plugin_basename( MDB_SPEECHES_FILE );

		$result = ( new Release_Updater() )->update(
			false,
			array( 'UpdateURI' => 'https://github.com/ooehme/tt5-btag-speech' ),
			$plugin_file,
			array()
		);

		self::assertSame( MDB_SPEECHES_VERSION, $result['version'] );
		self::assertSame( $plugin_file, $result['plugin'] );
		self::assertSame( '', $result['package'] );
	}

	public function test_only_versioned_asset_from_expected_release_path_is_accepted(): void {
		$method = new ReflectionMethod( Release_Updater::class, 'package_url' );
		$method->setAccessible( true );
		$updater = new Release_Updater();
		$name    = 'mdb-bundestag-speeches-1.2.3.zip';

		$valid = $method->invoke(
			$updater,
			array(
				array(
					'name'                 => $name,
					'browser_download_url' => 'https://github.com/ooehme/tt5-btag-speech/releases/download/v1.2.3/' . $name,
				),
			),
			'1.2.3'
		);
		self::assertStringContainsString( '/v1.2.3/' . $name, $valid );

		$invalid = $method->invoke(
			$updater,
			array(
				array(
					'name'                 => $name,
					'browser_download_url' => 'https://github.com/attacker/project/releases/download/v1.2.3/' . $name,
				),
			),
			'1.2.3'
		);
		self::assertSame( '', $invalid );
	}
}

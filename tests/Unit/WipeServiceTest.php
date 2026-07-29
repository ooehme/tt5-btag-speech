<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Cron;
use MDB\BundestagSpeeches\Settings;
use MDB\BundestagSpeeches\Wipe_Service;
use PHPUnit\Framework\TestCase;

final class WipeServiceTest extends TestCase {
	public function test_wipe_removes_current_and_legacy_plugin_data(): void {
		$GLOBALS['mdb_test_get_posts_queue'] = array(
			array( 17, 18 ),
			array( 90, 91 ),
			array( 91, 92 ),
		);
		$GLOBALS['mdb_test_deleted_posts']   = array();
		$GLOBALS['mdb_test_deleted_media']   = array();
		$GLOBALS['mdb_test_deleted_options'] = array();
		$GLOBALS['mdb_test_cleared_hooks']   = array();
		$GLOBALS['mdb_test_cron_array']      = array(
			1000 => array(
				Cron::DOWNLOAD_HOOK => array(
					'first'  => array( 'args' => array( 17 ) ),
					'orphan' => array( 'args' => array( 999 ) ),
				),
			),
		);
		$GLOBALS['mdb_test_deleted_terms']   = array();
		$GLOBALS['mdb_test_term_by']         = (object) array(
			'term_id' => 5,
			'count'   => 0,
		);
		$GLOBALS['mdb_test_options']         = array(
			Settings::OPTION           => array( 'speaker_id' => '99999' ),
			'mdb_speeches_last_sync'   => array( 'time' => 'old' ),
			'mdb_speeches_sync_lock'   => 'old-lock',
		);

		$result = ( new Wipe_Service() )->wipe();

		self::assertSame(
			array(
				'posts'       => 2,
				'attachments' => 3,
				'category'    => 1,
				'failed'      => 0,
			),
			$result
		);
		self::assertSame( array( 90, 91, 92 ), $GLOBALS['mdb_test_deleted_media'] );
		self::assertSame( array( 17, 18 ), $GLOBALS['mdb_test_deleted_posts'] );
		self::assertSame(
			array(
				array( Cron::DOWNLOAD_HOOK, array( 17 ) ),
				array( Cron::DOWNLOAD_HOOK, array( 999 ) ),
				array( Cron::SYNC_HOOK, array() ),
			),
			$GLOBALS['mdb_test_cleared_hooks']
		);
		self::assertTrue( in_array( 'mdb_speeches_download_lock_17', $GLOBALS['mdb_test_deleted_options'], true ) );
		self::assertTrue( in_array( 'mdb_speeches_download_lock_18', $GLOBALS['mdb_test_deleted_options'], true ) );
		self::assertTrue( in_array( 'mdb_speeches_download_lock_999', $GLOBALS['mdb_test_deleted_options'], true ) );
		self::assertSame( array( array( 5, 'category' ) ), $GLOBALS['mdb_test_deleted_terms'] );
		self::assertSame( Settings::defaults(), $GLOBALS['mdb_test_options'][ Settings::OPTION ] );
		self::assertTrue( $GLOBALS['mdb_test_options'][ Wipe_Service::PAUSE_OPTION ] );
		self::assertFalse( isset( $GLOBALS['mdb_test_options']['mdb_speeches_last_sync'] ) );
		self::assertFalse( isset( $GLOBALS['mdb_test_options']['mdb_speeches_sync_lock'] ) );
	}
}

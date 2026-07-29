<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Speech_Repository;
use MDB\BundestagSpeeches\Sync_Status;
use PHPUnit\Framework\TestCase;

final class RepositoryStatusTest extends TestCase {
	public function test_existing_speech_post_date_is_corrected_during_sync(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_video_id'      => '7654763',
				'_mdb_attachment_id' => '',
				'_mdb_sync_status'   => Sync_Status::EMBED_AVAILABLE,
			),
		);
		unset( $GLOBALS['mdb_last_wp_update'] );

		$result = ( new Speech_Repository() )->upsert(
			array(
				'video_id' => '7654763',
				'title'    => '86. Sitzung: Rede von Steffen Janich',
				'date'     => 'Rede am 25.06.2026',
			),
			'embed_only'
		);

		self::assertSame( 17, $result );
		self::assertSame( 17, $GLOBALS['mdb_last_wp_update']['ID'] );
		self::assertSame( '2026-06-25 12:00:00', $GLOBALS['mdb_last_wp_update']['post_date'] );
		self::assertSame( '2026-06-25 10:00:00', $GLOBALS['mdb_last_wp_update']['post_date_gmt'] );
	}

	public function test_duplicate_video_ids_are_removed(): void {
		$records = Speech_Repository::unique_by_video_id(
			array(
				array( 'video_id' => '7654763', 'title' => 'First' ),
				array( 'video_id' => '7654763', 'title' => 'Duplicate' ),
				array( 'video_id' => '7654306', 'title' => 'Second' ),
				array( 'video_id' => '../invalid', 'title' => 'Invalid' ),
			)
		);
		self::assertCount( 2, $records );
		self::assertSame( 'First', $records[0]['title'] );
	}

	public function test_status_logic_keeps_download_failures_separate(): void {
		self::assertSame( Sync_Status::DOWNLOAD_PENDING, Sync_Status::after_seen( 'automatic', 0 ) );
		self::assertSame( Sync_Status::EMBED_AVAILABLE, Sync_Status::after_seen( 'embed_only', 0 ) );
		self::assertSame( Sync_Status::DOWNLOADED, Sync_Status::after_seen( 'automatic', 42 ) );
		self::assertSame(
			Sync_Status::DOWNLOAD_FAILED,
			Sync_Status::after_seen( 'automatic', 0, Sync_Status::DOWNLOAD_FAILED )
		);
		self::assertTrue( Sync_Status::is_downloadable( Sync_Status::DOWNLOAD_FAILED ) );
		self::assertFalse( Sync_Status::is_downloadable( Sync_Status::DOWNLOADED ) );
	}
}

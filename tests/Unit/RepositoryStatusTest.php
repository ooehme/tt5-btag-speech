<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Speech_Repository;
use MDB\BundestagSpeeches\Sync_Status;
use PHPUnit\Framework\TestCase;

final class RepositoryStatusTest extends TestCase {
	public function test_existing_post_uses_article_title_video_block_and_meta_date(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_video_id'      => '7654763',
				'_mdb_attachment_id' => '',
				'_mdb_sync_status'   => Sync_Status::DOWNLOAD_AVAILABLE,
			),
		);
		$GLOBALS['mdb_test_contents'][17] = '';
		unset( $GLOBALS['mdb_last_wp_update'] );

		$result = ( new Speech_Repository() )->upsert(
			array(
				'video_id'     => '7654763',
				'title'        => '86. Sitzung: Rede von Steffen Janich',
				'article_title' => 'Cybersicherheit beraten',
				'date'         => '25.06.2026',
			),
			'automatic'
		);

		self::assertSame( 17, $result );
		self::assertSame( 'Cybersicherheit beraten', $GLOBALS['mdb_last_wp_update']['post_title'] );
		self::assertSame(
			'<!-- wp:mdb/speech-video {"display":"direct"} /-->',
			$GLOBALS['mdb_last_wp_update']['post_content']
		);
		self::assertSame( '2026-06-25 12:00:00', $GLOBALS['mdb_last_wp_update']['post_date'] );
		self::assertSame( '2026-06-25 10:00:00', $GLOBALS['mdb_last_wp_update']['post_date_gmt'] );
		self::assertSame( 'Bundestagsrede', $GLOBALS['mdb_test_taxonomy_terms']['category'][1]['name'] );
		self::assertSame( 'bundestagsrede', $GLOBALS['mdb_test_taxonomy_terms']['category'][1]['slug'] );
		self::assertSame( array( 1 ), $GLOBALS['mdb_test_terms'][17]['category'] );
	}

	public function test_video_block_is_added_without_losing_editorial_content(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_video_id'      => '7654763',
				'_mdb_attachment_id' => '',
			),
		);
		$GLOBALS['mdb_test_contents'][17] = '<!-- wp:paragraph --><p>Redaktionell</p><!-- /wp:paragraph -->';

		( new Speech_Repository() )->upsert(
			array(
				'video_id' => '7654763',
				'title'    => 'TOP 24: Rede von Steffen Janich',
			),
			'local'
		);

		self::assertSame( 'TOP 24', $GLOBALS['mdb_last_wp_update']['post_title'] );
		self::assertStringContainsString(
			'<!-- wp:mdb/speech-video {"display":"direct"} /-->',
			$GLOBALS['mdb_last_wp_update']['post_content']
		);
		self::assertStringContainsString(
			'<!-- wp:paragraph --><p>Redaktionell</p><!-- /wp:paragraph -->',
			$GLOBALS['mdb_last_wp_update']['post_content']
		);
	}

	public function test_existing_video_block_configuration_is_preserved(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_video_id'      => '7654763',
				'_mdb_attachment_id' => '',
			),
		);
		$GLOBALS['mdb_test_contents'][17] = '<!-- wp:mdb/speech-video {"display":"click_to_load"} /-->';

		( new Speech_Repository() )->upsert(
			array(
				'video_id' => '7654763',
				'title'    => 'TOP 24: Rede von Steffen Janich',
			),
			'local'
		);

		self::assertFalse( isset( $GLOBALS['mdb_last_wp_update']['post_content'] ) );
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
		self::assertSame( Sync_Status::DOWNLOAD_AVAILABLE, Sync_Status::after_seen( 'local', 0 ) );
		self::assertSame( Sync_Status::DOWNLOADED, Sync_Status::after_seen( 'automatic', 42 ) );
		self::assertSame(
			Sync_Status::DOWNLOAD_FAILED,
			Sync_Status::after_seen( 'automatic', 0, Sync_Status::DOWNLOAD_FAILED )
		);
		self::assertTrue( Sync_Status::is_downloadable( Sync_Status::DOWNLOAD_FAILED ) );
		self::assertFalse( Sync_Status::is_downloadable( Sync_Status::DOWNLOADED ) );
	}
}

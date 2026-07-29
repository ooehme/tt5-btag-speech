<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Download_Lock;
use MDB\BundestagSpeeches\Download_Service;
use MDB\BundestagSpeeches\MP4_Validator;
use MDB\BundestagSpeeches\Settings;
use MDB\BundestagSpeeches\Speech_Repository;
use MDB\BundestagSpeeches\Subtitle_Service;
use MDB\BundestagSpeeches\Sync_Status;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;

final class DownloadServiceTest extends TestCase {
	public function test_missing_8000_variant_falls_back_to_5000(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_video_id'    => '7612848',
				'_mdb_download_url' => ( new URL_Resolver() )->download_url( '7612848', '1080p_8000' ),
			),
		);
		$GLOBALS['mdb_test_post_types'][17] = Speech_Repository::POST_TYPE;
		$GLOBALS['mdb_test_options']        = array(
			Settings::OPTION => array(
				'quality'       => '1080p_8000',
				'max_file_size' => 10,
			),
		);
		$GLOBALS['mdb_http_head_urls']      = array();
		$GLOBALS['mdb_http_head_queue']     = array(
			array(
				'response' => array( 'code' => 404 ),
				'headers'  => array( 'content-type' => 'text/html', 'content-length' => '0' ),
			),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'video/mp4', 'content-length' => '3' ),
			),
		);
		$GLOBALS['mdb_http_get']            = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/octet-stream' ),
			'body'     => "1\r\n00:00:01,000 --> 00:00:02,000\r\nTest\r\n",
		);
		$GLOBALS['mdb_media_sideload_result'] = 90;

		$settings  = new Settings();
		$urls      = new URL_Resolver();
		$subtitles = new Subtitle_Service( $urls );
		$service   = new Download_Service(
			new MP4_Validator( $settings, $urls ),
			new Speech_Repository(),
			new Download_Lock(),
			$settings,
			$urls,
			$subtitles
		);

		$result = $service->download( 17 );

		self::assertSame( 90, $result );
		self::assertCount( 2, $GLOBALS['mdb_http_head_urls'] );
		self::assertStringContainsString( '_1080_5000kb_', $GLOBALS['mdb_last_download_url'] );
		self::assertStringContainsString( '_1080_5000kb_', $GLOBALS['mdb_test_meta'][17]['_mdb_download_url'] );
		self::assertSame( Sync_Status::DOWNLOADED, $GLOBALS['mdb_test_meta'][17]['_mdb_sync_status'] );
		self::assertSame( 90, $GLOBALS['mdb_test_meta'][17]['_mdb_subtitle_attachment_id'] );
		self::assertStringContainsString( 'WEBVTT', $GLOBALS['mdb_last_sideload_body'] );
	}
}

<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Legacy_Article_Image_Cleanup;
use PHPUnit\Framework\TestCase;

final class LegacyArticleImageCleanupTest extends TestCase {
	public function test_cleanup_removes_only_legacy_article_images(): void {
		$GLOBALS['mdb_test_options']         = array();
		$GLOBALS['mdb_test_deleted_media']   = array();
		$GLOBALS['mdb_test_get_posts_queue'] = array(
			array( 17, 18, 19 ),
			array( 501, 502 ),
		);
		$GLOBALS['mdb_test_meta']            = array(
			17  => array(
				'_mdb_article_image_id'    => 501,
				'_mdb_article_image_error' => 'Alt',
			),
			19  => array(
				'_mdb_article_image_id' => 502,
			),
			501 => array(
				'_mdb_article_image_source_url' => 'https://www.bundestag.de/resource/image/one.jpg',
			),
			502 => array(
				'_mdb_article_image_source_url' => 'https://www.bundestag.de/resource/image/two.jpg',
			),
		);
		$GLOBALS['mdb_test_post_types'][501] = 'attachment';
		$GLOBALS['mdb_test_post_types'][502] = 'attachment';
		$GLOBALS['mdb_test_thumbnails']      = array(
			17 => 501,
			18 => 777,
			19 => 778,
		);

		( new Legacy_Article_Image_Cleanup() )->cleanup();

		self::assertSame( array( 501, 502 ), $GLOBALS['mdb_test_deleted_media'] );
		self::assertFalse( isset( $GLOBALS['mdb_test_thumbnails'][17] ) );
		self::assertSame( 777, $GLOBALS['mdb_test_thumbnails'][18] );
		self::assertSame( 778, $GLOBALS['mdb_test_thumbnails'][19] );
		self::assertFalse( isset( $GLOBALS['mdb_test_meta'][17]['_mdb_article_image_id'] ) );
		self::assertFalse( isset( $GLOBALS['mdb_test_meta'][17]['_mdb_article_image_error'] ) );
		self::assertFalse( isset( $GLOBALS['mdb_test_meta'][19]['_mdb_article_image_id'] ) );
		self::assertSame(
			MDB_SPEECHES_VERSION,
			$GLOBALS['mdb_test_options'][ Legacy_Article_Image_Cleanup::OPTION ]
		);
	}

	public function test_completed_cleanup_is_not_repeated(): void {
		$GLOBALS['mdb_test_options'][ Legacy_Article_Image_Cleanup::OPTION ] = MDB_SPEECHES_VERSION;
		$GLOBALS['mdb_test_deleted_media']                                  = array();
		$GLOBALS['mdb_test_get_posts_queue']                                = array(
			array( 17 ),
			array( 501 ),
		);

		( new Legacy_Article_Image_Cleanup() )->cleanup();

		self::assertSame( array(), $GLOBALS['mdb_test_deleted_media'] );
		self::assertCount( 2, $GLOBALS['mdb_test_get_posts_queue'] );
		$GLOBALS['mdb_test_get_posts_queue'] = array();
	}
}

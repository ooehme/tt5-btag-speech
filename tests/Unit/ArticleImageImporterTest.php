<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Article_Image_Importer;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class ArticleImageImporterTest extends TestCase {
	public function test_remote_article_image_is_bounded_and_added_to_media_library(): void {
		$GLOBALS['mdb_test_meta'][17] = array(
			'_mdb_article_title'     => 'Regierungsentwurf zur Cybersicherheit beraten',
			'_mdb_article_image_url' => 'https://www.bundestag.de/resource/image/1184450/article.jpg',
		);
		$GLOBALS['mdb_http_get'] = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'image/jpeg' ),
			'body'     => 'test-image-content',
		);
		$GLOBALS['mdb_media_sideload_result'] = 501;

		$result = ( new Article_Image_Importer( new URL_Resolver() ) )->import( 17 );

		self::assertSame( 501, $result );
		self::assertSame( 501, $GLOBALS['mdb_test_meta'][17]['_mdb_article_image_id'] );
		self::assertSame( 501, $GLOBALS['mdb_test_thumbnails'][17] );
		self::assertTrue( $GLOBALS['mdb_last_http_get_args']['stream'] );
		self::assertSame( 15_000_001, $GLOBALS['mdb_last_http_get_args']['limit_response_size'] );
	}

	public function test_missing_or_disallowed_image_uses_fallback(): void {
		$GLOBALS['mdb_test_meta'][18] = array();
		$importer = new Article_Image_Importer( new URL_Resolver() );
		self::assertSame( 0, $importer->import( 18 ) );

		$GLOBALS['mdb_test_meta'][18]['_mdb_article_image_url'] = 'https://attacker.test/image.jpg';
		self::assertInstanceOf( WP_Error::class, $importer->import( 18 ) );
	}

	public function test_import_does_not_overwrite_manually_selected_featured_image(): void {
		$GLOBALS['mdb_test_meta'][19] = array(
			'_mdb_article_title'     => 'Artikel',
			'_mdb_article_image_url' => 'https://www.bundestag.de/resource/image/1184450/article.jpg',
		);
		$GLOBALS['mdb_test_thumbnails'][19] = 777;
		$GLOBALS['mdb_http_get'] = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'image/jpeg' ),
			'body'     => 'test-image-content',
		);
		$GLOBALS['mdb_media_sideload_result'] = 502;

		$result = ( new Article_Image_Importer( new URL_Resolver() ) )->import( 19 );

		self::assertSame( 502, $result );
		self::assertSame( 777, $GLOBALS['mdb_test_thumbnails'][19] );
	}
}

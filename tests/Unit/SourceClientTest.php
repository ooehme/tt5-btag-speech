<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Article_Parser;
use MDB\BundestagSpeeches\List_Parser;
use MDB\BundestagSpeeches\Source_Client;
use MDB\BundestagSpeeches\URL_Resolver;
use MDB\BundestagSpeeches\Video_Parser;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class SourceClientTest extends TestCase {
	public function test_expected_speech_record(): void {
		$GLOBALS['mdb_http_get_queue'] = array(
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'text/html; charset=utf-8' ),
				'body'     => (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/video-7654763.html' ),
			),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'text/html; charset=utf-8' ),
				'body'     => (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/article-1184344.html' ),
			),
		);

		$result = $this->client()->speech( '7654763' );
		self::assertSame( '7654763', $result['video_id'] );
		self::assertSame( '86. Sitzung vom 25.06.2026, TOP 24: Rede von Steffen Janich', $result['title'] );
		self::assertSame( 'https://www.bundestag.de/mediathek/video?videoid=7654763', $result['source_url'] );
		self::assertSame( 'https://webtv.bundestag.de/pservices/player/embed/nokey?a=144277506&c=7654763', $result['embed_url'] );
		self::assertStringContainsString( '/7654763/7654763_', $result['download_url'] );
		self::assertSame( '25.06.2026', $result['date'] );
		self::assertSame( '86. Sitzung', $result['session'] );
		self::assertSame( 'TOP 24', $result['topic'] );
		self::assertStringContainsString( '/kw26-de-cybersicherheit-1184344', $result['article_url'] );
		self::assertSame(
			'Regierungsentwurf zur Stärkung der Cybersicherheit beraten',
			$result['article_title']
		);
		self::assertStringContainsString( '/resource/image/1184450/', $result['article_image_url'] );
	}

	public function test_parser_drift_becomes_wp_error(): void {
		$GLOBALS['mdb_http_get'] = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/invalid-video.html' ),
		);
		$result = $this->client()->speech( '7654763' );
		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'mdb_video_parser', $result->get_error_code() );
		self::assertStringContainsString( 'HTML-Struktur', $result->get_error_message() );
	}

	private function client(): Source_Client {
		$urls = new URL_Resolver();
		return new Source_Client( $urls, new List_Parser(), new Video_Parser(), new Article_Parser() );
	}
}

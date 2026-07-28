<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\List_Parser;
use MDB\BundestagSpeeches\Parser_Exception;
use MDB\BundestagSpeeches\Video_Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {
	public function test_list_fixture(): void {
		$html    = (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/speech-list-12404.html' );
		$records = ( new List_Parser() )->parse( $html );

		self::assertCount( 2, $records );
		self::assertSame( '7654763', $records[0]['video_id'] );
		self::assertSame( '25.06.2026', $records[0]['date'] );
		self::assertSame( 'Janich, Steffen, AfD', $records[0]['title'] );
		self::assertStringContainsString( 'TOP 24', $records[0]['topic'] );
	}

	public function test_video_fixture(): void {
		$html   = (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/video-7654763.html' );
		$result = ( new Video_Parser() )->parse( $html );

		self::assertSame( '86. Sitzung vom 25.06.2026, TOP 24: Rede von Steffen Janich', $result['title'] );
		self::assertSame( '25.06.2026', $result['date'] );
		self::assertSame( '86. Sitzung', $result['session'] );
		self::assertSame( 'TOP 24', $result['topic'] );
	}

	public function test_structure_errors_are_explicit(): void {
		foreach (
			array(
				array( new List_Parser(), 'invalid-list.html' ),
				array( new Video_Parser(), 'invalid-video.html' ),
			) as $case
		) {
			list( $parser, $fixture ) = $case;
			try {
				$parser->parse( (string) file_get_contents( dirname( __DIR__ ) . '/fixtures/' . $fixture ) );
				self::fail( 'Expected parser exception for ' . $fixture );
			} catch ( Parser_Exception $exception ) {
				self::assertStringContainsString( 'HTML-Struktur', $exception->getMessage() );
			}
		}
	}

	public function test_video_title_fallback(): void {
		$result = ( new Video_Parser() )->parse(
			'<html><head><meta property="og:title" content="Deutscher Bundestag - Ersatzüberschrift"></head><body></body></html>'
		);
		self::assertSame( 'Ersatzüberschrift', $result['title'] );
	}
}

<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Subtitle_Service;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class SubtitleServiceTest extends TestCase {
	public function test_webvtt_mime_type_is_allowed(): void {
		$mimes = ( new Subtitle_Service( new URL_Resolver() ) )->mime_types( array() );

		self::assertSame( 'text/vtt', $mimes['vtt'] );
	}

	public function test_srt_is_converted_to_webvtt(): void {
		$srt = "1\r\n00:00:06,920 --> 00:00:08,989\r\nFrau Präsidentin!\r\n";
		$vtt = ( new Subtitle_Service( new URL_Resolver() ) )->to_webvtt( $srt );

		self::assertFalse( $vtt instanceof WP_Error );
		self::assertStringContainsString( "WEBVTT\n\n", $vtt );
		self::assertStringContainsString( '00:00:06.920 --> 00:00:08.989', $vtt );
		self::assertStringContainsString( 'Frau Präsidentin!', $vtt );
	}

	public function test_invalid_srt_is_rejected(): void {
		$result = ( new Subtitle_Service( new URL_Resolver() ) )->to_webvtt( 'kein Untertitel' );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'mdb_subtitle_format', $result->get_error_code() );
	}
}

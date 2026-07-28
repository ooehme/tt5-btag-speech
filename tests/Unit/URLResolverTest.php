<?php

declare(strict_types=1);

namespace MDB\Tests;

use InvalidArgumentException;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;

final class URLResolverTest extends TestCase {
	public function test_verified_urls(): void {
		$resolver = new URL_Resolver();
		self::assertSame(
			'https://www.bundestag.de/mediathek/video?videoid=7654763',
			$resolver->video_url( '7654763' )
		);
		self::assertSame(
			'https://webtv.bundestag.de/pservices/player/embed/nokey?a=144277506&c=7654763',
			$resolver->embed_url( '7654763' )
		);
		self::assertSame(
			'https://cldf-od.r53.cdn.tv1.eu/1000153copo/ondemand/app144277506/145293313/7654763/7654763_h264_1920_1080_8000kb_baseline_de_8000.mp4?fdl=1',
			$resolver->download_url( '7654763' )
		);
	}

	public function test_speaker_id_is_dynamic(): void {
		$url = ( new URL_Resolver() )->list_url( '99999', '88888 OR 99999' );
		self::assertStringContainsString( 'rednerId=99999', $url );
		self::assertStringContainsString( 'rednerIds=442354%2388888%20OR%2099999', $url );
		self::assertStringNotContainsString( '12404', $url );
	}

	public function test_verified_speaker_filter(): void {
		$url = ( new URL_Resolver() )->list_url( '12404', '21244 OR 12404' );
		self::assertStringContainsString( 'rednerIds=442354%2321244%20OR%2012404', $url );
	}

	public function test_host_allowlist_is_exact(): void {
		$resolver = new URL_Resolver();
		self::assertTrue( $resolver->is_allowed_url( 'https://cldf-od.r53.cdn.tv1.eu/video.mp4' ) );
		self::assertFalse( $resolver->is_allowed_url( 'http://www.bundestag.de/video' ) );
		self::assertFalse( $resolver->is_allowed_url( 'https://www.bundestag.de:8443/video' ) );
		self::assertFalse( $resolver->is_allowed_url( 'https://bundestag.de.attacker.test/video' ) );
	}

	public function test_invalid_id_is_rejected(): void {
		try {
			( new URL_Resolver() )->video_url( '7/../../etc' );
			self::fail( 'Invalid video ID accepted.' );
		} catch ( InvalidArgumentException $exception ) {
			self::assertStringContainsString( 'Invalid video ID', $exception->getMessage() );
		}
	}
}

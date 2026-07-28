<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\List_Parser;
use MDB\BundestagSpeeches\URL_Resolver;
use MDB\BundestagSpeeches\Video_Parser;
use PHPUnit\Framework\TestCase;

final class LiveEndpointsTest extends TestCase {
	public function test_live_endpoints_when_enabled(): void {
		if ( '1' !== getenv( 'MDB_RUN_LIVE_TESTS' ) ) {
			self::assertTrue( true );
			return;
		}
		if ( ! function_exists( 'curl_init' ) && ! in_array( 'https', stream_get_wrappers(), true ) ) {
			$this->markTestSkipped( 'No HTTPS transport is available in this PHP runtime.' );
			return;
		}

		$urls  = new URL_Resolver();
		$list  = $this->fetch( $urls->list_url( '12404', '21244 OR 12404' ) );
		$video = $this->fetch( $urls->video_url( '7654763' ) );

		self::assertTrue( is_string( $list ) && '' !== $list, 'Live speech list unavailable.' );
		self::assertTrue( is_string( $video ) && '' !== $video, 'Live video page unavailable.' );
		self::assertTrue( count( ( new List_Parser() )->parse( $list ) ) > 0 );
		self::assertSame( '86. Sitzung', ( new Video_Parser() )->parse( $video )['session'] );
	}

	private function fetch( string $url ): string|false {
		if ( function_exists( 'curl_init' ) ) {
			$handle = curl_init( $url );
			curl_setopt_array(
				$handle,
				array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_TIMEOUT        => 20,
					CURLOPT_USERAGENT      => 'MDB Bundestagsreden Integration Test',
				)
			);
			$result = curl_exec( $handle );
			curl_close( $handle );
			return is_string( $result ) ? $result : false;
		}

		$context = stream_context_create(
			array(
				'http' => array(
					'timeout'    => 20,
					'user_agent' => 'MDB Bundestagsreden Integration Test',
				),
			)
		);
		return @file_get_contents( $url, false, $context );
	}
}

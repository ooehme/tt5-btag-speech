<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\MP4_Validator;
use MDB\BundestagSpeeches\Settings;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class MP4ValidatorTest extends TestCase {
	public function test_accepts_only_bounded_mp4(): void {
		$GLOBALS['mdb_test_options'][ Settings::OPTION ] = array( 'max_file_size' => 10 );
		$GLOBALS['mdb_http_head'] = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array(
				'content-type'   => 'video/mp4',
				'content-length' => '1048576',
			),
		);

		$result = $this->validator()->validate( $this->url() );
		self::assertSame( 'video/mp4', $result['mime'] );
		self::assertSame( 1048576, $result['size'] );
	}

	public function test_range_get_fallback_reads_total_size(): void {
		$GLOBALS['mdb_test_options'][ Settings::OPTION ] = array( 'max_file_size' => 10 );
		$GLOBALS['mdb_http_head'] = new WP_Error( 'head', 'HEAD unsupported' );
		$GLOBALS['mdb_http_get']  = array(
			'response' => array( 'code' => 206 ),
			'headers'  => array(
				'content-type'   => 'video/mp4; charset=binary',
				'content-length' => '1',
				'content-range'  => 'bytes 0-0/5000000',
			),
		);

		$result = $this->validator()->validate( $this->url() );
		self::assertSame( 5000000, $result['size'] );
		self::assertSame( 'bytes=0-0', $GLOBALS['mdb_last_http_get_args']['headers']['Range'] );
		self::assertSame( 1, $GLOBALS['mdb_last_http_get_args']['limit_response_size'] );
	}

	public function test_rejects_wrong_mime_and_oversize(): void {
		$GLOBALS['mdb_test_options'][ Settings::OPTION ] = array( 'max_file_size' => 1 );
		$GLOBALS['mdb_http_head'] = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array(
				'content-type'   => 'text/html',
				'content-length' => '100',
			),
		);
		self::assertInstanceOf( WP_Error::class, $this->validator()->validate( $this->url() ) );

		$GLOBALS['mdb_http_head']['headers']['content-type']   = 'video/mp4';
		$GLOBALS['mdb_http_head']['headers']['content-length'] = '2000000';
		$result = $this->validator()->validate( $this->url() );
		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'mdb_mp4_too_large', $result->get_error_code() );
	}

	private function validator(): MP4_Validator {
		return new MP4_Validator( new Settings(), new URL_Resolver() );
	}

	private function url(): string {
		return ( new URL_Resolver() )->download_url( '7654763' );
	}
}

<?php

declare(strict_types=1);

namespace PHPUnit\Framework;

if ( ! class_exists( TestCase::class ) ) {
	abstract class TestCase {
		public static function assertTrue( mixed $actual, string $message = '' ): void {
			if ( true !== $actual ) {
				throw new \RuntimeException( $message ?: 'Failed asserting that value is true.' );
			}
		}

		public static function assertFalse( mixed $actual, string $message = '' ): void {
			if ( false !== $actual ) {
				throw new \RuntimeException( $message ?: 'Failed asserting that value is false.' );
			}
		}

		public static function assertSame( mixed $expected, mixed $actual, string $message = '' ): void {
			if ( $expected !== $actual ) {
				throw new \RuntimeException( $message ?: 'Values are not identical: ' . var_export( $actual, true ) );
			}
		}

		public static function assertCount( int $expected, mixed $actual, string $message = '' ): void {
			if ( ! is_countable( $actual ) || count( $actual ) !== $expected ) {
				throw new \RuntimeException( $message ?: 'Unexpected item count.' );
			}
		}

		public static function assertStringContainsString( string $needle, string $haystack, string $message = '' ): void {
			if ( ! str_contains( $haystack, $needle ) ) {
				throw new \RuntimeException( $message ?: 'String does not contain expected text: ' . $needle );
			}
		}

		public static function assertStringNotContainsString( string $needle, string $haystack, string $message = '' ): void {
			if ( str_contains( $haystack, $needle ) ) {
				throw new \RuntimeException( $message ?: 'String contains unexpected text: ' . $needle );
			}
		}

		public static function assertInstanceOf( string $expected, mixed $actual, string $message = '' ): void {
			if ( ! $actual instanceof $expected ) {
				throw new \RuntimeException( $message ?: 'Object is not an instance of ' . $expected );
			}
		}

		public static function fail( string $message = '' ): void {
			throw new \RuntimeException( $message ?: 'Test failed.' );
		}

		public function markTestSkipped( string $message = '' ): void {}
	}
}

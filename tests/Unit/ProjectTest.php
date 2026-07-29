<?php

declare(strict_types=1);

namespace MDB\Tests;

use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase {
	public function test_required_components_are_separate_files(): void {
		$classes = array(
			'admin',
			'article-image-importer',
			'article-parser',
			'blocks',
			'block-renderer',
			'cli',
			'cron',
			'download-service',
			'list-parser',
			'mp4-validator',
			'plugin',
			'query-display',
			'release-updater',
			'settings',
			'source-client',
			'speech-repository',
			'speech-video-renderer',
			'synchronizer',
			'title-display',
			'url-resolver',
			'video-parser',
		);
		foreach ( $classes as $class ) {
			self::assertTrue( is_file( MDB_SPEECHES_DIR . 'includes/class-' . $class . '.php' ), 'Missing class file: ' . $class );
		}
	}

	public function test_block_metadata_and_release_versions_are_consistent(): void {
		foreach ( array( 'speech-video', 'speech-topic', 'speech-session', 'speech-source-link' ) as $block ) {
			$metadata = json_decode( (string) file_get_contents( MDB_SPEECHES_DIR . 'blocks/' . $block . '/block.json' ), true );
			self::assertSame( '1.1.3', $metadata['version'] );
			self::assertSame( 3, $metadata['apiVersion'] );
			self::assertTrue( in_array( 'postId', $metadata['usesContext'], true ) );
		}

		$plugin  = (string) file_get_contents( MDB_SPEECHES_DIR . 'mdb-bundestag-speeches.php' );
		$readme  = (string) file_get_contents( MDB_SPEECHES_DIR . 'readme.txt' );
		$release = (string) file_get_contents( MDB_SPEECHES_DIR . '.github/workflows/release.yml' );
		self::assertStringContainsString( '* Version:           1.1.3', $plugin );
		self::assertStringContainsString( 'Stable tag: 1.1.3', $readme );
		self::assertStringContainsString( 'mdb-bundestag-speeches-${version}.zip', $release );
	}
}

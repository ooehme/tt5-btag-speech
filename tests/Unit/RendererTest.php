<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Block_Renderer;
use MDB\BundestagSpeeches\Speech_Video_Renderer;
use PHPUnit\Framework\TestCase;
use WP_Block;

final class RendererTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_source_url'        => 'https://www.bundestag.de/mediathek/video?videoid=7654763',
				'_mdb_topic'             => 'TOP 24',
				'_mdb_session'           => '86. Sitzung',
				'_mdb_attachment_id'     => 90,
				'_mdb_article_image_url' => 'https://www.bundestag.de/resource/image/1184450/article.jpg',
			),
		);
		$GLOBALS['mdb_test_attachments'][90] = 'https://example.test/uploads/rede.mp4';
		$GLOBALS['mdb_test_attachments'][91] = 'https://example.test/uploads/article.jpg';
		$GLOBALS['mdb_test_thumbnails'][17]  = 91;
		$GLOBALS['mdb_test_titles'][17]      = 'Cybersicherheit beraten';
	}

	public function test_click_to_load_uses_local_video_and_featured_image(): void {
		$this->setUp();
		$html = ( new Speech_Video_Renderer() )->render(
			array( 'display' => 'click_to_load' ),
			'',
			new WP_Block( array( 'postId' => 17 ) )
		);

		self::assertStringContainsString( 'data-mdb-src="https://example.test/uploads/rede.mp4"', $html );
		self::assertStringContainsString( 'uploads/article.jpg', $html );
		self::assertStringNotContainsString( '<iframe', $html );
		self::assertStringNotContainsString( 'data-mdb-kind', $html );
	}

	public function test_direct_player_uses_featured_image_as_poster(): void {
		$this->setUp();
		$html = ( new Speech_Video_Renderer() )->render(
			array( 'display' => 'direct', 'aspectRatio' => '16/9' ),
			'',
			new WP_Block( array( 'postId' => 17 ) )
		);

		self::assertStringContainsString( '<video', $html );
		self::assertStringContainsString( 'poster="https://example.test/uploads/article.jpg"', $html );
		self::assertStringContainsString( '--mdb-speech-aspect-ratio:16/9', $html );
	}

	public function test_direct_player_falls_back_to_remote_article_image(): void {
		$this->setUp();
		unset( $GLOBALS['mdb_test_thumbnails'][17] );
		$html = ( new Speech_Video_Renderer() )->render(
			array( 'display' => 'direct' ),
			'',
			new WP_Block( array( 'postId' => 17 ) )
		);

		self::assertStringContainsString(
			'poster="https://www.bundestag.de/resource/image/1184450/article.jpg"',
			$html
		);
		self::assertStringNotContainsString( 'uploads/article.jpg', $html );
	}

	public function test_missing_local_video_and_field_blocks(): void {
		$this->setUp();
		$GLOBALS['mdb_test_meta'][17]['_mdb_attachment_id'] = '';
		$block = new WP_Block( array( 'postId' => 17 ) );
		$html  = ( new Speech_Video_Renderer() )->render( array(), '', $block );
		self::assertStringContainsString( 'kein Video', $html );

		$fields = new Block_Renderer();
		self::assertStringContainsString( 'TOP 24', $fields->render_topic( array(), '', $block ) );
		self::assertStringContainsString( '86. Sitzung', $fields->render_session( array(), '', $block ) );
		self::assertStringContainsString( 'videoid=7654763', $fields->render_source_link( array(), '', $block ) );
	}
}

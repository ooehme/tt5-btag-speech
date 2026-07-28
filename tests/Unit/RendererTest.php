<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Block_Renderer;
use MDB\BundestagSpeeches\Speech_Video_Renderer;
use MDB\BundestagSpeeches\URL_Resolver;
use PHPUnit\Framework\TestCase;
use WP_Block;

final class RendererTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mdb_test_meta'] = array(
			17 => array(
				'_mdb_embed_url'   => 'https://webtv.bundestag.de/pservices/player/embed/nokey?a=144277506&c=7654763',
				'_mdb_source_url'  => 'https://www.bundestag.de/mediathek/video?videoid=7654763',
				'_mdb_topic'       => 'TOP 24',
				'_mdb_session'     => '86. Sitzung',
				'_mdb_attachment_id' => '',
				'_mdb_article_image_url' => 'https://www.bundestag.de/resource/image/1184450/article.jpg',
				'_mdb_article_image_id' => '',
			),
		);
		$GLOBALS['mdb_test_titles'][17] = 'Testrede';
	}

	public function test_click_to_load_does_not_emit_player(): void {
		$this->setUp();
		$html = ( new Speech_Video_Renderer( new URL_Resolver() ) )->render(
			array( 'source' => 'embed', 'display' => 'click_to_load' ),
			'',
			new WP_Block( array( 'postId' => 17 ) )
		);
		self::assertStringContainsString( 'data-mdb-src=', $html );
		self::assertStringNotContainsString( '<iframe', $html );
		self::assertStringContainsString( 'Deutscher Bundestag', $html );
	}

	public function test_direct_embed_has_sandbox(): void {
		$this->setUp();
		$html = ( new Speech_Video_Renderer( new URL_Resolver() ) )->render(
			array( 'source' => 'embed', 'display' => 'direct', 'aspectRatio' => '16/9' ),
			'',
			new WP_Block( array( 'postId' => 17 ) )
		);
		self::assertStringContainsString( '<iframe', $html );
		self::assertStringContainsString( 'sandbox="allow-same-origin allow-scripts allow-forms allow-modals allow-popups"', $html );
		self::assertStringContainsString( '--mdb-speech-aspect-ratio:16/9', $html );
	}

	public function test_article_image_is_used_as_remote_or_local_poster(): void {
		$this->setUp();
		$renderer = new Speech_Video_Renderer( new URL_Resolver() );
		$context  = new WP_Block( array( 'postId' => 17, 'mdb/useArticleImage' => true ) );
		$html     = $renderer->render(
			array( 'source' => 'embed', 'display' => 'click_to_load' ),
			'',
			$context
		);
		self::assertStringContainsString( 'resource/image/1184450/article.jpg', $html );

		$GLOBALS['mdb_test_meta'][17]['_mdb_article_image_id'] = 91;
		$GLOBALS['mdb_test_attachments'][91] = 'https://example.test/uploads/article-local.jpg';
		$html = $renderer->render(
			array( 'source' => 'embed', 'display' => 'click_to_load' ),
			'',
			$context
		);
		self::assertStringContainsString( 'uploads/article-local.jpg', $html );
		self::assertStringNotContainsString( 'resource/image/1184450/article.jpg', $html );
	}

	public function test_local_fallback_and_field_blocks(): void {
		$this->setUp();
		$block = new WP_Block( array( 'postId' => 17 ) );
		$html  = ( new Speech_Video_Renderer( new URL_Resolver() ) )->render( array( 'source' => 'local' ), '', $block );
		self::assertStringContainsString( 'kein Video verfügbar', $html );

		$fields = new Block_Renderer();
		self::assertStringContainsString( 'TOP 24', $fields->render_topic( array(), '', $block ) );
		self::assertStringContainsString( '86. Sitzung', $fields->render_session( array(), '', $block ) );
		self::assertStringContainsString( 'videoid=7654763', $fields->render_source_link( array(), '', $block ) );
	}
}

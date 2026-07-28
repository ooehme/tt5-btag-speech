<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Query_Display;
use MDB\BundestagSpeeches\Title_Display;
use PHPUnit\Framework\TestCase;
use WP_Block;

final class TitleDisplayTest extends TestCase {
	public function test_speaker_suffix_is_removed_from_title_and_markup(): void {
		self::assertSame(
			'86. Sitzung vom 25.06.2026, TOP 24',
			Title_Display::remove_speaker_suffix(
				'86. Sitzung vom 25.06.2026, TOP 24: Rede von Steffen Janich'
			)
		);
		self::assertSame(
			'<h2><a href="/rede">86. Sitzung, TOP 24</a></h2>',
			Title_Display::remove_speaker_suffix(
				'<h2><a href="/rede">86. Sitzung, TOP 24: Rede von Steffen Janich</a></h2>'
			)
		);
	}

	public function test_other_colons_and_disabled_context_remain_unchanged(): void {
		$title = '86. Sitzung: Beratung zu TOP 24';
		self::assertSame( $title, Title_Display::remove_speaker_suffix( $title ) );

		$markup = '<h2>TOP 24: Rede von Steffen Janich</h2>';
		$display = new Title_Display();
		self::assertSame(
			$markup,
			$display->render(
				$markup,
				array( 'attrs' => array( 'className' => Query_Display::KEEP_SPEAKER_CLASS ) ),
				new WP_Block()
			)
		);
		self::assertSame(
			'<h2>TOP 24</h2>',
			$display->render(
				$markup,
				array( 'attrs' => array( 'className' => Query_Display::REMOVE_SPEAKER_CLASS ) ),
				new WP_Block()
			)
		);
	}

	public function test_article_title_is_used_with_normal_title_as_fallback(): void {
		$GLOBALS['mdb_test_meta'][17]['_mdb_article_title'] = 'Regierungsentwurf zur Cybersicherheit beraten';
		$display = new Title_Display();
		$markup  = '<h2><a href="/rede">86. Sitzung: Rede von Steffen Janich</a></h2>';

		self::assertSame(
			'<h2><a href="/rede">Regierungsentwurf zur Cybersicherheit beraten</a></h2>',
			$display->render(
				$markup,
				array( 'attrs' => array( 'className' => Query_Display::USE_ARTICLE_TITLE_CLASS ) ),
				new WP_Block( array( 'postId' => 17 ) )
			)
		);
		self::assertSame(
			$markup,
			$display->render(
				$markup,
				array( 'attrs' => array( 'className' => Query_Display::USE_ARTICLE_TITLE_CLASS ) ),
				new WP_Block( array( 'postId' => 18 ) )
			)
		);
	}

	public function test_unconfigured_existing_query_defaults_are_applied_to_child_blocks(): void {
		$display = new Query_Display();
		$parsed = $display->render_block_data(
			array(
				'blockName'   => 'core/query',
				'attrs'       => array(
					'namespace' => 'mdb/speeches',
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-template',
						'attrs'       => array(),
						'innerBlocks' => array(
							array( 'blockName' => 'core/post-title', 'attrs' => array() ),
							array( 'blockName' => 'mdb/speech-video', 'attrs' => array() ),
						),
					),
				),
			)
		);
		$children = $parsed['innerBlocks'][0]['innerBlocks'];

		self::assertStringContainsString(
			Query_Display::REMOVE_SPEAKER_CLASS,
			$children[0]['attrs']['className']
		);
		self::assertStringContainsString(
			Query_Display::USE_ARTICLE_TITLE_CLASS,
			$children[0]['attrs']['className']
		);
		self::assertTrue( $children[1]['attrs']['useArticleImage'] );
	}

	public function test_direct_child_settings_override_legacy_query_options(): void {
		$parsed = ( new Query_Display() )->render_block_data(
			array(
				'blockName'   => 'core/query',
				'attrs'       => array(
					'namespace' => 'mdb/speeches',
					'query'     => array(
						Query_Display::REMOVE_SPEAKER_ATTRIBUTE    => true,
						Query_Display::USE_ARTICLE_TITLE_ATTRIBUTE => true,
						Query_Display::USE_ARTICLE_IMAGE_ATTRIBUTE => true,
					),
				),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/post-title',
						'attrs'     => array(
							'className' => Query_Display::KEEP_SPEAKER_CLASS . ' ' . Query_Display::USE_SOURCE_TITLE_CLASS,
						),
					),
					array(
						'blockName' => 'mdb/speech-video',
						'attrs'     => array( 'useArticleImage' => false ),
					),
				),
			)
		);

		self::assertSame(
			Query_Display::KEEP_SPEAKER_CLASS . ' ' . Query_Display::USE_SOURCE_TITLE_CLASS,
			$parsed['innerBlocks'][0]['attrs']['className']
		);
		self::assertFalse( $parsed['innerBlocks'][1]['attrs']['useArticleImage'] );
	}
}

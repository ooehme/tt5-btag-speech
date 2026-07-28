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
				array(),
				new WP_Block( array( Query_Display::REMOVE_SPEAKER_CONTEXT => false ) )
			)
		);
		self::assertSame(
			'<h2>TOP 24</h2>',
			$display->render(
				$markup,
				array(),
				new WP_Block( array( Query_Display::REMOVE_SPEAKER_CONTEXT => true ) )
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
				array(),
				new WP_Block(
					array(
						'postId'                                  => 17,
						Query_Display::USE_ARTICLE_TITLE_CONTEXT => true,
					)
				)
			)
		);
		self::assertSame(
			$markup,
			$display->render(
				$markup,
				array(),
				new WP_Block(
					array(
						'postId'                                  => 18,
						Query_Display::USE_ARTICLE_TITLE_CONTEXT => true,
					)
				)
			)
		);
	}

	public function test_query_attributes_provide_display_context(): void {
		$display = new Query_Display();
		$query   = $display->block_type_args( array(), 'core/query' );
		$title   = $display->block_type_args( array(), 'core/post-title' );

		self::assertSame(
			false,
			$query['attributes'][ Query_Display::REMOVE_SPEAKER_ATTRIBUTE ]['default']
		);
		self::assertSame(
			Query_Display::USE_ARTICLE_TITLE_ATTRIBUTE,
			$query['provides_context'][ Query_Display::USE_ARTICLE_TITLE_CONTEXT ]
		);
		self::assertSame(
			Query_Display::USE_ARTICLE_IMAGE_ATTRIBUTE,
			$query['provides_context'][ Query_Display::USE_ARTICLE_IMAGE_CONTEXT ]
		);
		self::assertTrue(
			in_array( Query_Display::REMOVE_SPEAKER_CONTEXT, $title['uses_context'], true )
		);
		self::assertTrue(
			in_array( Query_Display::USE_ARTICLE_TITLE_CONTEXT, $title['uses_context'], true )
		);
	}
}

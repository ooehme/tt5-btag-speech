<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Video_Parser;
use PHPUnit\Framework\TestCase;

final class VideoDateParserTest extends TestCase {
	public function test_publication_date_prefers_speech_date_over_generic_meta_tag(): void {
		$result = ( new Video_Parser() )->parse(
			'<html><head><meta name="date" content="07.08.2025">'
			. '<title>Deutscher Bundestag - Sitzung vom 08.07.2026, TOP 5, ZP 2: Rede von Steffen Janich</title>'
			. '</head><body></body></html>'
		);

		self::assertSame( '08.07.2026', $result['date'] );
	}

	public function test_generic_meta_date_is_not_used_without_a_speech_date(): void {
		$result = ( new Video_Parser() )->parse(
			'<html><head><meta name="date" content="07.08.2025">'
			. '<title>Deutscher Bundestag - Rede von Steffen Janich</title>'
			. '</head><body></body></html>'
		);

		self::assertSame( '', $result['date'] );
	}

	public function test_document_link_is_not_mistaken_for_an_article(): void {
		$result = ( new Video_Parser() )->parse(
			'<main><h1>Sitzung vom 08.07.2026, TOP 5: Rede von Steffen Janich</h1>'
			. '<a href="/dokumente">zum Artikel</a>'
			. '<a href="/dokumente/textarchiv/">Dokumente</a>'
			. '<div data-videoid="7649404"></div></main>'
		);

		self::assertSame( '', $result['article_url'] );
	}
}

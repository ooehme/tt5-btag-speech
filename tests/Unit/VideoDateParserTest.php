<?php

declare(strict_types=1);

namespace MDB\Tests;

use MDB\BundestagSpeeches\Video_Parser;
use PHPUnit\Framework\TestCase;

final class VideoDateParserTest extends TestCase {
	public function test_publication_date_prefers_date_meta_tag(): void {
		$result = ( new Video_Parser() )->parse(
			'<html><head><meta name="date" content="07.08.2025">'
			. '<title>Deutscher Bundestag - 86. Sitzung vom 25.06.2026: Rede von Beispiel</title>'
			. '</head><body></body></html>'
		);

		self::assertSame( '07.08.2025', $result['date'] );
	}
}

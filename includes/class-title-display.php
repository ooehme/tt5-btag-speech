<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Block;

final class Title_Display {
	public function register(): void {
		add_filter( 'render_block_core/post-title', array( $this, 'render' ), 10, 3 );
	}

	/**
	 * @param array<string,mixed> $parsed_block Parsed block data.
	 */
	public function render( string $content, array $parsed_block, WP_Block $block ): string {
		if ( ! empty( $block->context[ Query_Display::USE_ARTICLE_TITLE_CONTEXT ] ) ) {
			$post_id       = absint( $block->context['postId'] ?? 0 );
			$article_title = (string) get_post_meta( $post_id, '_mdb_article_title', true );
			if ( '' !== $article_title ) {
				$content = self::replace_title( $content, $article_title );
			}
		}

		return ! empty( $block->context[ Query_Display::REMOVE_SPEAKER_CONTEXT ] )
			? self::remove_speaker_suffix( $content )
			: $content;
	}

	public static function replace_title( string $markup, string $title ): string {
		$replaced = preg_replace(
			'~(?<=>)[^<>]*(?=</(?:a|h[1-6])>)~u',
			esc_html( $title ),
			$markup,
			1
		);

		return null === $replaced ? $markup : $replaced;
	}

	public static function remove_speaker_suffix( string $title_or_markup ): string {
		$cleaned = preg_replace(
			'~\s*:\s*Rede\s+von\s+[^<]+(?=</(?:a|h[1-6])>|$)~iu',
			'',
			$title_or_markup,
			1
		);

		return null === $cleaned ? $title_or_markup : $cleaned;
	}
}

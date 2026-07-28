<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Query_Display {
	public const REMOVE_SPEAKER_ATTRIBUTE    = 'mdbRemoveSpeakerFromTitle';
	public const USE_ARTICLE_TITLE_ATTRIBUTE = 'mdbUseArticleTitle';
	public const USE_ARTICLE_IMAGE_ATTRIBUTE = 'mdbUseArticleImage';

	public const REMOVE_SPEAKER_CLASS    = 'mdb-speech-title--remove-speaker';
	public const KEEP_SPEAKER_CLASS      = 'mdb-speech-title--keep-speaker';
	public const USE_ARTICLE_TITLE_CLASS = 'mdb-speech-title--article-title';
	public const USE_SOURCE_TITLE_CLASS  = 'mdb-speech-title--source-title';

	public function register(): void {
		add_filter( 'render_block_data', array( $this, 'render_block_data' ) );
	}

	/**
	 * Übernimmt alte Query-Optionen für Kindblöcke, die noch keine eigenen
	 * Einstellungen besitzen.
	 *
	 * @param array<string,mixed> $parsed_block Parsed block data.
	 * @return array<string,mixed>
	 */
	public function render_block_data( array $parsed_block ): array {
		if (
			'core/query' !== ( $parsed_block['blockName'] ?? '' )
			|| 'mdb/speeches' !== ( $parsed_block['attrs']['namespace'] ?? '' )
		) {
			return $parsed_block;
		}

		$attributes = is_array( $parsed_block['attrs'] ?? null ) ? $parsed_block['attrs'] : array();
		$query      = is_array( $attributes['query'] ?? null ) ? $attributes['query'] : array();
		$options    = array();

		foreach ( $this->option_attributes() as $attribute ) {
			$options[ $attribute ] = array_key_exists( $attribute, $query )
				? (bool) $query[ $attribute ]
				: ( array_key_exists( $attribute, $attributes ) ? (bool) $attributes[ $attribute ] : true );
		}

		$parsed_block['innerBlocks'] = $this->decorate_blocks(
			is_array( $parsed_block['innerBlocks'] ?? null ) ? $parsed_block['innerBlocks'] : array(),
			$options
		);

		return $parsed_block;
	}

	/**
	 * @param array<int,array<string,mixed>> $blocks Parsed child blocks.
	 * @param array<string,bool>             $options Legacy display options.
	 * @return array<int,array<string,mixed>>
	 */
	private function decorate_blocks( array $blocks, array $options ): array {
		foreach ( $blocks as &$block ) {
			if ( 'core/query' === ( $block['blockName'] ?? '' ) ) {
				continue;
			}

			$block['attrs'] = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( 'core/post-title' === ( $block['blockName'] ?? '' ) ) {
				$block['attrs']['className'] = $this->legacy_title_classes(
					(string) ( $block['attrs']['className'] ?? '' ),
					$options
				);
			}
			if (
				'mdb/speech-video' === ( $block['blockName'] ?? '' )
				&& ! array_key_exists( 'useArticleImage', $block['attrs'] )
			) {
				$block['attrs']['useArticleImage'] = $options[ self::USE_ARTICLE_IMAGE_ATTRIBUTE ];
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->decorate_blocks( $block['innerBlocks'], $options );
			}
		}
		unset( $block );

		return $blocks;
	}

	/**
	 * @param array<string,bool> $options Legacy display options.
	 */
	private function legacy_title_classes( string $class_name, array $options ): string {
		$classes = preg_split( '/\s+/', trim( $class_name ) ) ?: array();
		if (
			! in_array( self::REMOVE_SPEAKER_CLASS, $classes, true )
			&& ! in_array( self::KEEP_SPEAKER_CLASS, $classes, true )
		) {
			$classes[] = $options[ self::REMOVE_SPEAKER_ATTRIBUTE ]
				? self::REMOVE_SPEAKER_CLASS
				: self::KEEP_SPEAKER_CLASS;
		}
		if (
			! in_array( self::USE_ARTICLE_TITLE_CLASS, $classes, true )
			&& ! in_array( self::USE_SOURCE_TITLE_CLASS, $classes, true )
		) {
			$classes[] = $options[ self::USE_ARTICLE_TITLE_ATTRIBUTE ]
				? self::USE_ARTICLE_TITLE_CLASS
				: self::USE_SOURCE_TITLE_CLASS;
		}

		return trim( implode( ' ', array_unique( array_filter( $classes ) ) ) );
	}

	/**
	 * @return list<string>
	 */
	private function option_attributes(): array {
		return array(
			self::REMOVE_SPEAKER_ATTRIBUTE,
			self::USE_ARTICLE_TITLE_ATTRIBUTE,
			self::USE_ARTICLE_IMAGE_ATTRIBUTE,
		);
	}
}

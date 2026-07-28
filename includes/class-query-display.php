<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Query_Display {
	public const REMOVE_SPEAKER_ATTRIBUTE   = 'mdbRemoveSpeakerFromTitle';
	public const USE_ARTICLE_TITLE_ATTRIBUTE = 'mdbUseArticleTitle';
	public const USE_ARTICLE_IMAGE_ATTRIBUTE = 'mdbUseArticleImage';

	public const REMOVE_SPEAKER_CONTEXT   = 'mdb/removeSpeakerFromTitle';
	public const USE_ARTICLE_TITLE_CONTEXT = 'mdb/useArticleTitle';
	public const USE_ARTICLE_IMAGE_CONTEXT = 'mdb/useArticleImage';

	public function register(): void {
		add_filter( 'register_block_type_args', array( $this, 'block_type_args' ), 10, 2 );
		add_filter( 'render_block_data', array( $this, 'render_block_data' ) );
	}

	/**
	 * @param array<string,mixed> $args Block type arguments.
	 * @return array<string,mixed>
	 */
	public function block_type_args( array $args, string $block_type ): array {
		if ( 'core/query' === $block_type ) {
			$args['attributes']       ??= array();
			$args['provides_context'] ??= array();
			foreach ( $this->contexts() as $context => $attribute ) {
				$args['attributes'][ $attribute ] = array(
					'type'    => 'boolean',
					'default' => false,
				);
				$args['provides_context'][ $context ] = $attribute;
			}
		}

		if ( 'core/post-title' === $block_type ) {
			$args['uses_context'] ??= array();
			$args['uses_context']   = array_values(
				array_unique(
					array_merge(
						$args['uses_context'],
						array( self::REMOVE_SPEAKER_CONTEXT, self::USE_ARTICLE_TITLE_CONTEXT )
					)
				)
			);
		}

		return $args;
	}

	/**
	 * Kopiert gespeicherte Query-Optionen in registrierte Attribute, damit
	 * WordPress sie als Block-Kontext an die Kindblöcke weitergibt.
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

		foreach ( $this->contexts() as $attribute ) {
			$attributes[ $attribute ] = array_key_exists( $attribute, $query )
				? (bool) $query[ $attribute ]
				: ! empty( $attributes[ $attribute ] );
		}

		$parsed_block['attrs'] = $attributes;

		return $parsed_block;
	}

	/**
	 * @return array<string,string>
	 */
	private function contexts(): array {
		return array(
			self::REMOVE_SPEAKER_CONTEXT    => self::REMOVE_SPEAKER_ATTRIBUTE,
			self::USE_ARTICLE_TITLE_CONTEXT => self::USE_ARTICLE_TITLE_ATTRIBUTE,
			self::USE_ARTICLE_IMAGE_CONTEXT => self::USE_ARTICLE_IMAGE_ATTRIBUTE,
		);
	}
}

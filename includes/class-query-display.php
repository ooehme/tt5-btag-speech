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

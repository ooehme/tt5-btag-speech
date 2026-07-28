<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class Article_Parser {
	/**
	 * @return array{article_title:string,article_image_url:string}
	 * @throws Parser_Exception When the article contains no usable metadata.
	 */
	public function parse( string $html ): array {
		$xpath = $this->xpath( $html );
		$title = $this->first_value(
			$xpath,
			array(
				'//main//h1[contains(concat(" ", normalize-space(@class), " "), " bt-artikel__title ")]',
				'//main//article//h1',
				'//main//h1',
				'//h1',
				'//meta[@property="og:title"]',
				'//title',
			)
		);
		$title = preg_replace( '/^Deutscher Bundestag\s*[-–]\s*/u', '', $title ) ?? $title;

		$image_url = $this->first_value(
			$xpath,
			array(
				'//meta[@property="og:image"]',
				'//meta[@name="twitter:image"]',
				'//main//article//figure//img',
				'//main//figure//img',
			),
			true
		);

		if ( '' === $title && '' === $image_url ) {
			throw new Parser_Exception(
				'Auf der verlinkten Bundestag-Artikelseite wurden weder Titel noch Titelbild gefunden. '
				. 'Möglicherweise hat sich die HTML-Struktur geändert.'
			);
		}

		return array(
			'article_title'     => $title,
			'article_image_url' => $image_url,
		);
	}

	private function xpath( string $html ): DOMXPath {
		if ( ! class_exists( DOMDocument::class ) ) {
			throw new Parser_Exception( 'Die PHP-DOM-Erweiterung wird zum Einlesen der Bundestag-Seiten benötigt.' );
		}

		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$loaded   = $document->loadHTML(
			'<?xml encoding="utf-8" ?>' . $html,
			LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			throw new Parser_Exception( 'Die Artikelseite enthält kein lesbares HTML.' );
		}

		return new DOMXPath( $document );
	}

	/**
	 * @param array<int,string> $queries XPath fallbacks.
	 */
	private function first_value( DOMXPath $xpath, array $queries, bool $image = false ): string {
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );
			if ( false === $nodes || 0 === $nodes->length ) {
				continue;
			}

			$node = $nodes->item( 0 );
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$value = $image ? $this->image_url( $node ) : $this->text_value( $node );
			if ( '' !== $value && ( ! $image || ! $this->is_placeholder_image( $value ) ) ) {
				return $value;
			}
		}

		return '';
	}

	private function is_placeholder_image( string $url ): bool {
		$path = strtolower( (string) ( parse_url( $url, PHP_URL_PATH ) ?? '' ) );
		return str_ends_with( $path, '/adler.png' )
			|| str_ends_with( $path, '/image3x4_small.png' );
	}

	private function text_value( DOMElement $node ): string {
		if ( 'meta' === strtolower( $node->tagName ) ) {
			return $this->normalize( $node->getAttribute( 'content' ) );
		}
		return $this->normalize( (string) $node->textContent );
	}

	private function image_url( DOMElement $node ): string {
		if ( 'meta' === strtolower( $node->tagName ) ) {
			return trim( $node->getAttribute( 'content' ) );
		}

		foreach (
			array(
				'data-img-xl-retina',
				'data-img-xl-normal',
				'data-img-lg-retina',
				'data-img-lg-normal',
				'data-img-md-retina',
				'data-img-md-normal',
				'src',
			) as $attribute
		) {
			$value = trim( $node->getAttribute( $attribute ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	private function normalize( string $value ): string {
		return trim(
			(string) preg_replace(
				'/\s+/u',
				' ',
				html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			)
		);
	}
}

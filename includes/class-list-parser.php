<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class List_Parser {
	/**
	 * @return array<int,array<string,string>>
	 * @throws Parser_Exception When the expected speech links are absent.
	 */
	public function parse( string $html ): array {
		$xpath = $this->xpath( $html );
		$nodes = $xpath->query(
			'//a[contains(@href, "videoid=")]'
			. ' | //*[@data-videoid]'
			. ' | //*[@data-video-id]'
		);

		if ( false === $nodes ) {
			throw new Parser_Exception( 'Die Redenliste konnte nicht durchsucht werden.' );
		}

		$records = array();
		foreach ( $nodes as $node ) {
			$video_id = $this->video_id( $node );
			if ( '' === $video_id || isset( $records[ $video_id ] ) ) {
				continue;
			}

			$card = $this->card( $node );
			$records[ $video_id ] = array(
				'video_id' => $video_id,
				'date'     => $this->first_text( $xpath, $card, array( './/*[contains(concat(" ", normalize-space(@class), " "), " bt-date ")]', './/time' ) ),
				'title'    => $this->first_text( $xpath, $card, array( './/h3', './/h2', './/*[contains(@class, "title")]' ) ),
				'topic'    => $this->first_text( $xpath, $card, array( './/p[@data-presidium]', './/*[contains(@class, "topic")]' ) ),
			);
		}

		if ( array() === $records ) {
			throw new Parser_Exception( 'Die Bundestag-Antwort enthält keine erkennbaren Reden. Möglicherweise hat sich die HTML-Struktur geändert.' );
		}

		return array_values( $records );
	}

	private function xpath( string $html ): DOMXPath {
		if ( ! class_exists( DOMDocument::class ) ) {
			throw new Parser_Exception( 'Die PHP-DOM-Erweiterung wird zum Einlesen der Bundestag-Seiten benötigt.' );
		}

		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$loaded   = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			throw new Parser_Exception( 'Die Redenliste enthält kein lesbares HTML.' );
		}
		return new DOMXPath( $document );
	}

	private function video_id( DOMNode $node ): string {
		if ( ! $node instanceof DOMElement ) {
			return '';
		}

		foreach ( array( 'data-videoid', 'data-video-id' ) as $attribute ) {
			$value = trim( $node->getAttribute( $attribute ) );
			if ( preg_match( '/^\d+$/', $value ) ) {
				return $value;
			}
		}

		$href = html_entity_decode( $node->getAttribute( 'href' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( preg_match( '/(?:[?&]videoid=)(\d+)/i', $href, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	private function card( DOMNode $node ): DOMNode {
		$current = $node;
		for ( $depth = 0; $depth < 6 && null !== $current->parentNode; ++$depth ) {
			if ( $current instanceof DOMElement ) {
				$class = ' ' . preg_replace( '/\s+/', ' ', $current->getAttribute( 'class' ) ) . ' ';
				if ( str_contains( $class, ' bt-slide-content ' ) || str_contains( $class, ' bt-fl-teaser ' ) ) {
					return $current;
				}
			}
			$current = $current->parentNode;
		}
		return $node;
	}

	/**
	 * @param array<int,string> $queries XPath fallbacks.
	 */
	private function first_text( DOMXPath $xpath, DOMNode $context, array $queries ): string {
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query, $context );
			if ( false !== $nodes && $nodes->length > 0 ) {
				$text = $this->normalize( (string) $nodes->item( 0 )?->textContent );
				if ( '' !== $text ) {
					return $text;
				}
			}
		}
		return '';
	}

	private function normalize( string $value ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}
}

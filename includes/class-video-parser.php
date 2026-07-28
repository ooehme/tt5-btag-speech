<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class Video_Parser {
	/**
	 * @return array{video_id:string,title:string,date:string,session:string,topic:string,article_url:string}
	 * @throws Parser_Exception When no reliable title is present.
	 */
	public function parse( string $html ): array {
		$xpath = $this->xpath( $html );
		$title = '';

		foreach (
			array(
				'//*[contains(concat(" ", normalize-space(@class), " "), " m-videos__headline ")]//h1',
				'//main//h1',
				'//h1',
				'//meta[@property="og:title"]',
				'//title',
			) as $query
		) {
			$nodes = $xpath->query( $query );
			if ( false === $nodes || 0 === $nodes->length ) {
				continue;
			}

			$node = $nodes->item( 0 );
			if ( $node instanceof DOMElement && 'meta' === strtolower( $node->tagName ) ) {
				$title = $this->normalize( $node->getAttribute( 'content' ) );
			} else {
				$title = $this->title_without_roofline( $node );
			}

			if ( '' !== $title ) {
				break;
			}
		}

		$title = preg_replace( '/^Deutscher Bundestag\s*[-–]\s*/u', '', $title ) ?? $title;
		if ( '' === $title ) {
			throw new Parser_Exception( 'Auf der Bundestag-Videoseite wurde kein Titel gefunden. Möglicherweise hat sich die HTML-Struktur geändert.' );
		}

		$date = '';
		if ( preg_match( '/\b(\d{1,2}\.\d{1,2}\.\d{4})\b/u', $title, $matches ) ) {
			$date = $matches[1];
		} else {
			$date = $this->first_text(
				$xpath,
				array(
					'//*[contains(concat(" ", normalize-space(@class), " "), " m-videos__roofline ")]',
					'//time',
				)
			);
		}

		$session = preg_match( '/\b(\d+\.\s*Sitzung)\b/ui', $title, $matches ) ? $this->normalize( $matches[1] ) : '';
		$topic   = preg_match( '/\b(TOP\s+[A-Za-z0-9.\-\/]+(?:\s+[A-Za-z0-9.\-\/]+)?)\s*:/ui', $title, $matches )
			? $this->normalize( $matches[1] )
			: $this->breadcrumb_topic( $xpath );
		$video_id   = $this->video_id( $xpath );
		$article_url = $this->article_url( $xpath );

		return array(
			'video_id'   => $video_id,
			'title'      => $title,
			'date'       => $date,
			'session'    => $session,
			'topic'      => $topic,
			'article_url' => $article_url,
		);
	}

	private function article_url( DOMXPath $xpath ): string {
		foreach (
			array(
				'//main//a[contains(translate(normalize-space(.), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "zum artikel")]',
				'//main//a[contains(@href, "/dokumente/textarchiv/")]',
				'//a[contains(@href, "/dokumente/textarchiv/")]',
			) as $query
		) {
			$nodes = $xpath->query( $query );
			if ( false !== $nodes && $nodes->length > 0 && $nodes->item( 0 ) instanceof DOMElement ) {
				$url = trim( $nodes->item( 0 )->getAttribute( 'href' ) );
				if ( '' !== $url ) {
					return $url;
				}
			}
		}

		return '';
	}

	private function video_id( DOMXPath $xpath ): string {
		foreach (
			array(
				'//*[contains(concat(" ", normalize-space(@class), " "), " bt-videoplayer ")]//*[@data-videoid]',
				'//*[@data-playertype="ondemand" and @data-videoid]',
				'//*[@data-videoid]',
			) as $query
		) {
			$nodes = $xpath->query( $query );
			if ( false !== $nodes && $nodes->length > 0 && $nodes->item( 0 ) instanceof DOMElement ) {
				$value = trim( $nodes->item( 0 )->getAttribute( 'data-videoid' ) );
				if ( preg_match( '/^\d+$/', $value ) ) {
					return $value;
				}
			}
		}
		return '';
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
			throw new Parser_Exception( 'Die Videoseite enthält kein lesbares HTML.' );
		}
		return new DOMXPath( $document );
	}

	private function title_without_roofline( ?DOMNode $node ): string {
		if ( null === $node ) {
			return '';
		}

		$copy = $node->cloneNode( true );
		if ( $copy instanceof DOMElement ) {
			$remove = array();
			foreach ( $copy->getElementsByTagName( '*' ) as $child ) {
				if ( $child instanceof DOMElement ) {
					$class = ' ' . preg_replace( '/\s+/', ' ', $child->getAttribute( 'class' ) ) . ' ';
					if ( str_contains( $class, ' m-videos__roofline ' ) ) {
						$remove[] = $child;
					}
				}
			}
			foreach ( $remove as $child ) {
				$child->parentNode?->removeChild( $child );
			}
		}
		return $this->normalize( (string) $copy->textContent );
	}

	/**
	 * @param array<int,string> $queries XPath fallbacks.
	 */
	private function first_text( DOMXPath $xpath, array $queries ): string {
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );
			if ( false !== $nodes && $nodes->length > 0 ) {
				$text = $this->normalize( (string) $nodes->item( 0 )?->textContent );
				if ( '' !== $text ) {
					return $text;
				}
			}
		}
		return '';
	}

	private function breadcrumb_topic( DOMXPath $xpath ): string {
		$nodes = $xpath->query( '//nav[contains(@class, "breadcrumb")]//a[contains(normalize-space(.), "TOP ")]' );
		if ( false === $nodes || 0 === $nodes->length ) {
			return '';
		}
		return $this->normalize( (string) $nodes->item( $nodes->length - 1 )?->textContent );
	}

	private function normalize( string $value ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}
}

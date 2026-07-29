<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Block;

final class Speech_Video_Renderer {
	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function render( array $attributes, string $content, WP_Block $block ): string {
		$attributes = wp_parse_args(
			$attributes,
			array(
				'display'     => 'click_to_load',
				'controls'    => true,
				'autoplay'    => false,
				'muted'       => false,
				'aspectRatio' => '16/9',
			)
		);
		$post_id  = $this->post_id( $block );
		$poster   = $this->poster( $post_id );
		$source   = $this->source( $post_id );
		$subtitle = $this->subtitle( $post_id );
		$wrapper  = get_block_wrapper_attributes( array( 'class' => 'mdb-speech-video' ) );

		if ( 'link' === $attributes['display'] ) {
			return sprintf(
				'<p %1$s><a href="%2$s">%3$s</a></p>',
				$wrapper,
				esc_url( get_permalink( $post_id ) ),
				esc_html__( 'Bundestagsrede ansehen', 'mdb-bundestag-speeches' )
			);
		}

		if ( '' === $source ) {
			return sprintf(
				'<div %1$s><p class="mdb-speech-video__fallback">%2$s</p></div>',
				$wrapper,
				esc_html__( 'Für diese Rede ist derzeit kein Video verfügbar.', 'mdb-bundestag-speeches' )
			);
		}

		$ratio = $this->aspect_ratio( (string) $attributes['aspectRatio'] );
		$frame = 'click_to_load' === $attributes['display']
			? $this->click_to_load( $source, $poster, $subtitle, $attributes, $ratio, $post_id )
			: $this->direct( $source, $poster, $subtitle, $attributes, $ratio );

		return sprintf( '<div %1$s>%2$s</div>', $wrapper, $frame );
	}

	private function source( int $post_id ): string {
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_attachment_id', true );
		return $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';
	}

	private function poster( int $post_id ): string {
		$url = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}

		return (string) get_post_meta( $post_id, '_mdb_article_image_url', true );
	}

	private function subtitle( int $post_id ): string {
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_subtitle_attachment_id', true );
		return $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private function direct( string $source, string $poster, string $subtitle, array $attributes, string $ratio ): string {
		return sprintf(
			'<div class="mdb-speech-video__frame" style="%1$s">%2$s</div>',
			esc_attr( $this->frame_style( $ratio ) ),
			$this->video( $source, $poster, $subtitle, $attributes )
		);
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private function click_to_load( string $source, string $poster, string $subtitle, array $attributes, string $ratio, int $post_id ): string {
		$escaped_poster = esc_url( $poster );
		$image = '' !== $escaped_poster
			? '<img class="mdb-speech-video__poster" src="' . $escaped_poster . '" alt="" loading="lazy">'
			: '';

		return sprintf(
			'<div class="mdb-speech-video__frame" style="%1$s">'
			. '<button type="button" class="mdb-speech-video__load" data-mdb-src="%2$s" data-mdb-controls="%3$s" data-mdb-autoplay="%4$s" data-mdb-muted="%5$s" data-mdb-poster="%6$s" data-mdb-subtitle="%7$s">'
			. '%8$s<span class="mdb-speech-video__load-label">%9$s</span></button>'
			. '<noscript><a href="%10$s">%11$s</a></noscript></div>',
			esc_attr( $this->frame_style( $ratio ) ),
			esc_url( $source ),
			! empty( $attributes['controls'] ) ? '1' : '0',
			! empty( $attributes['autoplay'] ) ? '1' : '0',
			! empty( $attributes['muted'] ) ? '1' : '0',
			$escaped_poster,
			esc_url( $subtitle ),
			$image,
			esc_html__( 'Video laden', 'mdb-bundestag-speeches' ),
			esc_url( get_permalink( $post_id ) ),
			esc_html__( 'Bundestagsrede ansehen', 'mdb-bundestag-speeches' )
		);
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private function video( string $url, string $poster, string $subtitle, array $attributes ): string {
		$boolean_attributes = '';
		foreach ( array( 'controls', 'autoplay', 'muted' ) as $attribute ) {
			if ( ! empty( $attributes[ $attribute ] ) ) {
				$boolean_attributes .= ' ' . $attribute;
			}
		}
		$poster_attribute = '' !== $poster ? ' poster="' . esc_url( $poster ) . '"' : '';
		$track = '' !== $subtitle
			? '<track kind="subtitles" srclang="de" label="Deutsch" src="' . esc_url( $subtitle ) . '" default>'
			: '';

		return '<video src="' . esc_url( $url ) . '" preload="metadata" playsinline' . $boolean_attributes . $poster_attribute . '>' . $track . '</video>';
	}

	private function aspect_ratio( string $ratio ): string {
		return in_array( $ratio, array( '16/9', '4/3', '1/1', '21/9' ), true ) ? $ratio : '16/9';
	}

	private function frame_style( string $ratio ): string {
		// Keep the frame measurable before block styles and lazy-video scripts finish loading.
		return 'aspect-ratio:' . $ratio
			. ';--mdb-speech-aspect-ratio:' . $ratio
			. ';overflow:hidden;position:relative;width:100%';
	}

	private function post_id( WP_Block $block ): int {
		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		return $post_id > 0 ? $post_id : get_the_ID();
	}
}

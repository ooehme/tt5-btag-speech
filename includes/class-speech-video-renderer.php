<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Block;

final class Speech_Video_Renderer {
	public function __construct( private URL_Resolver $urls ) {}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function render( array $attributes, string $content, WP_Block $block ): string {
		$attributes = wp_parse_args(
			$attributes,
			array(
				'source'      => 'auto',
				'display'     => 'click_to_load',
				'controls'    => true,
				'autoplay'    => false,
				'muted'       => false,
				'poster'      => '',
				'aspectRatio' => '16/9',
			)
		);
		$post_id    = $this->post_id( $block );
		$source_url = (string) get_post_meta( $post_id, '_mdb_source_url', true );
		$source     = $this->source( $post_id, (string) $attributes['source'] );
		$wrapper    = get_block_wrapper_attributes( array( 'class' => 'mdb-speech-video' ) );

		if ( 'link' === $attributes['display'] ) {
			return sprintf(
				'<p %1$s><a href="%2$s">%3$s</a></p>',
				$wrapper,
				esc_url( get_permalink( $post_id ) ),
				esc_html__( 'Bundestagsrede ansehen', 'mdb-bundestag-speeches' )
			);
		}

		if ( '' === $source['url'] ) {
			return sprintf(
				'<div %1$s><p class="mdb-speech-video__fallback">%2$s</p>%3$s</div>',
				$wrapper,
				esc_html__( 'Für diese Rede ist derzeit kein Video verfügbar.', 'mdb-bundestag-speeches' ),
				$this->credit( $source_url )
			);
		}

		$ratio = $this->aspect_ratio( (string) $attributes['aspectRatio'] );
		$frame = 'click_to_load' === $attributes['display']
			? $this->click_to_load( $source, $attributes, $ratio, $post_id )
			: $this->direct( $source, $attributes, $ratio, $post_id );

		return sprintf( '<div %1$s>%2$s%3$s</div>', $wrapper, $frame, $this->credit( $source_url ) );
	}

	/**
	 * @return array{kind:string,url:string}
	 */
	private function source( int $post_id, string $preference ): array {
		$attachment_id = (int) get_post_meta( $post_id, '_mdb_attachment_id', true );
		$local_url     = $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';
		$embed_url     = (string) get_post_meta( $post_id, '_mdb_embed_url', true );
		if ( ! $this->urls->is_allowed_url( $embed_url ) ) {
			$embed_url = '';
		}

		if ( 'local' === $preference ) {
			return array( 'kind' => 'local', 'url' => $local_url );
		}
		if ( 'embed' === $preference ) {
			return array( 'kind' => 'embed', 'url' => $embed_url );
		}
		if ( '' !== $local_url ) {
			return array( 'kind' => 'local', 'url' => $local_url );
		}
		return array( 'kind' => 'embed', 'url' => $embed_url );
	}

	/**
	 * @param array{kind:string,url:string} $source Resolved player source.
	 * @param array<string,mixed>           $attributes Block attributes.
	 */
	private function direct( array $source, array $attributes, string $ratio, int $post_id ): string {
		$player = 'local' === $source['kind']
			? $this->video( $source['url'], $attributes )
			: $this->iframe( $source['url'], $attributes, $post_id );

		return sprintf(
			'<div class="mdb-speech-video__frame" style="--mdb-speech-aspect-ratio:%1$s">%2$s</div>',
			esc_attr( $ratio ),
			$player
		);
	}

	/**
	 * @param array{kind:string,url:string} $source Resolved player source.
	 * @param array<string,mixed>           $attributes Block attributes.
	 */
	private function click_to_load( array $source, array $attributes, string $ratio, int $post_id ): string {
		$poster = esc_url( (string) $attributes['poster'] );
		$image  = '' !== $poster
			? '<img class="mdb-speech-video__poster" src="' . $poster . '" alt="" loading="lazy">'
			: '';

		return sprintf(
			'<div class="mdb-speech-video__frame" style="--mdb-speech-aspect-ratio:%1$s">'
			. '<button type="button" class="mdb-speech-video__load" data-mdb-kind="%2$s" data-mdb-src="%3$s" data-mdb-title="%4$s" data-mdb-controls="%5$s" data-mdb-autoplay="%6$s" data-mdb-muted="%7$s" data-mdb-poster="%8$s">'
			. '%9$s<span class="mdb-speech-video__load-label">%10$s</span></button>'
			. '<noscript><a href="%11$s">%12$s</a></noscript></div>',
			esc_attr( $ratio ),
			esc_attr( $source['kind'] ),
			esc_url( $source['url'] ),
			esc_attr( sprintf( __( 'Bundestagsrede: %s', 'mdb-bundestag-speeches' ), get_the_title( $post_id ) ) ),
			! empty( $attributes['controls'] ) ? '1' : '0',
			! empty( $attributes['autoplay'] ) ? '1' : '0',
			! empty( $attributes['muted'] ) ? '1' : '0',
			$poster,
			$image,
			esc_html__( 'Video vom Deutschen Bundestag laden', 'mdb-bundestag-speeches' ),
			esc_url( get_permalink( $post_id ) ),
			esc_html__( 'Bundestagsrede ansehen', 'mdb-bundestag-speeches' )
		);
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private function video( string $url, array $attributes ): string {
		$boolean_attributes = '';
		foreach ( array( 'controls', 'autoplay', 'muted' ) as $attribute ) {
			if ( ! empty( $attributes[ $attribute ] ) ) {
				$boolean_attributes .= ' ' . $attribute;
			}
		}
		$poster = '' !== (string) $attributes['poster'] ? ' poster="' . esc_url( (string) $attributes['poster'] ) . '"' : '';

		return '<video src="' . esc_url( $url ) . '" preload="metadata" playsinline' . $boolean_attributes . $poster . '></video>';
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private function iframe( string $url, array $attributes, int $post_id ): string {
		if ( ! empty( $attributes['autoplay'] ) ) {
			$url = add_query_arg( 'autoplay', '1', $url );
		}

		return sprintf(
			'<iframe src="%1$s" title="%2$s" loading="lazy" allowfullscreen="true" referrerpolicy="origin" allow="geolocation; autoplay; fullscreen" sandbox="allow-same-origin allow-scripts allow-forms allow-modals allow-popups"></iframe>',
			esc_url( $url ),
			esc_attr( sprintf( __( 'Bundestagsrede: %s', 'mdb-bundestag-speeches' ), get_the_title( $post_id ) ) )
		);
	}

	private function credit( string $source_url ): string {
		if ( '' === $source_url ) {
			return '<p class="mdb-speech-video__credit">' . esc_html__( 'Quelle: Deutscher Bundestag', 'mdb-bundestag-speeches' ) . '</p>';
		}
		return sprintf(
			'<p class="mdb-speech-video__credit">%1$s <a href="%2$s" rel="external">%3$s</a></p>',
			esc_html__( 'Quelle:', 'mdb-bundestag-speeches' ),
			esc_url( $source_url ),
			esc_html__( 'Deutscher Bundestag', 'mdb-bundestag-speeches' )
		);
	}

	private function aspect_ratio( string $ratio ): string {
		return in_array( $ratio, array( '16/9', '4/3', '1/1', '21/9' ), true ) ? $ratio : '16/9';
	}

	private function post_id( WP_Block $block ): int {
		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		return $post_id > 0 ? $post_id : get_the_ID();
	}
}

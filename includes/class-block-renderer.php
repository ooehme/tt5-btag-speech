<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Block;

final class Block_Renderer {
	public function render_topic( array $attributes, string $content, WP_Block $block ): string {
		return $this->field( '_mdb_topic', 'mdb-speech-topic', $block );
	}

	public function render_session( array $attributes, string $content, WP_Block $block ): string {
		return $this->field( '_mdb_session', 'mdb-speech-session', $block );
	}

	public function render_source_link( array $attributes, string $content, WP_Block $block ): string {
		$post_id = $this->post_id( $block );
		$url     = (string) get_post_meta( $post_id, '_mdb_source_url', true );
		if ( '' === $url ) {
			return '';
		}

		$label   = isset( $attributes['label'] ) && '' !== trim( (string) $attributes['label'] )
			? (string) $attributes['label']
			: __( 'Originalquelle: Deutscher Bundestag', 'mdb-bundestag-speeches' );
		$target  = ! empty( $attributes['openInNewTab'] ) ? ' target="_blank"' : '';
		$rel     = ! empty( $attributes['openInNewTab'] ) ? 'external noopener noreferrer' : 'external';
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'mdb-speech-source-link' ) );

		return sprintf(
			'<p %1$s><a href="%2$s"%3$s rel="%4$s">%5$s</a></p>',
			$wrapper,
			esc_url( $url ),
			$target,
			esc_attr( $rel ),
			esc_html( $label )
		);
	}

	private function field( string $meta_key, string $class, WP_Block $block ): string {
		$value = (string) get_post_meta( $this->post_id( $block ), $meta_key, true );
		if ( '' === $value ) {
			return '';
		}
		$wrapper = get_block_wrapper_attributes( array( 'class' => $class ) );
		return '<p ' . $wrapper . '>' . esc_html( $value ) . '</p>';
	}

	private function post_id( WP_Block $block ): int {
		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		return $post_id > 0 ? $post_id : get_the_ID();
	}
}

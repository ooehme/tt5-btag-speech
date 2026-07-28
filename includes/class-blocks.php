<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Blocks {
	public function __construct(
		private Speech_Video_Renderer $video_renderer,
		private Block_Renderer $renderer
	) {}

	public function register(): void {
		add_action( 'init', array( $this, 'blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'category' ) );
	}

	public function blocks(): void {
		$asset = require MDB_SPEECHES_DIR . 'assets/editor.asset.php';
		wp_register_script(
			'mdb-speeches-editor-blocks',
			MDB_SPEECHES_URL . 'assets/editor/blocks.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_register_script(
			'mdb-speeches-editor-query',
			MDB_SPEECHES_URL . 'assets/editor/query.js',
			array( 'wp-blocks', 'wp-i18n' ),
			$asset['version'],
			true
		);
		wp_register_script(
			'mdb-speeches-editor-query-controls',
			MDB_SPEECHES_URL . 'assets/editor/query-controls.js',
			array( 'wp-block-editor', 'wp-compose', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			$asset['version'],
			true
		);
		wp_register_script(
			'mdb-speeches-editor-title-controls',
			MDB_SPEECHES_URL . 'assets/editor/title-controls.js',
			array( 'wp-block-editor', 'wp-compose', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			$asset['version'],
			true
		);
		wp_register_script(
			'mdb-speeches-editor',
			MDB_SPEECHES_URL . 'assets/editor.js',
			array(
				'mdb-speeches-editor-blocks',
				'mdb-speeches-editor-query',
				'mdb-speeches-editor-query-controls',
				'mdb-speeches-editor-title-controls',
			),
			$asset['version'],
			true
		);
		$translated_scripts = array(
			'mdb-speeches-editor-blocks',
			'mdb-speeches-editor-query',
			'mdb-speeches-editor-query-controls',
			'mdb-speeches-editor-title-controls',
		);
		foreach ( $translated_scripts as $handle ) {
			wp_set_script_translations( $handle, 'mdb-bundestag-speeches', MDB_SPEECHES_DIR . 'languages' );
		}
		wp_register_script(
			'mdb-speeches-view',
			MDB_SPEECHES_URL . 'assets/view.js',
			array(),
			MDB_SPEECHES_VERSION,
			true
		);
		wp_register_style( 'mdb-speeches-style', MDB_SPEECHES_URL . 'assets/style.css', array(), MDB_SPEECHES_VERSION );
		wp_register_style( 'mdb-speeches-editor-style', MDB_SPEECHES_URL . 'assets/editor.css', array( 'wp-edit-blocks' ), MDB_SPEECHES_VERSION );

		register_block_type_from_metadata(
			MDB_SPEECHES_DIR . 'blocks/speech-video',
			array( 'render_callback' => array( $this->video_renderer, 'render' ) )
		);
		register_block_type_from_metadata(
			MDB_SPEECHES_DIR . 'blocks/speech-topic',
			array( 'render_callback' => array( $this->renderer, 'render_topic' ) )
		);
		register_block_type_from_metadata(
			MDB_SPEECHES_DIR . 'blocks/speech-session',
			array( 'render_callback' => array( $this->renderer, 'render_session' ) )
		);
		register_block_type_from_metadata(
			MDB_SPEECHES_DIR . 'blocks/speech-source-link',
			array( 'render_callback' => array( $this->renderer, 'render_source_link' ) )
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $categories Block categories.
	 * @return array<int,array<string,mixed>>
	 */
	public function category( array $categories ): array {
		$categories[] = array(
			'slug'  => 'mdb-speeches',
			'title' => __( 'Bundestagsreden', 'mdb-bundestag-speeches' ),
			'icon'  => 'video-alt3',
		);
		return $categories;
	}
}

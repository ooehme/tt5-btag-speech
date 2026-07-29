<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Plugin {
	private static ?self $instance = null;
	private bool $registered = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register(): void {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		$settings       = new Settings();
		$urls           = new URL_Resolver();
		$repository     = new Speech_Repository();
		$source         = new Source_Client( $urls, new List_Parser(), new Video_Parser(), new Article_Parser() );
		$validator      = new MP4_Validator( $settings, $urls );
		$downloads      = new Download_Service(
			$validator,
			$repository,
			new Download_Lock()
		);
		$sync           = new Synchronizer( $settings, $source, $repository, new Sync_Lock() );
		$wipe           = new Wipe_Service();

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'admin_init', array( $settings, 'register' ) );

		( new Speech_Post_Type() )->register();
		( new Legacy_Article_Image_Cleanup() )->register();
		( new Cron( $settings, $sync, $downloads ) )->register();
		( new Blocks( new Speech_Video_Renderer(), new Block_Renderer() ) )->register();
		( new Release_Updater() )->register();
		( new CLI( $sync, $downloads, $repository ) )->register();

		if ( is_admin() ) {
			( new Admin( $settings, $sync, $downloads, $repository, $wipe ) )->register();
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'mdb-bundestag-speeches', false, dirname( plugin_basename( MDB_SPEECHES_FILE ) ) . '/languages' );
	}

	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) || version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
			deactivate_plugins( plugin_basename( MDB_SPEECHES_FILE ) );
			wp_die(
				esc_html__( 'MDB Bundestagsreden benötigt WordPress 6.7 und PHP 8.0 oder neuer.', 'mdb-bundestag-speeches' ),
				esc_html__( 'Plugin konnte nicht aktiviert werden', 'mdb-bundestag-speeches' ),
				array( 'back_link' => true )
			);
		}

		add_option( Settings::OPTION, Settings::defaults(), '', false );
		( new Speech_Post_Type() )->register_post_type();
		flush_rewrite_rules();

		if ( false === wp_next_scheduled( Cron::SYNC_HOOK ) ) {
			$settings = get_option( Settings::OPTION, Settings::defaults() );
			$interval = is_array( $settings ) ? (string) ( $settings['interval'] ?? 'twicedaily' ) : 'twicedaily';
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, Cron::SYNC_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( Cron::SYNC_HOOK );
		wp_clear_scheduled_hook( Cron::DOWNLOAD_HOOK );
		flush_rewrite_rules();
	}
}

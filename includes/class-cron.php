<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Cron {
	public const SYNC_HOOK = 'mdb_speeches_sync';
	public const DOWNLOAD_HOOK = 'mdb_speeches_download_one';

	public function __construct(
		private Settings $settings,
		private Synchronizer $synchronizer,
		private Download_Service $downloads
	) {}

	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'schedules' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
		add_action( self::SYNC_HOOK, array( $this, 'sync' ) );
		add_action( self::DOWNLOAD_HOOK, array( $this, 'download' ) );
		add_action( 'update_option_' . Settings::OPTION, array( $this, 'settings_updated' ), 10, 2 );
	}

	/**
	 * @param array<string,array<string,mixed>> $schedules Existing schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public function schedules( array $schedules ): array {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Einmal wöchentlich', 'mdb-bundestag-speeches' ),
		);
		return $schedules;
	}

	public function maybe_schedule(): void {
		if ( false === wp_next_scheduled( self::SYNC_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, (string) $this->settings->get( 'interval' ), self::SYNC_HOOK );
		}
	}

	/**
	 * @param mixed $old_value Old option value.
	 * @param mixed $new_value New option value.
	 */
	public function settings_updated( mixed $old_value, mixed $new_value ): void {
		$old_interval = is_array( $old_value ) ? (string) ( $old_value['interval'] ?? '' ) : '';
		$new_interval = is_array( $new_value ) ? (string) ( $new_value['interval'] ?? '' ) : '';
		if ( $old_interval !== $new_interval ) {
			wp_clear_scheduled_hook( self::SYNC_HOOK );
			$this->maybe_schedule();
		}
	}

	public function sync(): void {
		$this->synchronizer->sync();
	}

	public function download( int $post_id ): void {
		$this->downloads->download( $post_id );
	}
}

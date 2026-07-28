<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class CLI {
	public function __construct(
		private Synchronizer $synchronizer,
		private Download_Service $downloads,
		private Speech_Repository $repository
	) {}

	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'mdb-speeches', $this );
		}
	}

	/**
	 * Synchronisiert die Metadaten aller Reden.
	 */
	public function sync(): void {
		$result = $this->synchronizer->sync();
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success(
			sprintf(
				'%d neu, %d aktualisiert, %d Fehler, %d Downloads eingeplant.',
				$result['created'],
				$result['updated'],
				$result['errors'],
				$result['queued']
			)
		);
	}

	/**
	 * Lädt alle verfügbaren, noch nicht lokal gespeicherten Reden.
	 */
	public function download(): void {
		$ids     = $this->repository->ids_by_status(
			array(
				Sync_Status::EMBED_AVAILABLE,
				Sync_Status::DOWNLOAD_PENDING,
				Sync_Status::DOWNLOAD_FAILED,
			)
		);
		$success = 0;
		$errors  = 0;
		foreach ( $ids as $post_id ) {
			$result = $this->downloads->download( $post_id );
			is_wp_error( $result ) ? ++$errors : ++$success;
		}
		\WP_CLI::success( sprintf( '%d Downloads abgeschlossen, %d Fehler.', $success, $errors ) );
	}

	/**
	 * Startet fehlgeschlagene Downloads erneut.
	 */
	public function retry(): void {
		$result = $this->downloads->queue_failed();
		\WP_CLI::success( sprintf( '%d Downloads erneut eingeplant.', $result['queued'] ) );
	}
}

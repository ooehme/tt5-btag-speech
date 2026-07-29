<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use WP_Error;

final class Admin {
	private const PAGE = 'mdb-speeches-settings';

	public function __construct(
		private Settings $settings,
		private Synchronizer $synchronizer,
		private Download_Service $downloads,
		private Speech_Repository $repository,
		private Wipe_Service $wipe_service
	) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_mdb_speeches_sync', array( $this, 'sync' ) );
		add_action( 'admin_post_mdb_speeches_retry', array( $this, 'retry' ) );
		add_action( 'admin_post_mdb_speeches_download', array( $this, 'download' ) );
		add_action( 'admin_post_mdb_speeches_wipe', array( $this, 'wipe' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MDB_SPEECHES_FILE ), array( $this, 'action_links' ) );
	}

	public function assets( string $hook_suffix ): void {
		if ( Speech_Repository::POST_TYPE . '_page_' . self::PAGE !== $hook_suffix ) {
			return;
		}
		wp_enqueue_script(
			'mdb-speeches-admin',
			MDB_SPEECHES_URL . 'assets/admin.js',
			array(),
			MDB_SPEECHES_VERSION,
			true
		);
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . Speech_Repository::POST_TYPE,
			__( 'Bundestagsreden synchronisieren', 'mdb-bundestag-speeches' ),
			__( 'Synchronisierung', 'mdb-bundestag-speeches' ),
			'manage_options',
			self::PAGE,
			array( $this, 'page' )
		);
	}

	public function page(): void {
		$this->guard();
		$settings    = $this->settings->all();
		$speeches    = $this->repository->recent();
		$last_sync   = get_option( 'mdb_speeches_last_sync', array() );
		$wipe_paused = (bool) get_option( Wipe_Service::PAUSE_OPTION, false );
		$notice      = get_transient( $this->notice_key() );
		delete_transient( $this->notice_key() );

		require MDB_SPEECHES_DIR . 'includes/admin/views/settings-page.php';
	}

	public function sync(): void {
		$this->guard();
		check_admin_referer( 'mdb_speeches_sync' );
		$result = $this->synchronizer->sync();
		if ( is_wp_error( $result ) ) {
			$this->redirect( 'error', $result->get_error_message() );
		}

		$message = sprintf(
			/* translators: 1: created, 2: updated, 3: errors, 4: queued downloads. */
			__( 'Synchronisiert: %1$d neu, %2$d aktualisiert, %3$d Fehler, %4$d Downloads eingeplant.', 'mdb-bundestag-speeches' ),
			$result['created'],
			$result['updated'],
			$result['errors'],
			$result['queued']
		);
		$this->redirect( 'success', $message );
	}

	public function retry(): void {
		$this->guard();
		check_admin_referer( 'mdb_speeches_retry' );
		$result = $this->downloads->queue_failed();
		$this->redirect(
			'success',
			sprintf(
				/* translators: %d number of queued downloads. */
				_n( '%d Download erneut eingeplant.', '%d Downloads erneut eingeplant.', $result['queued'], 'mdb-bundestag-speeches' ),
				$result['queued']
			)
		);
	}

	public function download(): void {
		$this->guard();
		check_admin_referer( 'mdb_speeches_download' );
		$result = $this->downloads->queue_available();
		$this->redirect(
			'success',
			sprintf(
				/* translators: %d number of queued downloads. */
				_n( '%d Download eingeplant.', '%d Downloads eingeplant.', $result['queued'], 'mdb-bundestag-speeches' ),
				$result['queued']
			)
		);
	}

	public function wipe(): void {
		$this->guard();
		check_admin_referer( 'mdb_speeches_wipe' );
		$result = $this->wipe_service->wipe();

		$message = sprintf(
			/* translators: 1: deleted posts, 2: deleted attachments, 3: failures. */
			__( 'Zurückgesetzt: %1$d Beiträge und %2$d Mediendateien gelöscht. Fehler: %3$d. Automatischer Abgleich pausiert.', 'mdb-bundestag-speeches' ),
			$result['posts'],
			$result['attachments'],
			$result['failed']
		);
		$this->redirect( $result['failed'] > 0 ? 'error' : 'success', $message );
	}

	/**
	 * @param array<int,string> $links Plugin action links.
	 * @return array<int,string>
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . Speech_Repository::POST_TYPE . '&page=' . self::PAGE ) ) . '">' . esc_html__( 'Synchronisierung', 'mdb-bundestag-speeches' ) . '</a>'
		);
		return $links;
	}

	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'mdb-bundestag-speeches' ) );
		}
	}

	private function redirect( string $type, string $message ): void {
		set_transient(
			$this->notice_key(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Speech_Repository::POST_TYPE . '&page=' . self::PAGE ) );
		exit;
	}

	private function notice_key(): string {
		return 'mdb_speeches_notice_' . get_current_user_id();
	}
}

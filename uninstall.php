<?php
/**
 * Removes plugin-owned settings and scheduled jobs.
 *
 * Synced speeches and media attachments are editorial content and are preserved.
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cleanup = static function (): void {
	wp_clear_scheduled_hook( 'mdb_speeches_sync' );

	$speech_ids = get_posts(
		array(
			'post_type'      => 'mdb_speech',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	foreach ( $speech_ids as $post_id ) {
		wp_clear_scheduled_hook( 'mdb_speeches_download_one', array( (int) $post_id ) );
		delete_option( 'mdb_speeches_download_lock_' . (int) $post_id );
	}

	delete_option( 'mdb_speeches_settings' );
	delete_option( 'mdb_speeches_last_sync' );
	delete_option( 'mdb_speeches_sync_lock' );
	delete_site_transient( 'mdb_speeches_github_release' );
};

if ( is_multisite() ) {
	$offset = 0;
	do {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $offset,
			)
		);
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			$cleanup();
			restore_current_blog();
		}
		$offset += count( $site_ids );
	} while ( 100 === count( $site_ids ) );
} else {
	$cleanup();
}

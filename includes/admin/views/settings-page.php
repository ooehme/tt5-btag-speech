<?php
/**
 * Synchronization settings view.
 *
 * @var array<string,mixed> $settings
 * @var array<int,\WP_Post> $speeches
 * @var array<int,array{name:string,rednerId:string}> $speakers
 * @var array<string,mixed> $last_sync
 * @var bool                $wipe_paused
 * @var mixed               $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Bundestagsreden', 'mdb-bundestag-speeches' ); ?></h1>

	<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
		<div class="notice <?php echo esc_attr( 'success' === $notice['type'] ? 'notice-success' : 'notice-error' ); ?> is-dismissible">
			<p><?php echo esc_html( (string) $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $wipe_paused ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Der automatische Abgleich ist nach dem Komplett-Wipe pausiert. „Jetzt synchronisieren“ hebt die Pause auf.', 'mdb-bundestag-speeches' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( is_array( $last_sync ) && ! empty( $last_sync['errors'] ) && is_array( $last_sync['errors'] ) ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Fehler der letzten Synchronisierung:', 'mdb-bundestag-speeches' ); ?></strong></p>
			<ul>
				<?php foreach ( $last_sync['errors'] as $sync_error ) : ?>
					<li><?php echo esc_html( (string) $sync_error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( is_array( $last_sync ) && ! empty( $last_sync['time'] ) ) : ?>
		<p>
			<?php
			printf(
				/* translators: %s UTC time of the latest synchronization. */
				esc_html__( 'Letzte Synchronisierung: %s UTC', 'mdb-bundestag-speeches' ),
				esc_html( (string) $last_sync['time'] )
			);
			?>
		</p>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'mdb_speeches' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="mdb-speaker-id"><?php esc_html_e( 'Bundestag-Redner-ID', 'mdb-bundestag-speeches' ); ?></label></th>
				<td>
					<input id="mdb-speaker-id" name="mdb_speeches_settings[speaker_id]" type="text" inputmode="numeric" pattern="[0-9]+" list="mdb-speaker-options" autocomplete="off" value="<?php echo esc_attr( (string) $settings['speaker_id'] ); ?>" class="regular-text" required>
					<datalist id="mdb-speaker-options">
						<?php foreach ( $speakers as $speaker ) : ?>
							<option value="<?php echo esc_attr( $speaker['rednerId'] ); ?>" label="<?php echo esc_attr( $speaker['name'] ); ?>"><?php echo esc_html( $speaker['name'] ); ?></option>
						<?php endforeach; ?>
					</datalist>
					<p class="description"><?php esc_html_e( 'Redner auswählen oder eine numerische Redner-ID manuell eingeben.', 'mdb-bundestag-speeches' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mdb-speaker-filter"><?php esc_html_e( 'Redenlisten-Filter-IDs', 'mdb-bundestag-speeches' ); ?></label></th>
				<td>
					<input id="mdb-speaker-filter" name="mdb_speeches_settings[speaker_filter]" type="text" pattern="[0-9]+( OR [0-9]+)*" value="<?php echo esc_attr( (string) $settings['speaker_filter'] ); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e( 'Zusätzliche Bundestag-interne IDs mit OR trennen, z. B. 21244 OR 12404.', 'mdb-bundestag-speeches' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mdb-interval"><?php esc_html_e( 'Synchronisationsintervall', 'mdb-bundestag-speeches' ); ?></label></th>
				<td>
					<select id="mdb-interval" name="mdb_speeches_settings[interval]">
						<option value="hourly" <?php selected( $settings['interval'], 'hourly' ); ?>><?php esc_html_e( 'Stündlich', 'mdb-bundestag-speeches' ); ?></option>
						<option value="twicedaily" <?php selected( $settings['interval'], 'twicedaily' ); ?>><?php esc_html_e( 'Zweimal täglich', 'mdb-bundestag-speeches' ); ?></option>
						<option value="daily" <?php selected( $settings['interval'], 'daily' ); ?>><?php esc_html_e( 'Täglich', 'mdb-bundestag-speeches' ); ?></option>
						<option value="weekly" <?php selected( $settings['interval'], 'weekly' ); ?>><?php esc_html_e( 'Wöchentlich', 'mdb-bundestag-speeches' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mdb-download-mode"><?php esc_html_e( 'Downloadmodus', 'mdb-bundestag-speeches' ); ?></label></th>
				<td>
					<select id="mdb-download-mode" name="mdb_speeches_settings[download_mode]">
						<option value="automatic" <?php selected( $settings['download_mode'], 'automatic' ); ?>><?php esc_html_e( 'Automatisch lokal speichern', 'mdb-bundestag-speeches' ); ?></option>
						<option value="local" <?php selected( $settings['download_mode'], 'local' ); ?>><?php esc_html_e( 'Lokale Downloads manuell starten', 'mdb-bundestag-speeches' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mdb-quality"><?php esc_html_e( 'Bevorzugte Qualität', 'mdb-bundestag-speeches' ); ?></label></th>
				<td>
					<select id="mdb-quality" name="mdb_speeches_settings[quality]">
						<option value="1080p_8000" <?php selected( $settings['quality'], '1080p_8000' ); ?>>1080p / 8 Mbit/s</option>
						<option value="1080p_5000" <?php selected( $settings['quality'], '1080p_5000' ); ?>>1080p / 5 Mbit/s</option>
					</select>
					<p class="description"><?php esc_html_e( 'Falls die gewählte Variante fehlt, wird automatisch die andere 1080p-Variante versucht.', 'mdb-bundestag-speeches' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mdb-max-file-size"><?php esc_html_e( 'Maximale Dateigröße', 'mdb-bundestag-speeches' ); ?></label></th>
				<td><input id="mdb-max-file-size" name="mdb_speeches_settings[max_file_size]" type="number" min="1" max="2048" value="<?php echo esc_attr( (string) $settings['max_file_size'] ); ?>"> MB</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>

	<hr>
	<h2><?php esc_html_e( 'Aktionen', 'mdb-bundestag-speeches' ); ?></h2>
	<div id="mdb-speeches-sync-progress" class="notice notice-info inline" role="status" aria-live="polite" hidden>
		<p><span class="spinner is-active" aria-hidden="true"></span><?php esc_html_e( 'Synchronisierung läuft. Bitte diese Seite geöffnet lassen.', 'mdb-bundestag-speeches' ); ?></p>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap">
		<form
			id="mdb-speeches-sync-form"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			data-progress-label="<?php echo esc_attr__( 'Synchronisierung läuft …', 'mdb-bundestag-speeches' ); ?>"
		>
			<input type="hidden" name="action" value="mdb_speeches_sync">
			<?php wp_nonce_field( 'mdb_speeches_sync' ); ?>
			<?php submit_button( __( 'Jetzt synchronisieren', 'mdb-bundestag-speeches' ), 'primary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mdb_speeches_download">
			<?php wp_nonce_field( 'mdb_speeches_download' ); ?>
			<?php submit_button( __( 'Lokale Downloads und Untertitel starten', 'mdb-bundestag-speeches' ), 'secondary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mdb_speeches_retry">
			<?php wp_nonce_field( 'mdb_speeches_retry' ); ?>
			<?php submit_button( __( 'Fehlgeschlagene Downloads erneut starten', 'mdb-bundestag-speeches' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<hr>
	<h2><?php esc_html_e( 'Gefahrenzone', 'mdb-bundestag-speeches' ); ?></h2>
	<p><?php esc_html_e( 'Löscht unwiderruflich alle synchronisierten Bundestagsreden, zugehörigen Medien, Legacy-Daten und Plugin-Einstellungen.', 'mdb-bundestag-speeches' ); ?></p>
	<form
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		onsubmit="return confirm('<?php echo esc_attr__( 'Wirklich alle Bundestagsreden und zugehörigen Medien unwiderruflich löschen?', 'mdb-bundestag-speeches' ); ?>');"
	>
		<input type="hidden" name="action" value="mdb_speeches_wipe">
		<?php wp_nonce_field( 'mdb_speeches_wipe' ); ?>
		<?php submit_button( __( 'Plugin-Daten vollständig zurücksetzen', 'mdb-bundestag-speeches' ), 'delete', 'submit', false ); ?>
	</form>

	<h2><?php esc_html_e( 'Status', 'mdb-bundestag-speeches' ); ?></h2>
	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'Rede', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Video-ID', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Artikeltitel', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Veröffentlichungsdatum', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Artikelbild-URL', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Status', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Zuletzt gesehen', 'mdb-bundestag-speeches' ); ?></th>
			<th><?php esc_html_e( 'Fehler', 'mdb-bundestag-speeches' ); ?></th>
		</tr></thead>
		<tbody>
		<?php if ( array() === $speeches ) : ?>
			<tr><td colspan="8"><?php esc_html_e( 'Noch keine Reden synchronisiert.', 'mdb-bundestag-speeches' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $speeches as $speech ) : ?>
				<?php $article_image_url = (string) get_post_meta( $speech->ID, '_mdb_article_image_url', true ); ?>
				<tr>
					<td><a href="<?php echo esc_url( get_edit_post_link( $speech->ID ) ); ?>"><?php echo esc_html( get_the_title( $speech ) ); ?></a></td>
					<td><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_video_id', true ) ); ?></td>
					<td><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_article_title', true ) ); ?></td>
					<td><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_source_date', true ) ); ?></td>
					<td style="overflow-wrap:anywhere">
						<?php if ( '' !== $article_image_url ) : ?>
							<a href="<?php echo esc_url( $article_image_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $article_image_url ); ?></a>
						<?php endif; ?>
					</td>
					<td><code><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_sync_status', true ) ); ?></code></td>
					<td><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_last_seen', true ) ); ?></td>
					<td><?php echo esc_html( (string) get_post_meta( $speech->ID, '_mdb_last_error', true ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use stdClass;
use WP_Error;

final class Release_Updater {
	private const REPOSITORY = 'ooehme/tt5-btag-speech';
	private const UPDATE_URI = 'https://github.com/' . self::REPOSITORY;
	private const API_URL = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';
	private const PACKAGE_BASE = self::UPDATE_URI . '/releases/download/';
	private const CACHE_KEY = 'mdb_speeches_github_release';

	public function register(): void {
		add_filter( 'update_plugins_github.com', array( $this, 'update' ), 10, 4 );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
	}

	/**
	 * @param mixed               $update Existing update value.
	 * @param array<string,mixed> $plugin_data Plugin header data.
	 * @param string              $plugin_file Plugin basename.
	 * @param array<int,string>   $locales Requested locales.
	 */
	public function update( mixed $update, array $plugin_data, string $plugin_file, array $locales ): mixed {
		if (
			plugin_basename( MDB_SPEECHES_FILE ) !== $plugin_file
			|| self::UPDATE_URI !== (string) ( $plugin_data['UpdateURI'] ?? '' )
		) {
			return $update;
		}

		$release = $this->release();
		if ( is_wp_error( $release ) || version_compare( $release['version'], MDB_SPEECHES_VERSION, '<=' ) ) {
			return false;
		}

		return array(
			'id'           => self::UPDATE_URI,
			'slug'         => dirname( plugin_basename( MDB_SPEECHES_FILE ) ),
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'tested'       => '7.0',
			'requires_php' => '8.0',
		);
	}

	/**
	 * @param mixed    $result Existing API result.
	 * @param string   $action Requested action.
	 * @param stdClass $args API arguments.
	 */
	public function plugin_information( mixed $result, string $action, stdClass $args ): mixed {
		$slug = dirname( plugin_basename( MDB_SPEECHES_FILE ) );
		if ( 'plugin_information' !== $action || (string) ( $args->slug ?? '' ) !== $slug ) {
			return $result;
		}

		$release = $this->release();
		if ( is_wp_error( $release ) ) {
			return $result;
		}

		return (object) array(
			'name'          => 'MDB Bundestagsreden',
			'slug'          => $slug,
			'version'       => $release['version'],
			'author'        => '<a href="https://oliveroehme.de/">Oliver Oehme</a>',
			'homepage'      => self::UPDATE_URI,
			'requires'      => '6.7',
			'requires_php'  => '8.0',
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => __( 'Synchronisiert Bundestagsreden und stellt dynamische Gutenberg-Blöcke bereit.', 'mdb-bundestag-speeches' ),
				'changelog'   => wp_kses_post( $release['notes'] ),
			),
		);
	}

	/**
	 * @return array{version:string,url:string,package:string,notes:string}|WP_Error
	 */
	private function release(): array|WP_Error {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_safe_remote_get(
			self::API_URL,
			array(
				'timeout'             => 10,
				'redirection'         => 2,
				'limit_response_size' => 1024 * 1024,
				'headers'             => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'MDB-Bundestagsreden/' . MDB_SPEECHES_VERSION,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'mdb_update_http', __( 'Das aktuelle GitHub-Release konnte nicht ermittelt werden.', 'mdb-bundestag-speeches' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
			return new WP_Error( 'mdb_update_json', __( 'GitHub lieferte ungültige Release-Daten.', 'mdb-bundestag-speeches' ) );
		}

		$version = ltrim( sanitize_text_field( (string) ( $data['tag_name'] ?? '' ) ), 'vV' );
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/D', $version ) ) {
			return new WP_Error( 'mdb_update_asset', __( 'Das GitHub-Release enthält kein gültiges Plugin-ZIP.', 'mdb-bundestag-speeches' ) );
		}
		$package = $this->package_url( $data['assets'] ?? array(), $version );
		if ( '' === $package ) {
			return new WP_Error( 'mdb_update_asset', __( 'Das GitHub-Release enthält kein gültiges Plugin-ZIP.', 'mdb-bundestag-speeches' ) );
		}

		$details_url = esc_url_raw( (string) ( $data['html_url'] ?? '' ) );
		if ( ! str_starts_with( $details_url, self::UPDATE_URI . '/releases/' ) ) {
			$details_url = self::UPDATE_URI . '/releases/latest';
		}

		$release = array(
			'version' => $version,
			'url'     => $details_url,
			'package' => $package,
			'notes'   => (string) ( $data['body'] ?? '' ),
		);
		set_site_transient( self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * @param mixed $assets GitHub release assets.
	 */
	private function package_url( mixed $assets, string $version ): string {
		if ( ! is_array( $assets ) ) {
			return '';
		}
		$expected = 'mdb-bundestag-speeches-' . $version . '.zip';
		foreach ( $assets as $asset ) {
			if ( is_array( $asset ) && $expected === (string) ( $asset['name'] ?? '' ) ) {
				$url = esc_url_raw( (string) ( $asset['browser_download_url'] ?? '' ) );
				if ( str_starts_with( $url, self::PACKAGE_BASE ) && str_ends_with( $url, '/' . $expected ) ) {
					return $url;
				}
			}
		}
		return '';
	}
}

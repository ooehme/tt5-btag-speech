<?php
/**
 * Plugin Name:       MDB Bundestagsreden
 * Plugin URI:        https://oliveroehme.de/werkzeuge/tt5-btag-speech/
 * Description:       Synchronisiert Reden aus der Mediathek des Deutschen Bundestages und stellt sie als Gutenberg-Blöcke bereit.
 * Version:           2.0.2
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Oliver Oehme
 * Author URI:        https://oliveroehme.de/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/ooehme/tt5-btag-speech
 * Text Domain:       mdb-bundestag-speeches
 * Domain Path:       /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MDB_SPEECHES_VERSION', '2.0.2' );
define( 'MDB_SPEECHES_FILE', __FILE__ );
define( 'MDB_SPEECHES_DIR', plugin_dir_path( __FILE__ ) );
define( 'MDB_SPEECHES_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'MDB\\BundestagSpeeches\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = MDB_SPEECHES_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'MDB\\BundestagSpeeches\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MDB\\BundestagSpeeches\\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		MDB\BundestagSpeeches\Plugin::instance()->register();
	}
);

<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Settings {
	public const OPTION = 'mdb_speeches_settings';

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'speaker_id'     => '12404',
			'speaker_filter' => '21244 OR 12404',
			'interval'       => 'twicedaily',
			'download_mode'  => 'embed_only',
			'quality'        => '1080p_8000',
			'max_file_size'  => 750,
		);
	}

	public function register(): void {
		register_setting(
			'mdb_speeches',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function all(): array {
		$value = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $value ) ? $value : array() );
	}

	public function get( string $key ): mixed {
		$settings = $this->all();
		return $settings[ $key ] ?? null;
	}

	/**
	 * @param mixed $input Untrusted option value.
	 * @return array<string,mixed>
	 */
	public function sanitize( mixed $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$speaker_id = isset( $input['speaker_id'] ) ? trim( (string) $input['speaker_id'] ) : '';
		if ( ! preg_match( '/^\d+$/', $speaker_id ) ) {
			add_settings_error( self::OPTION, 'speaker_id', __( 'Die Redner-ID muss aus Ziffern bestehen.', 'mdb-bundestag-speeches' ) );
			$speaker_id = (string) $defaults['speaker_id'];
		}
		$speaker_filter = isset( $input['speaker_filter'] ) ? strtoupper( trim( (string) $input['speaker_filter'] ) ) : '';
		$speaker_filter = preg_replace( '/\s+/', ' ', $speaker_filter ) ?? '';
		if ( '' === $speaker_filter ) {
			$speaker_filter = $speaker_id;
		}
		if ( ! preg_match( '/^\d+(?: OR \d+)*$/', $speaker_filter ) ) {
			add_settings_error( self::OPTION, 'speaker_filter', __( 'Der Redenlisten-Filter darf nur numerische IDs enthalten, getrennt durch OR.', 'mdb-bundestag-speeches' ) );
			$speaker_filter = $speaker_id;
		}

		$intervals = array( 'hourly', 'twicedaily', 'daily', 'weekly' );
		$interval  = isset( $input['interval'] ) ? sanitize_key( (string) $input['interval'] ) : '';
		if ( ! in_array( $interval, $intervals, true ) ) {
			$interval = (string) $defaults['interval'];
		}

		$modes = array( 'embed_only', 'automatic', 'local' );
		$mode  = isset( $input['download_mode'] ) ? sanitize_key( (string) $input['download_mode'] ) : '';
		if ( ! in_array( $mode, $modes, true ) ) {
			$mode = (string) $defaults['download_mode'];
		}

		$qualities = array_keys( URL_Resolver::quality_profiles() );
		$quality   = isset( $input['quality'] ) ? sanitize_key( (string) $input['quality'] ) : '';
		if ( ! in_array( $quality, $qualities, true ) ) {
			$quality = (string) $defaults['quality'];
		}

		$max_file_size = isset( $input['max_file_size'] ) ? absint( $input['max_file_size'] ) : (int) $defaults['max_file_size'];
		$max_file_size = max( 1, min( 2048, $max_file_size ) );

		return array(
			'speaker_id'     => $speaker_id,
			'speaker_filter' => $speaker_filter,
			'interval'       => $interval,
			'download_mode'  => $mode,
			'quality'        => $quality,
			'max_file_size'  => $max_file_size,
		);
	}
}

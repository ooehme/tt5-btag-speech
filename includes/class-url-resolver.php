<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

use InvalidArgumentException;

final class URL_Resolver {
	private const LIST_BASE = 'https://www.bundestag.de/ajax/filterlist/de/mediathek/536668-536668';
	private const VIDEO_BASE = 'https://www.bundestag.de/mediathek/video';
	private const CDN_BASE = 'https://cldf-od.r53.cdn.tv1.eu/1000153copo/ondemand/app144277506/145293313';

	/**
	 * @return array<string,string>
	 */
	public static function quality_profiles(): array {
		return array(
			'1080p_8000' => 'h264_1920_1080_8000kb_baseline_de_8000',
			'360p_1000'  => 'h264_640_360_1000kb_baseline_de_1000',
		);
	}

	public function list_url( string $speaker_id, string $speaker_filter = '' ): string {
		$speaker_id = $this->numeric_id( $speaker_id, 'speaker' );
		$speaker_filter = '' === trim( $speaker_filter ) ? $speaker_id : strtoupper( trim( $speaker_filter ) );
		if ( ! preg_match( '/^\d+(?:\s+OR\s+\d+)*$/', $speaker_filter ) ) {
			throw new InvalidArgumentException( 'Invalid speaker filter.' );
		}
		$speaker_filter = preg_replace( '/\s+/', ' ', $speaker_filter ) ?? $speaker_id;
		return self::LIST_BASE . '?' . http_build_query(
			array(
				'documenttype' => '442354#BTFaisSpeechRecord',
				'filterset'    => 'plenum',
				'mediaCategory' => '442350#Plenarsitzungen',
				'noFilterSet'  => 'false',
				'rednerId'     => $speaker_id,
				'rednerIds'    => '442354#' . $speaker_filter,
				'scroll'       => 'mod536668',
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	public function video_url( string $video_id ): string {
		return self::VIDEO_BASE . '?videoid=' . rawurlencode( $this->numeric_id( $video_id, 'video' ) );
	}

	public function download_url( string $video_id, string $quality = '1080p_8000' ): string {
		$video_id = $this->numeric_id( $video_id, 'video' );
		$profiles = self::quality_profiles();
		if ( ! isset( $profiles[ $quality ] ) ) {
			throw new InvalidArgumentException( 'Unknown video quality profile.' );
		}

		$file = $video_id . '_' . $profiles[ $quality ] . '.mp4';
		return self::CDN_BASE . '/' . $video_id . '/' . rawurlencode( $file ) . '?fdl=1';
	}

	public function absolute_bundestag_url( string $url ): string {
		$url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		} elseif ( str_starts_with( $url, '/' ) ) {
			$url = 'https://www.bundestag.de' . $url;
		}

		if ( ! $this->is_allowed_url( $url ) ) {
			throw new InvalidArgumentException( 'Disallowed Bundestag URL.' );
		}

		return $url;
	}

	public function is_allowed_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
			return false;
		}
		if ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) {
			return false;
		}

		$host = strtolower( (string) ( $parts['host'] ?? '' ) );
		return in_array(
			$host,
			array(
				'www.bundestag.de',
				'bundestag.de',
				'cldf-od.r53.cdn.tv1.eu',
			),
			true
		);
	}

	private function numeric_id( string $id, string $type ): string {
		if ( ! preg_match( '/^\d+$/', $id ) ) {
			throw new InvalidArgumentException( sprintf( 'Invalid %s ID.', $type ) );
		}
		return $id;
	}
}

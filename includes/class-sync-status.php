<?php

declare(strict_types=1);

namespace MDB\BundestagSpeeches;

final class Sync_Status {
	public const EMBED_AVAILABLE = 'embed_available';
	public const DOWNLOAD_PENDING = 'download_pending';
	public const DOWNLOADED = 'downloaded';
	public const DOWNLOAD_FAILED = 'download_failed';
	public const SYNC_ERROR = 'sync_error';
	public const NOT_SEEN = 'not_seen';

	public static function after_seen( string $mode, int $attachment_id, string $previous = '' ): string {
		if ( $attachment_id > 0 ) {
			return self::DOWNLOADED;
		}
		if ( self::DOWNLOAD_FAILED === $previous ) {
			return self::DOWNLOAD_FAILED;
		}
		return 'automatic' === $mode ? self::DOWNLOAD_PENDING : self::EMBED_AVAILABLE;
	}

	public static function is_downloadable( string $status ): bool {
		return in_array(
			$status,
			array( self::EMBED_AVAILABLE, self::DOWNLOAD_PENDING, self::DOWNLOAD_FAILED, self::SYNC_ERROR ),
			true
		);
	}

	/**
	 * @return array<int,string>
	 */
	public static function all(): array {
		return array(
			self::EMBED_AVAILABLE,
			self::DOWNLOAD_PENDING,
			self::DOWNLOADED,
			self::DOWNLOAD_FAILED,
			self::SYNC_ERROR,
			self::NOT_SEEN,
		);
	}
}

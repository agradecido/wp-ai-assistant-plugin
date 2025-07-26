<?php
/**
 * DownloadRepository Interface
 *
 * @package WPAIS\Domain\Download
 * @since 1.0
 */

namespace WPAIS\Domain\Download;

/**
 * Interface DownloadRepository
 *
 * @package WPAIS\Domain\Download
 */
interface DownloadRepository {

	/**
	 * Save a download record.
	 *
	 * @param Download $download
	 * @return void
	 */
	public function save( Download $download): void;
}

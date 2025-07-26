<?php
/**
 * Download Entity
 *
 * @package WPAIS\Domain\Download
 * @since 1.0
 */

namespace WPAIS\Domain\Download;

/**
 * Class Download
 *
 * Represents a single download record.
 *
 * @package WPAIS\Domain\Download
 */
class Download {

	private int $id;
	private string $file_name;
	private string $file_path;
	private ?int $user_id;
	private string $ip_address;
	private string $downloaded_at;

	/**
	 * Download constructor.
	 *
	 * @param string $file_name
	 * @param string $file_path
	 * @param string $ip_address
	 * @param int|null $user_id
	 */
	public function __construct( string $file_name, string $file_path, string $ip_address, ?int $user_id = null ) {
		$this->file_name   = $file_name;
		$this->file_path   = $file_path;
		$this->ip_address  = $ip_address;
		$this->user_id     = $user_id;
	}

	/**
	 * Get the file name.
	 *
	 * @return string
	 */
	public function get_file_name(): string {
		return $this->file_name;
	}

	/**
	 * Get the file path.
	 *
	 * @return string
	 */
	public function get_file_path(): string {
		return $this->file_path;
	}

	/**
	 * Get the user ID.
	 *
	 * @return int|null
	 */
	public function get_user_id(): ?int {
		return $this->user_id;
	}

	/**
	 * Get the IP address.
	 *
	 * @return string
	 */
	public function get_ip_address(): string {
		return $this->ip_address;
	}
}

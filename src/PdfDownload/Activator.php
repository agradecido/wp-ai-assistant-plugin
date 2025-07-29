<?php
namespace WPAIS\PdfDownload;

use WPAIS\PdfDownload\Database\Schema;

/**
 * Handles activation tasks for the PDF download module.
 */
final class Activator {
	/**
	 * Run activation hooks.
	 */
	public static function activate(): void {
		Schema::create_table();
		flush_rewrite_rules();
	}
}

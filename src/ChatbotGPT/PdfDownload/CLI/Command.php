<?php
namespace ChatbotGPT\PdfDownload\CLI;

use WP_CLI;

/**
 * CLI commands for PDF management.
 */
class Command {
    /**
     * Register CLI commands.
     */
    public static function register(): void {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('cgcof pdf list', [static::class, 'list_files']);
            WP_CLI::add_command('cgcof pdf stats', [static::class, 'stats']);
            WP_CLI::add_command('cgcof pdf import', [static::class, 'import']);
        }
    }

    public static function list_files(): void {
        $files = glob(WP_CONTENT_DIR . '/private-pdfs/*.pdf');
        foreach ($files as $file) {
            WP_CLI::line(basename($file));
        }
    }

    public static function stats(): void {
        WP_CLI::line('Not implemented');
    }

    public static function import($args, $assoc): void {
        WP_CLI::line('Not implemented');
    }
}

<?php
namespace ChatbotGPT\PdfDownload\Entity;

/**
 * Represents a download record.
 */
class DownloadRecord {
    public function __construct(
        public int $user_id,
        public string $file_name,
        public string $downloaded_at,
        public ?string $ip_address
    ) {}
}

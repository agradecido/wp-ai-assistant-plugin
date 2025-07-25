<?php
namespace ChatbotGPT\PdfDownload\Service;

use ChatbotGPT\PdfDownload\Repository\DownloadRepository;

/**
 * Business logic for secure PDF downloads.
 */
class DownloadService {
    public function __construct(private DownloadRepository $repository) {}

    /**
     * Stream a PDF file after recording the download.
     */
    public function download(string $slug): void {
        $slug = sanitize_title_with_dashes($slug);
        $file = WP_CONTENT_DIR . '/private-pdfs/' . $slug . '.pdf';
        if (validate_file($file) !== 0 || ! is_file($file) || strpos($file, WP_CONTENT_DIR . '/private-pdfs/') !== 0) {
            wp_die(__('File not found or invalid path', 'wp-ai-assistant'), 404);
            wp_die(__('File not found', 'wp-ai-assistant'), 404);
        }

        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $this->repository->record($user_id, basename($file), $ip);

        @ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}

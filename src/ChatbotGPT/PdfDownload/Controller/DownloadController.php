<?php
namespace ChatbotGPT\PdfDownload\Controller;

use ChatbotGPT\PdfDownload\Service\DownloadService;

/**
 * Route handler for friendly download URLs.
 */
class DownloadController {
    public function __construct(private DownloadService $service) {}

    public function __invoke(): void {
        $slug = get_query_var('download_file');
        if (empty($slug)) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_die(__('Private content. Please log in first.', 'wp-ai-assistant'), 401);
        }

        $this->service->download($slug);
    }
}

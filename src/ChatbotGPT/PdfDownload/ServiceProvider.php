<?php
namespace ChatbotGPT\PdfDownload;

use ChatbotGPT\PdfDownload\Controller\DownloadController;
use ChatbotGPT\PdfDownload\Repository\DownloadRepository;
use ChatbotGPT\PdfDownload\Service\DownloadService;
use ChatbotGPT\PdfDownload\Admin\StatsPage;
use ChatbotGPT\PdfDownload\CLI\Command;

/**
 * Registers PDF download services.
 */
class ServiceProvider {
    private DownloadController $controller;

    /**
     * Register hooks and services.
     */
    public function register(): void {
        global $wpdb;
        $repository = new DownloadRepository($wpdb);
        $service    = new DownloadService($repository);
        $this->controller = new DownloadController($service);

        (new StatsPage($repository))->register();
        Command::register();

        add_action('init', [$this, 'register_rewrites']);
        add_action('template_redirect', $this->controller);
    }

    /**
     * Register rewrite rules.
     */
    public function register_rewrites(): void {
        add_rewrite_tag('%download_file%', '([^/]+)');
        add_rewrite_rule('^download/([^/]+)/?$', 'index.php?download_file=$matches[1]', 'top');
    }
}

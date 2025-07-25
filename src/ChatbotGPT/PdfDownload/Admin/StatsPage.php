<?php
namespace ChatbotGPT\PdfDownload\Admin;

use ChatbotGPT\PdfDownload\Repository\DownloadRepository;

/**
 * Admin submenu to display download statistics.
 */
class StatsPage {
    public function __construct(private DownloadRepository $repository) {}

    /**
     * Register the submenu page.
     */
    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu(): void {
        add_submenu_page(
            'tools.php',
            __('PDF Downloads', 'wp-ai-assistant'),
            __('PDF Downloads', 'wp-ai-assistant'),
            'manage_options',
            'pdf-downloads',
            [$this, 'render']
        );
    }

    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html__('PDF Downloads', 'wp-ai-assistant') . '</h1>';
        $table = new DownloadListTable($this->repository);
        $table->prepare_items();
        echo '<form method="post">';
        $table->display();
        echo '</form></div>';
    }
}

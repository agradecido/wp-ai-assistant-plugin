<?php
use ChatbotGPT\PdfDownload\Repository\DownloadRepository;
use WP_Mock as M;
use Mockery;

class DownloadRepositoryTest extends PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        M::setUp();
    }

    protected function tearDown(): void {
        M::tearDown();
        Mockery::close();
    }

    public function test_record_inserts_row(): void {
        $db = Mockery::mock('wpdb');
        $db->prefix = 'wp_';
        $db->shouldReceive('insert')
            ->once()
            ->with('wp_pdf_downloads', Mockery::type('array'), Mockery::type('array'));

        M::userFunction('current_time', ['return' => '2024-01-01 00:00:00']);

        $repo = new DownloadRepository($db);
        $repo->record(1, 'test.pdf', '127.0.0.1');

        $this->addToAssertionCount(1);
    }
}

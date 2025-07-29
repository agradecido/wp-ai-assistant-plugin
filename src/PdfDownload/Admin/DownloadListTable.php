<?php
namespace WPAIS\PdfDownload\Admin;

use WP_List_Table;
use WPAIS\PdfDownload\Repository\DownloadRepository;

/**
 * Table to list download statistics.
 */
class DownloadListTable extends WP_List_Table {
	public function __construct( private DownloadRepository $repository ) {
		parent::__construct(
			array(
				'plural'   => 'downloads',
				'singular' => 'download',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'file_name'     => __( 'File', 'wp-ai-assistant' ),
			'user'          => __( 'User', 'wp-ai-assistant' ),
			'downloaded_at' => __( 'Date/Time', 'wp-ai-assistant' ),
			'ip_address'    => __( 'IP', 'wp-ai-assistant' ),
		);
	}

	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	public function prepare_items(): void {
		$current_page = $this->get_pagenum();
		$per_page     = 20;
		$offset       = ( $current_page - 1 ) * $per_page;
		$records      = $this->repository->all( $offset, $per_page );
		$total_items  = $this->repository->count();
		$this->items  = $records;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}
}

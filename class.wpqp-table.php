<?php
if ( ! class_exists( "WP_List_Table" ) ) {
	require_once( ABSPATH . "wp-admin/includes/class-wp-list-table.php" );
}

class WPQP_Table extends WP_List_Table {
	private $_items;

	function __construct( $data ) {
		$this->_items = $data;
	}

	function get_columns() {
		return [
			'cb'     => '<input type="checkbox"/>',
			'name'   => __( 'Name', 'wp-quick-provision' ),
			'slug'   => __( 'Source', 'wp-quick-provision' ),
			'source' => __( 'Source', 'wp-quick-provision' ),
		];
	}

	function column_cb( $item ) {
		return "<input type='checkbox' value='{$item['id']}'/>";
	}

	function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $this->_items;
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}
}
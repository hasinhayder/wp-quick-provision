<?php
if ( ! class_exists( "WP_List_Table" ) ) {
	require_once( ABSPATH . "wp-admin/includes/class-wp-list-table.php" );
}

class WPQP_Table extends WP_List_Table {
	private $_items;
	private $checkbox_field_name = 'items';

	function __construct( $data, $type='items' ) {
		parent::__construct();
		$this->_items = $data;
		$this->checkbox_field_name = $type;
	}

	function get_columns() {
		return [
			'cb'          => '<input type="checkbox">',
			'slug'        => __( 'Slug', 'wp-quick-provision' ),
//			'origin'      => __( 'Origin', 'wp-quick-provision' ),
			'installable' => __( 'Install From', 'wp-quick-provision' ),
		];
	}

	function column_cb( $item ) {
		return "<input type='checkbox' name='wpqp_{$this->checkbox_field_name}[]' value='{$item['slug']}' checked/>";
	}

	function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $this->_items;
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

}
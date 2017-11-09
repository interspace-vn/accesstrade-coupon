<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Nhymxu_AT_Coupon_List extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		
		$perPage = 50;
        $currentPage = $this->get_pagenum();

        $data = $this->table_data( $perPage, $currentPage );
		
		$totalItems = $this->get_number_of_records();
		
        $this->set_pagination_args( [
            'total_items' => $totalItems,
            'per_page'    => $perPage
		] );
		
		$this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;
	}
	
	private function get_number_of_records() {
		global $wpdb;

		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}coupons" );
	}

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = [
            'id'        => 'ID',
			'title'     => 'Tiêu đề',
			'type'		=> 'Merchant',
			'exp'		=> 'Ngày hết hạn',
			'note'		=> 'Ghi chú',
			'save'		=> 'Giảm'
        ];
        return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return [];
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return [
			'title' => ['title', false],
			'id' => ['id', false],
			'type' => ['type', false],
			'exp' => ['exp', false],
		];
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data( $per_page = 50, $page_number = 1 )
    {
		global $wpdb;

		$sql = "SELECT id, title, type, exp, note, save FROM {$wpdb->prefix}coupons";
		
		if ( !empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$results = $wpdb->get_results( $sql, ARRAY_A );	
	
		return $results;
    }
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
			case 'title':
			case 'type':
			case 'exp':
			case 'note':
			case 'save':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }
}

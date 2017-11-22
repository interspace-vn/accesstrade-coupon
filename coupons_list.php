<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Nhymxu_AT_Coupon_List extends WP_List_Table
{
    public $active_filter = '';
    public $filters = [];
    public $search_key = '';

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        global $wpdb;

        $this->search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

        // Get filters data
        $results = $wpdb->get_results( "SELECT type FROM {$wpdb->prefix}coupons GROUP BY type" );
        if( !empty( $results ) ) {
            foreach( $results as $row ) {
                $this->filters[] = $row->type;
            }
        }

		if ( isset( $_REQUEST['filter_merchant'] ) && in_array( $_REQUEST['filter_merchant'], $this->filters ) ) {
			$this->active_filter = $_REQUEST['filter_merchant'];
		} else {
			$this->active_filter = '';
        }
        // END Get filters data

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

        if( $this->active_filter != '' ) {
            return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}coupons WHERE type = '{$this->active_filter}'" );
        }

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
            'cb'        => '<input type="checkbox">',
			'title'     => 'Tiêu đề',
            'type'		=> 'Merchant',
            'code'      => 'Mã giảm giá',
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

		$sql = "SELECT id, title, type, code, exp, note, save FROM {$wpdb->prefix}coupons";
        
        if( $this->active_filter != '' ) {
            $sql .= ' WHERE type = "'. $_REQUEST['filter_merchant'] .'"';
        }

        if( $this->search_key != '' ) {
            $sql .= ' AND title LIKE "%'. $this->search_key .'%"';
        }

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
            case 'title':
            case 'code':
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

    /**
	 * Allow filter per merchant
	 */
	function extra_tablenav( $which ) {
        ?><div class="alignleft actions"><?php
        if ( 'top' == $which ) {
            if ( ! empty( $this->filters ) ):
            ?>
            <select id="filter_merchant" name="filter_merchant">
                <option value="">Tất cả merchant</option>
                <?php foreach ( $this->filters as $merchant ): ?>
                    <option value="<?=esc_attr( $merchant );?>" <?=( $this->active_filter == $merchant ) ? 'selected' : '' ;?>><?=esc_attr( $merchant );?></option>
                <?php endforeach; ?>
            </select>
            <input id="btn-filter" type="submit" class="button" value="Lọc">
            <?php endif;
        }
        ?></div><?php
    }

    /**
     * Get value for checkbox column.
     *
     * @param object $item  A row's data.
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $item ) {
        $output = '<label class="screen-reader-text" for="coupon_' . $item['id'] . '">Chọn' . $item['title'] . '</label>';
        $output .= '<input type="checkbox" class="input_coupon_bulk_action" name="coupons[]" id="coupon_'. $item['id'] .'" value="'. $item['id'] .'">';
        return $output;
    }


    public function get_bulk_actions() {
        return [];
        /*
        * on hitting apply in bulk actions the url paramas are set as
        * ?action=bulk-delete&paged=1&action2=-1
        * 
        * action and action2 are set based on the triggers above and below the table		 		    
        */
        $actions = ['bulk-delete' => 'Xóa coupon'];
        return $actions;
    }

    /*
    * Method for rendering the title column.
    * Adds row action links to the title column.
    * e.g. url/admin.php?page=accesstrade_coupon_addnew&coupon_id=1
    */
    protected function column_title( $item ) {		
        $admin_page_url =  admin_url('admin.php');
        // row action to view usermeta.
        $query_args_editcoupon = array(
            'page'		=>  wp_unslash( 'accesstrade_coupon_addnew' ),
            'coupon_id'	=> absint( $item['id']),
        );
        $editcoupon_link = esc_url( add_query_arg( $query_args_editcoupon, $admin_page_url ) );		
        $actions['edit_coupon'] = '<a href="' . $editcoupon_link . '">Sửa</a>';		
        $actions['delete_coupon'] = '<a href="javascript:void(0);" onclick="nhymxu_delete_coupon(\''. $item['id'] .'\', \''. $item['code'] .'\');">Xóa</a>';
        // similarly add row actions for add usermeta.
        //$_GET$row_value = '<strong>' . $item['title'] . '</strong>';
        $row_value = $item['title'];        
        return $row_value . $this->row_actions( $actions );
    }
}

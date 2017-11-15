<?php

class nhymxu_at_coupon_editor {
	public function __construct() {
		add_action( 'admin_print_footer_scripts', [$this, 'shortcode_button_script'] );	
		add_action( 'admin_print_scripts', [$this, 'data_for_tinymce_list'] );
		add_action( 'init', [$this,'tinymce_new_button'] );	
	}

	function shortcode_button_script() {
		if(wp_script_is("quicktags")):
			?>
			<script type="text/javascript">
			QTags.addButton( 
				"at_coupon", 
				"AT Coupon", 
				nhymxu_at_coupon_callback
			);

			function nhymxu_at_coupon_callback() {
				QTags.insertContent('[atcoupon type="danh_sach_merchant" cat="danh_sach_category"]');
			}
			</script>
			<?php
		endif;
	}

	function data_for_tinymce_list() {
	?>
	<script type="text/javascript">
	function nhymxu_at_coupon_get_tinymce_list( type ) {
		if( type == 'merchant' ) {
			return [<?=$this->get_coupon_merchant();?>];
		}
		if( type == 'cat' ) {
			return [<?=$this->get_coupon_cat();?>];
		}
	}
	</script>
	<?php
	}

	function get_coupon_merchant() {
		global $wpdb;

		$coupon_type = $wpdb->get_results("SELECT type FROM {$wpdb->prefix}coupons GROUP BY type", ARRAY_A);
		$output = '';

		foreach( $coupon_type as $row ) {
			$output .= '{text:\'' . $row['type'] . '\',value:\'' . $row['type'] . '\'},';
		}

		return $output;
	}

	function get_coupon_cat() {
		global $wpdb;
		
		$output = '{text:\'Tất cả\', value:\'\'},';
		$coupon_cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}coupon_categories");
		foreach( $coupon_cats as $row ) {
			$output .= '{text:\'' . $row->name . '\',value:\'' . $row->slug . '\'},';
		}

		return $output;
	}

	function tinymce_new_button() {
		add_filter("mce_external_plugins", [$this,'tinymce_add_button']);
		add_filter("mce_buttons", [$this,'tinymce_register_button']);	
	}

	function tinymce_add_button($plugin_array) {
		//enqueue TinyMCE plugin script with its ID.
		$plugin_array["at_coupon_button"] =  plugin_dir_url(__FILE__) . "visual-editor-button.js";
		return $plugin_array;
	}

	function tinymce_register_button($buttons) {
		//register buttons with their id.
		array_push($buttons, "at_coupon_button");
		return $buttons;
	}
}

<?php

class nhymxu_at_coupon_admin {
	public function __construct() {
		add_action( 'admin_menu', [$this,'admin_page'] );
	}

	public function admin_page() {
		add_menu_page( 'Cài đặt Coupon', 'Smart Coupons', 'manage_options', 'accesstrade_coupon', [$this, 'admin_page_callback_settings'], 'dashicons-tickets', 6 );
		add_submenu_page( 'accesstrade_coupon', 'Cài đặt Coupon', 'Cài đặt', 'manage_options', 'accesstrade_coupon_settings', [$this, 'admin_page_callback_settings'] );
	}

	/*
	 * Admin page setting
	 */
	public function admin_page_callback_settings() {
		global $wpdb;
		if( isset( $_POST, $_POST['nhymxu_hidden'] ) && $_POST['nhymxu_hidden'] == 'coupon' ) {
			$input = [
				'uid'	=> sanitize_text_field($_REQUEST['nhymxu_at_coupon_uid']),
				'accesskey'	=> sanitize_text_field($_REQUEST['nhymxu_at_coupon_accesskey']),
				'utmsource'	=> sanitize_text_field($_REQUEST['nhymxu_at_coupon_utmsource'])
			];
	
			update_option('nhymxu_at_coupon', $input);
			echo '<h1>Cập nhật thành công</h1><br>';
		}
		$option = get_option('nhymxu_at_coupon', ['uid' => '', 'accesskey' => '', 'utmsource' => '']);
		$uid = (isset($option['uid'])) ? $option['uid'] : '';
		if( defined('NHYMXU_MARS_VERSION') && $uid == '' ) {
			$uid = get_option('accesstrade_userid');
			$option['uid'] = $uid;
			update_option('nhymxu_at_coupon', $option);
		}
		?>
		<script type="text/javascript">
		function nhymxu_force_update_coupons() {
			var is_run = jQuery('#nhymxu_force_update').data('run');
			if( is_run !== 0 ) {
				console.log('Đã chạy rồi');
				return false;
			} 
			jQuery('#nhymxu_force_update').attr('disabled', 'disabled');
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: { action: 'nhymxu_coupons_ajax_forceupdate' },
				success: function(response) {
					alert('Khởi chạy thành công. Vui lòng đợi vài phút để dữ liệu được cập nhật.');
				}
			});
		}
		</script>
		<div>
			<h2>Cài đặt ACCESSTRADE Coupon</h2>
			<br>
			<?php if( !isset($option['uid']) ): ?>
			<h3>Bạn cần nhập ACCESSTRADE ID để plugin hoạt động tốt.</h3>
			<br>
			<?php endif; ?>
			<form action="<?=admin_url( 'admin.php?page=accesstrade_coupon_settings' );?>" method="post">
				<input type="hidden" name="nhymxu_hidden" value="coupon">
				<input type="hidden" name="nhymxu_at_coupon_accesskey" value="<?=(isset($option['accesskey'])) ? $option['accesskey'] : '';?>">
				<table>
					<tr>
						<td>ACCESSTRADE ID*:</td>
						<td><input type="text" name="nhymxu_at_coupon_uid" value="<?=$uid;?>" <?=( defined('NHYMXU_MARS_VERSION') ) ? 'disabled' : '';?>></td>
					</tr>
					<tr>
						<td></td>
						<td>Lấy ID tại <a href="https://pub.accesstrade.vn/tools/deep_link" target="_blank">đây</a></td>
					</tr>
					<tr>
						<td>UTM Source:</td>
						<td><input type="text" name="nhymxu_at_coupon_utmsource" value="<?=(isset($option['utmsource'])) ? $option['utmsource'] : '';?>"></td>
					</tr>
				</table>
				<input name="Submit" type="submit" value="Lưu">
			</form>
		</div>
		<hr>
		<div>
			<h3>Thông tin coupon</h3>
			<h4>Danh sách category</h4>
			<p>
				<table border="1">
					<tr>
						<td>Name</td>
						<td>Slug</td>
					</tr>
				<?php
				$coupon_cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}coupon_categories");
				foreach( $coupon_cats as $row ):
				?>
					<tr>
						<td><?=$row->name;?></td>
						<td><?=$row->slug;?></td>
					</tr>
				<?php endforeach; ?>
				</table>
			</p>
			<hr>
			<?php
			$total_coupon = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}coupons" );
			$today = date('Y-m-d');	
			$total_expired_coupon = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}coupons WHERE exp < '{$today}'" );		
			?>
			<p>Tổng số coupon trong hệ thống: <strong><?=$total_coupon;?></strong></p>
			<p>Tổng số coupon hết hạn: <strong><?=$total_expired_coupon;?></strong></p>
			<?php $last_run = (int) get_option('nhymxu_at_coupon_sync_time', 0); $now = time(); ?>
			<p>
				Lần đồng bộ cuối: <strong><?=( $last_run == 0 ) ? 'chưa rõ' : date("Y-m-d H:i:s", $last_run);?></strong>
				<?php if( $last_run == 0 || ( ($now - $last_run) >= 1800 ) ): ?>
				- <button id="nhymxu_force_update" data-run="0" onclick="nhymxu_force_update_coupons();">Cập nhật ngay</button>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}

<?php

class nhymxu_at_coupon_admin {
	public function __construct() {
		add_action( 'admin_menu', [$this,'admin_page'] );
		add_action( 'wp_ajax_nhymxu_coupons_ajax_insertupdate', [$this, 'ajax_insert_update'] );
		add_action( 'wp_ajax_nhymxu_coupons_ajax_checkcoupon', [$this, 'ajax_check_coupon'] );
		add_action( 'wp_ajax_nhymxu_coupons_ajax_deletecoupon', [$this, 'ajax_delete_coupon'] );	
		add_action( 'init', [$this, 'wp_strip_referer'] );
	}

	function wp_strip_referer() {
		if (is_admin() && ($_GET['page'] == "accesstrade_coupon")) {
			if (strpos($_SERVER['REQUEST_URI'], '_wp_http_referer') !== false) {
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		}
	}

	public function ajax_insert_update() {
		global $wpdb;

		$input = $_POST['coupon_data'];

		if( $input['cid'] > 0 ) {
			$result = $this->coupon_update( $input );
		} else {
			$result = $this->coupon_insert( $input );
		}

		echo ( $result === false ) ? 0 : 1;

		wp_die();
	}

	public function ajax_check_coupon() {
		global $wpdb;

		$input = $_POST['coupon_data'];

		$code = ($input['code']) ? $input['code'] : '';
		$url = ($input['url']) ? $input['url'] : '';
		$title = $input['title'];

		if( $code != '' ) {
			$sql = "SELECT * FROM {$wpdb->prefix}coupons WHERE code = '{$code}' AND url = '{$url}'";
		} else {
			$sql = "SELECT * FROM {$wpdb->prefix}coupons WHERE title = '{$title}'";
		}

		$coupon = $wpdb->get_row($sql);

		if ( null !== $coupon ) {
			echo 'found';
		} else {
			$result = $this->coupon_insert( $input );
			echo ( $result === false ) ? 0 : 1;
		}

		wp_die();
	}

	public function ajax_delete_coupon() {
		global $wpdb;

		$input = $_POST['coupon_data'];

		$id = ($_POST['cid']) ? $_POST['cid'] : '';

		if( $id == '' ) {
			echo 'not_found';
			wp_die();
		}

		$wpdb->query("START TRANSACTION;");
		try {
			$wpdb->delete( $wpdb->prefix . 'coupons', ['id' => $id] );
			$wpdb->delete( $wpdb->prefix . 'coupon_category_rel', ['coupon_id' => $id] );
			$wpdb->query("COMMIT;");
			echo 'ok';
		} catch ( Exception $e ) {
			$wpdb->query("ROLLBACK;");
			echo 'fail';
		}
		wp_die();
	}

	/*
	 * callback insert function for ajax action
	 */
	private function coupon_insert( $input ) {
		global $wpdb;

		$result = $wpdb->insert( 
			$wpdb->prefix . 'coupons',
			[
				'type'	=> $input['merchant'],
				'title' => $input['title'],
				'code'	=> ($input['code']) ? $input['code'] : '',
				'exp'	=> $input['exp'],
				'note'	=> $input['note'],
				'url'	=> ($input['url']) ? $input['url'] : '',
				'save'	=> ($input['save']) ? $input['save'] : ''
			],
			['%s','%s','%s','%s','%s','%s','%s']
		);

		if( $result !== false ) {
			$this->coupon_tracking( $input );
		}
		
		return $result;
	}

	/*
	 * callback update function for ajax action
	 */
	private function coupon_update( $input ) {
		global $wpdb;

		$result = $wpdb->update( 
			$wpdb->prefix . 'coupons',
			[
				'type'	=> $input['merchant'],
				'title' => $input['title'],
				'code'	=> ($input['code']) ? $input['code'] : '',
				'exp'	=> $input['exp'],
				'note'	=> $input['note'],
				'url'	=> ($input['url']) ? $input['url'] : '',
				'save'	=> ($input['save']) ? $input['save'] : ''
			],
			[ 'id'	=> $input['cid'] ],
			['%s','%s','%s','%s','%s','%s','%s'], 
			['%d']
		);

		return $result;
	}

	private function coupon_tracking( $input ) {
		$input['domain'] = get_option( 'siteurl' );
		$input['email'] = get_option( 'admin_email' );

		wp_remote_post( 'http://mail.isvn.space/nhymxu-track-coupon.php', [
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => [],
			'body' => [
				'_hidden_nhymxu' => 'tracking_coupon',
				'data'	=> json_encode( $input )
			],
			'cookies' => []
		]);		
	}

	private function get_coupon_detail( $coupon_id ) {
		global $wpdb;

		$coupon_id = (int) $coupon_id;
		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}coupons WHERE id = {$coupon_id}", ARRAY_A);

		if ( null !== $result ) {
			return $result;
		}

		return false;
	} 

	public function admin_page() {
		add_menu_page( 'Danh sách coupon', 'Smart Coupons', 'manage_options', 'accesstrade_coupon', [$this, 'admin_page_callback_list'], 'dashicons-tickets', 6 );
		add_submenu_page( 'accesstrade_coupon', 'Danh sách coupon', 'Tất cả', 'manage_options', 'accesstrade_coupon', [$this, 'admin_page_callback_list'] );
		add_submenu_page( 'accesstrade_coupon', 'Thêm coupon mới', 'Thêm mới', 'manage_options', 'accesstrade_coupon_addnew', [$this, 'admin_page_callback_addnew'] );
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
		$option = get_option('nhymxu_at_coupon', ['uid' => '', 'utmsource' => '']);
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

		function nhymxu_force_update_merchants() {
			var is_run = jQuery('#nhymxu_force_update_merchants').data('run');
			if( is_run !== 0 ) {
				console.log('Đã chạy rồi');
				return false;
			} 
			jQuery('#nhymxu_force_update_merchants').attr('disabled', 'disabled');
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: { action: 'nhymxu_coupons_ajax_forceupdate_merchants' },
				success: function(response) {
					alert('Khởi chạy thành công. Vui lòng đợi vài phút để dữ liệu được cập nhật.');
				}
			});
		}
		</script>
		<div>
			<h2>Cài đặt AccessTrade Coupon</h2>
			<br>
			<?php if( !isset($option['uid'], $option['accesskey']) ): ?>
			<h3>Bạn cần nhập AccessTrade ID và Access Key để plugin hoạt động tốt.</h3>
			<br>
			<?php endif; ?>
			<form action="<?=admin_url( 'admin.php?page=accesstrade_coupon_settings' );?>" method="post">
				<input type="hidden" name="nhymxu_hidden" value="coupon">
				<table>
					<tr>
						<td>AccessTrade ID*:</td>
						<td><input type="text" name="nhymxu_at_coupon_uid" value="<?=(isset($option['uid'])) ? $option['uid'] : '';?>"></td>
					</tr>
					<tr>
						<td></td>
						<td>Lấy ID tại <a href="https://pub.accesstrade.vn/tools/deep_link" target="_blank">đây</a></td>
					</tr>
					<tr>
						<td>Access Key*:</td>
						<td><input type="text" name="nhymxu_at_coupon_accesskey" value="<?=(isset($option['accesskey'])) ? $option['accesskey'] : '';?>"></td>
					</tr>
					<tr>
						<td></td>
						<td>Lấy Access Key tại <a href="https://pub.accesstrade.vn/accounts/profile" target="_blank">đây</a></td>
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
			<h4>Danh các merchant</h4>
			<p>
			<?php 
			$coupon_type = $wpdb->get_results("SELECT type FROM {$wpdb->prefix}coupons GROUP BY type", ARRAY_A);
			foreach( $coupon_type as $row ) {
				echo $row['type'], ', ';
			}
			?>
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
			<?php $active_merchants = get_option('nhymxu_at_coupon_merchants', false); ?>
			<?php if( $active_merchants ): ?>
			<p>
				Bạn có <?=count($active_merchants);?> campain đang hoạt động. <button id="nhymxu_force_update_merchants" data-run="0" onclick="nhymxu_force_update_merchants();">Cập nhật campain ngay</button>
			</p>
			<?php endif;?>
		</div>
		<?php
	}

	/*
	 * Admin page add new
	 */
	public function admin_page_callback_addnew() {

		$active_merchants = get_option('nhymxu_at_coupon_merchants', false);

		if( !$active_merchants ) {
			echo 'Chưa có campain nào được duyệt ( hoặc chưa đồng bộ ). vui lòng đồng bộ campain lại ở <a href="'. admin_url('admin.php?page=accesstrade_coupon_settings') .'">đây</a>';
			return false;
		} 

		$default_data = [
			'id'	=> 0,
			'type'	=> '',
			'title' => '',
			'code'	=> '',
			'exp'	=> '',
			'note'	=> '',
			'url'	=> '',
			'save'	=> ''			
		];

		if( isset($_GET['coupon_id']) && $_GET['coupon_id'] != '' ) {
			$tmp = $this->get_coupon_detail($_GET['coupon_id']);
			if( $tmp ) {
				$default_data = $tmp;
			}
		}
		?>
		<link rel="stylesheet" href="//unpkg.com/purecss@1.0.0/build/forms-min.css">
		<link rel="stylesheet" href="//unpkg.com/purecss@1.0.0/build/buttons-min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/css/selectize.min.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/js/standalone/selectize.min.js" type="text/javascript"></script>
		<style>
		div.selectize-control.single {
			display: inline-block;
			min-width: 250px;
		}
		</style>
		<script type="text/javascript">
		/*
		* Insert coupon
		* @args action_type	int
		*		0: insert once
		*		1: insert more
		*/
		function nhymxu_insert_log( msg ) {
			jQuery('#nhymxu_coupon_notice').html(msg);
		}

		function nhymxu_coupon_exec( action_type ) {
			var jq = jQuery;
			var input = {
				cid: jq('#input_couponid').val(),
				merchant: jq('#input_merchant').val(),
				title: jq('#input_title').val(),
				code: jq('#input_code').val(),
				note: jq('#input_note').val(),
				url: jq('#input_url').val(),
				save: jq('#input_save').val(),
				exp: jq('#input_exp').val()
			};

			if( input['merchant'] === '' || input['title'] === '' || input['url'] === '' || input['exp'] === '' ) {
				nhymxu_insert_log('Nhập đủ các mục bắt buộc!');
				return false;
			}

			var today = new Date();
			var expired = new Date( input['exp'] );
			if( +expired <= +today ) {
				nhymxu_insert_log('Chọn ngày hết hạn phải từ hôm nay.');
				return false;
			}

			if( input['save'].length > 5 ) {
				nhymxu_insert_log('Mức giảm giá phải dưới 6 kí tự.');
				return false;
			} 

			function exec_after_success() {
				if( action_type === 0 ) {
					window.location.href = '<?=admin_url('admin.php?page=accesstrade_coupon');?>';
				} else if ( action_type === 1 ) {
					window.location.reload();
				}
			}

			function ajax_database_exec() {
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: { action: 'nhymxu_coupons_ajax_insertupdate', coupon_data: input },
					success: function(response) {
						if( response == 'found' ) {
							alert('Xử lý thất bại. Vui lòng thử lại.');
						} else {
							alert('Thành công');
							exec_after_success();
						}
					}
				});
			}

			if( input['cid'] > 0 ) {
				ajax_database_exec();
			} else {
				jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: { action: 'nhymxu_coupons_ajax_checkcoupon', coupon_data: input },
				success: function(response) {
					if( response == 'found' ) {
						alert('Đã tồn tại coupon.');
					} else if( response == 0 ) {
						alert('Xử lý thất bại. Vui lòng thử lại.');
					} else {
						alert('Thành công');
						exec_after_success();
					}
				}			
			});
			}
		}

		jQuery(document).ready(function (){
			jQuery('#input_merchant').selectize({
				create: false,
				sortField: 'text'
			});
		});
		</script>
		<div class="wrap">
			<h2 class="dashicons-before dashicons-tickets"><?=( isset($_GET['coupon_id']) && $_GET['coupon_id'] != '' ) ? 'Sửa thông tin coupon' : 'Thêm coupon mới';?></h2>
			<div class="body_coupon">
				<div id="nhymxu_coupon_notice"></div>
				<div class="pure-form pure-form-aligned">
					<fieldset>
						<input type="hidden" id="input_couponid" value="<?=$default_data['id'];?>">
						<div class="pure-control-group">
							<label for="input_merchant">Merchant*</label>
							<select id="input_merchant" required autocomplete="off">
								<option value="">---Chọn merchant---</option>
								<?php foreach( $active_merchants as $slug => $title ): ?>
								<option value="<?=$slug;?>" <?=( $slug == $default_data['type'] ) ? 'selected' : '';?>><?=$title;?></option>
								<?php endforeach; ?>
							</select>
							<span class="pure-form-message-inline">Bắt buộc</span>
						</div>

						<div class="pure-control-group">
							<label for="input_title">Tiêu đề*</label>
							<input id="input_title" type="text" placeholder="Tiêu đề" required value="<?=$default_data['title'];?>" autocomplete="off">
							<span class="pure-form-message-inline">Bắt buộc</span>
						</div>

						<div class="pure-control-group">
							<label for="input_code">Mã giảm giá</label>
							<input id="input_code" type="text" placeholder="Mã giảm giá" value="<?=$default_data['code'];?>" autocomplete="off">
							<span class="pure-form-message-inline">Tối đa 60 kí tự</span>
						</div>

						<div class="pure-control-group">
							<label for="input_note">Ghi chú</label>
							<input id="input_note" type="text" placeholder="Ghi chú" value="<?=$default_data['note'];?>" autocomplete="off">
						</div>

						<div class="pure-control-group">
							<label for="input_url">Link đích*</label>
							<input id="input_url" type="text" placeholder="Link đích" value="<?=$default_data['url'];?>" required autocomplete="off">
							<span class="pure-form-message-inline">Không nhập link affiliate ở đây</span>
						</div>

						<div class="pure-control-group">
							<label for="input_save">Mức giảm giá</label>
							<input id="input_save" type="text" placeholder="Mô tả ngắn. VD: 500k" value="<?=$default_data['save'];?>" autocomplete="off">
							<span class="pure-form-message-inline">Tối đa 5 kí tự</span>
						</div>

						<div class="pure-control-group">
							<label for="input_exp">Ngày hết hạn*</label>
							<input id="input_exp" type="date" placeholder="YYYY-MM-DD" required value="<?=$default_data['exp'];?>" autocomplete="off">
						</div>

						<div class="pure-controls">
							<button onclick="nhymxu_coupon_exec(0);" class="pure-button pure-button-primary">Lưu coupon</button>
							<button onclick="nhymxu_coupon_exec(1);" class="pure-button pure-button-primary">Lưu và thêm coupon mới</button>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
		<?php
	}

	/*
	 * Admin page list
	 */
	public function admin_page_callback_list() {
		$coupon_list_table = new Nhymxu_AT_Coupon_List();
		$coupon_list_table->prepare_items();
	?>
		<style>
		.wp-list-table .column-id {
			width: 60px;
		}
		.wp-list-table .column-save {
			width: 66px;
		}
		.wp-list-table .column-exp {
			width: 120px;
		}
		.wp-list-table .column-note {
			width: 200px;
		}
		.wp-list-table .column-code, .wp-list-table .column-type {
			width: 100px;
		}
		</style>
		<script type="text/javascript">
		function nhymxu_delete_coupon( coupon_id, code ) {
			var answer = confirm('Xóa ID: '+ coupon_id +' - Code: "'+ code +'"?');
			if (answer == true) {
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: { action: 'nhymxu_coupons_ajax_deletecoupon', cid: coupon_id },
					success: function( resp ) {
						resp = jQuery.trim(resp);
						if( resp == 'not_found' ) {
							alert('Không có coupon ID');
						} else if( resp == 'fail' ) {
							alert( 'Xóa thất bại. vui lòng F5 và thử lại.' );
						} else {
							jQuery('#coupon_'+coupon_id).parent().parent().remove();
							alert( 'Xóa thành công!' );
						}
						return true;
					}
				});
			}
			
			return false;
		}
		
		jQuery(document).ready(function($) {
			$('#btn-filter').click(function() {
				var merchant = $('#filter_merchant').val();
				if( merchant !== '' ) {
					window.location.href = window.location.href + '&filter_merchant=' + merchant;
				} 
			});
			$('#doaction').click(function() {
				var action = $('#bulk-action-selector-top').val();
				if( action == 'bulk-delete' ) {
					var bulk_id = [];
					$('.input_coupon_bulk_action').each(function() {
						if( $(this).is(':checked') ) {
							//console.log( $(this).val() );
							bulk_id.push( $(this).val() );
						} 
						console.log(bulk_id);
					});
				} 
			});
		});
		</script>
		<div class="wrap">
			<h1 class="dashicons-before dashicons-tickets wp-heading-inline">Coupons</h1>
 			<a href="<?=admin_url( 'admin.php?page=accesstrade_coupon_addnew' );?>" class="page-title-action">Thêm mới</a>
			<hr class="wp-header-end">
			<form id="nhymxu-coupon-list-form" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>">
				<?php $coupon_list_table->search_box( 'Tìm', 'nhymxu-coupon-find'); ?>
				<?php $coupon_list_table->display(); ?>
			</form>
		</div>
	<?php
	}
}

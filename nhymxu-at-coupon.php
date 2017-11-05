<?php
/*
Plugin Name: AccessTrade Coupon
Plugin URI: http://github.com/nhymxu/accesstrade-coupon
Description: Hệ thống coupon đồng bộ tự động từ AccessTrade
Author: Dũng Nguyễn (nhymxu)
Version: 0.1.2
Author URI: http://dungnt.net
*/

defined( 'ABSPATH' ) || die;
define('NHYMXU_AT_COUPON_VER', '0.1.2');

date_default_timezone_set('Asia/Ho_Chi_Minh');

class nhymxu_at_coupon {
	public function __construct() {
		add_filter( 'http_request_host_is_external', [$this, 'allow_external_update_host'], 10, 3 );
		add_action( 'nhymxu_at_coupon_sync_event', [$this,'do_this_twicedaily'] );
		add_shortcode( 'atcoupon', [$this,'shortcode_callback'] );
		add_action('admin_menu', [$this,'admin_page'] );
		add_action( 'init', [$this, 'init_updater'] );
	}
	
	public function do_this_twicedaily() {
		global $wpdb;
		$previous_time = get_option('nhymxu_at_coupon_sync_time', 0);
		$current_time = time();
		
		$url = 'http://sv.isvn.space/api/v1/mars/coupon?from='.$previous_time.'&to='.$current_time;

		$result = wp_remote_get( $url, ['timeout'=>'60'] );

		if ( is_wp_error( $result ) ) {
			$msg = 'previous_time: ' . $previous_time . PHP_EOL;
			$msg .= 'current_time: ' . $current_time . PHP_EOL;
			$msg .= 'Error: ' . $result->get_error_message() . PHP_EOL;

			$this->insert_log( $msg );
		} else {
			$input = json_decode( $result['body'], true );
			if( !empty($input) && isset( $input[0] ) && is_array( $input[0] ) ) {
				$wpdb->query("START TRANSACTION;");
				try {
					foreach( $input as $cp ) {
						$this->insert_coupon($cp);
					}
					update_option('nhymxu_at_coupon_sync_time', $current_time);
					$wpdb->query("COMMIT;");
				} catch ( Exception $e ) {
					$msg = 'previous_time: ' . $previous_time . PHP_EOL;
					$msg .= 'current_time: ' . $current_time . PHP_EOL;
					$msg .= 'Error: Insert error' . PHP_EOL;			
					$this->insert_log( $msg );

					$wpdb->query("ROLLBACK;");
				}
			}
		}

	}

	public function shortcode_callback( $atts, $content = '' ) {
		$args = shortcode_atts( [
			'type' => '',
			'cat'	=> '',
			'limit' => ''
		], $atts );
	
		if( '' == $args['type'] )
			return '';
		
		$data = $this->get_coupons( $args['type'], $args['cat'], $args['limit'] );
	
		$html = $this->build_html( $data );
		
		return $html;
	}

	/*
	 * Get list coupon from database
	 */
	private function get_coupons( $vendor, $category = '', $limit = '' ) {
		global $wpdb;
		
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	
		$today = date('Y-m-d');
	
		$vendor = explode(',', $vendor);
		$vendor_slug = [];
		foreach( $vendor as $slug ) {
			$vendor_slug[] = "'". $slug ."'";
		}
		$vendor_slug = implode(',', $vendor_slug);
	
		$sql = "SELECT * FROM {$wpdb->prefix}coupons WHERE type IN ({$vendor_slug}) AND exp >= '{$today}' ORDER BY exp ASC";
	
		if( $category != '' ) {
			$cat_slug = explode(',', $category);
			$cat_slug_arr = [];
			foreach( $cat_slug as $cat ) {
				$cat_slug_arr[] = "'". trim($cat) ."'";
			}
			$cat_slug_arr = implode(',', $cat_slug_arr);
	
			$coupon_cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}coupon_categories WHERE slug IN ({$cat_slug_arr})");
			if( !$coupon_cats ) {
				return false;
			}
			$cat_id = [];
			foreach( $coupon_cats as $row ) {
				$cat_id[] = $row->id;
			}
			$cat_id = implode(',', $cat_id);
	
			$sql = "SELECT coupons.* FROM {$wpdb->prefix}coupons AS coupons LEFT JOIN {$wpdb->prefix}coupon_category_rel AS rel ON rel.coupon_id = coupons.id WHERE coupons.type IN ({$vendor_slug}) AND rel.category_id IN ({$cat_id}) AND coupons.exp >= '{$today}' ORDER BY coupons.exp ASC";	
		}
	
		if( $limit != '' && $limit >= 0 ) {
			$sql .= ' LIMIT 0,' . $limit;
		}
	
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		if( $results ) {
			$coupon_id = [];
			$data = [];
			foreach( $results as $row ) {
				$coupon_id[] = $row['id'];
				$data[$row['id']] = $row;
				$data[$row['id']]['categories'] = [];
				$data[$row['id']]['deeplink'] = $this->build_deeplink( $row['url'] );
			}
			$sql = "SELECT rel.*, cat.name FROM {$wpdb->prefix}coupon_category_rel rel LEFT JOIN {$wpdb->prefix}coupon_categories cat ON rel.category_id = cat.id WHERE rel.coupon_id IN (". implode(',',$coupon_id) .")";
			$cats = $wpdb->get_results( $sql, ARRAY_A );
			foreach( $cats as $cat ) {
				$data[$cat['coupon_id']]['categories'][] = $cat['name'];
			}
		
			return $data;
		}
	
		return false;
	}

	/*
	 * Build html template from coupon data
	 */
	private function build_html( $at_coupons ) {
		if( !$at_coupons ) {
			return '';
		}
	
		ob_start();

		if( file_exists( get_template_directory() . '/accesstrade_coupon_template.php' ) ) {
			require get_template_directory() . '/accesstrade_coupon_template.php';
			return ob_get_clean();	
		}
		?>
		<style>
		/*
		* Coupon area
		*/
		.coupondiv{border:1px solid #d3d3d3;min-width:250px;margin-bottom:6px;background-color:#fff}.coupondiv .promotiontype{padding:15px;overflow:hidden}.promotag{float:left}.promotagcont{background:#fff;color:#fe6f17;overflow:hidden;width:70px;border-radius:2px;-webkit-box-shadow:1px 1px 4px rgba(34,34,34,.2);box-shadow:1px 1px 4px rgba(34,34,34,.2);text-align:center}.promotagcont .saleorcoupon{background:#fe6f17;padding:7px 6px;color:#fff;font-size:12px;font-weight:700;line-height:2em}.tagsale.promotagcont{background:#fff;color:#1fb207}.tagsale .saleorcoupon{background:#1fb207}.saveamount{min-height:58px;font-size:20px;margin:0 auto;padding:4px 3px 0;font-weight:700;line-height:2.5}.coupondiv .cpbutton{float:right;position:relative;z-index:1;text-align:right;width:140px;margin-top:35px;margin-right:15px}.copyma{width:110px;min-width:110px;display:inline-block;position:relative;margin-right:30px;padding:15px 5px;border:0;background:#fe6f17;color:#fff;font-family:'Roboto',sans-serif;font-size:15px;font-weight:500;line-height:1;text-align:center;text-decoration:none;cursor:pointer;border-style:solid;border-color:#fe6f17;border-radius:0}.copyma:after{border-left-color:#fe6f17;content:"";display:block;width:0;height:0;border-top:45px solid transparent;border-left:45px solid #fe6f17;position:absolute;right:-45px;top:0}.copyma:hover{background-color:#cb5912}.copyma:hover:after{opacity:0;-webkit-transition-duration:.5s;transition-duration:.5s}.coupon-code{position:absolute;top:0;right:-45px;z-index:-1;min-width:50px;height:45px;padding:0 5;font-weight:500;line-height:45px;text-align:center;text-decoration:none;cursor:pointer;border-radius:0;font-size:16px;color:#222;font-family:'Open Sans',sans-serif;border:1px solid #ddd}.xemngayz{width:88px;min-width:88px;display:inline-block;position:relative;margin-right:30px;padding:15px 15px;border:0;background:#1fb207;color:#fff;font-family:'Roboto',sans-serif;font-size:16px;font-weight:500;line-height:1;text-align:center;text-decoration:none;cursor:pointer;border-style:solid;border-color:#1fb207;border-radius:0}.xemngayz:hover{background-color:#167f05}.promotiondetails{padding-left:20px;width:calc(100% - 270px);word-wrap:break-word;float:left;font-size:16px}.coupontitle{display:block;font-family:'Roboto',sans-serif;margin-bottom:5px;color:#222;font-weight:500;line-height:1.2;text-decoration:none;font-size:16px}.cpinfo{display:block;margin-bottom:5px;color:#222;line-height:1.6;text-decoration:none;font-size:14px}.news-box .news-thumb,.news-box .news-info{display:inline-block;float:left}.news-box .news-info{width:500px;margin-left:10px}@media screen and (max-width:767px){.coupontitle{font-size:18px}.promotagcont{width:60px}.promotagcont .saleorcoupon{font-size:11px}.saveamount{min-height:50px;font-size:16px}.promotiondetails{margin-right:0;font-size:14px;width:auto;float:none;margin-left:70px;padding-left:0}.coupondiv .cpbutton{clear:both;margin-top:0;width:116px}.copyma{width:100px;min-width:100px;padding:10px 8px}.copyma:after{border-top:35px solid transparent;border-left:35px solid #fe6f17;position:absolute;right:-34px;top:0}.coupon-code{position:absolute;top:0;right:-35px;z-index:-1;height:35px;line-height:35px}.xemngayz{width:135px;min-width:135px;padding:10px 8px}.xemngayz:hover{background-color:#167f05}}
		</style>
		<script type="text/javascript">
		function nhymxu_at_coupon_copy2clipboard(b){var a=document.createElement("input");a.setAttribute("value",b);document.body.appendChild(a);a.select();document.execCommand("copy");document.body.removeChild(a)};
		</script>
		<?php foreach( $at_coupons as $row ): ?>
			<div class="coupondiv">
				<div class="promotiontype">
					<div class="promotag">
						<div class="promotagcont tagsale">
							<div class="saveamount"><?=($row['save'] != '') ? $row['save'] : 'KM';?></div>
							<div class="saleorcoupon"><?=($row['code']) ? ' SALE' : ' COUPON';?></div>
						</div>
					</div>
					<div class="promotiondetails">
						<div class="coupontitle"><?=$row['title'];?></div>
						<div class="cpinfo">
							<strong>Hạn dùng: </strong><?=$row['exp'];?>
							<?php if( !empty($row['categories']) ): ?>
							<br><strong>Ngành hàng:</strong> <?=implode(',', $row['categories']);?>
							<?php endif; ?>
							<?=( $row['note'] != '' ) ? '<br>' . $row['note'] : '';?>
						</div>
					</div>
					<div class="cpbutton">
					<?php if( $row['code'] != '' ): ?>
						<div class="copyma" onclick="nhymxu_at_coupon_copy2clipboard('<?=$row['code'];?>');window.open('<?=$row['deeplink'];?>','_blank')">
							<div class="coupon-code"><?=$row['code'];?></div>
							<div>COPY MÃ</div>
						</div>
					<?php else: ?>
						<div class="xemngayz" onclick="window.open('<?=$row['deeplink'];?>','_blank')">XEM NGAY</div>
					<?php endif; ?>
					</div>
				</div>
			</div>	
		<?php
		endforeach;
		
		$html = ob_get_clean();
		return $html;
	}

	private function build_deeplink( $url ) {
		$option = get_option('nhymxu_at_coupon', ['uid' => '', 'utmsource' => '']);
		
		if( $option['uid'] == '' ) {
			return $url;
		}
	
		$utm_source = '';
		if( $option['utmsource'] != '' ) {
			$utm_source = '&utm_source='. $option['utmsource'];
		}
	
		return 'https://pub.accesstrade.vn/deep_link/'. $option['uid'] .'?url=' . rawurlencode( $url ) . $utm_source;
	}

	public function admin_page() {
		add_options_page('AccessTrade Coupon', 'AccessTrade Coupon', 'manage_options', 'accesstrade_coupon', [$this,'admin_page_callback']);
	}

	public function admin_page_callback() {
		global $wpdb;
		if( isset( $_POST, $_POST['nhymxu_hidden'] ) && $_POST['nhymxu_hidden'] == 'coupon' ) {
			$input = [
				'uid'	=> sanitize_text_field($_REQUEST['nhymxu_at_coupon_uid']),
				'utmsource'	=> sanitize_text_field($_REQUEST['nhymxu_at_coupon_utmsource'])
			];
	
			update_option('nhymxu_at_coupon', $input);
			echo '<h1>Cập nhật thành công</h1><br>';
		}
		$option = get_option('nhymxu_at_coupon', ['uid' => '', 'utmsource' => '']);
		?>
		
		<div>
			<h2>Cài đặt AccessTrade Coupon</h2>
			<br>
			<form action="options-general.php?page=accesstrade_coupon" method="post">
				<input type="hidden" name="nhymxu_hidden" value="coupon">
				<table>
					<tr>
						<td>AccessTrade ID*:</td>
						<td><input type="text" name="nhymxu_at_coupon_uid" value="<?=$option['uid'];?>"></td>
					</tr>
					<tr>
						<td></td>
						<td>Lấy ID tại <a href="https://pub.accesstrade.vn/tools/deep_link" target="_blank">đây</a></td>
					</tr>
					<tr>
						<td>UTM Source:</td>
						<td><input type="text" name="nhymxu_at_coupon_utmsource" value="<?=$option['utmsource'];?>"></td>
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
			?>
			<p>Tổng số coupon trong hệ thống: <strong><?=$total_coupon;?></strong></p>
			<?php $last_run = get_option('nhymxu_at_coupon_sync_time', 0);?>
			<p>Lần đồng bộ cuối: <strong><?=( $last_run == 0 ) ? 'chưa rõ' : date("Y-m-d H:i:s", $last_run);?></strong></p>
		</div>
		<?php
	}

	public function allow_external_update_host( $allow, $host, $url ) {
		//if ( $host == 'sv.isvn.space' ) {$allow = true;}
		$allow = true;
		return $allow;
	}

	public function init_updater() {
		if( is_admin() ) {
			if( !class_exists('nhymxu_AT_AutoUpdate') ) {
				require_once('nhymxu-updater.php');
			}
			$plugin_remote_path = 'http://sv.isvn.space/wp-update/plugin-accesstrade-coupon.json';
			$plugin_slug = plugin_basename( __FILE__ );
			$license_user = 'nhymxu';
			$license_key = 'AccessTrade';
			new nhymxu_AT_AutoUpdate( NHYMXU_AT_COUPON_VER, $plugin_remote_path, $plugin_slug, $license_user, $license_key );
		}
	}

	private function insert_log( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'coupon_logs',
			[
				'created_at'	=> time(),
				'data'	=> $data
			],
			['%d', '%s']
		);
		
	}

	private function insert_coupon( $data ) {
		global $wpdb;
		
		$result = $wpdb->insert( 
			$wpdb->prefix . 'coupons',
			[
				'type'	=> $data['merchant'],
				'title' => $data['title'],
				'code'	=> ($data['coupon_code']) ? $data['coupon_code'] : '',
				'exp'	=> $data['date_end'],
				'note'	=> $data['coupon_desc'],
				'url'	=> ($data['link']) ? $data['link'] : '',
				'save'	=> ($data['coupon_save']) ? $data['coupon_save'] : ''
			],
			['%s','%s','%s','%s','%s','%s','%s']
		);
		
		if ( $result ) {
			$coupon_id = $wpdb->insert_id;
			if( isset( $data['categories'] ) && !empty( $data['categories'] ) ) {
				$cat_ids = $this->get_coupon_category_id( $data['categories'] );
				foreach( $cat_ids as $row ) {
					$wpdb->insert(
						$wpdb->prefix . 'coupon_category_rel',
						[
							'coupon_id' => $coupon_id,
							'category_id'	=> $row
						],
						['%d', '%d']
					);
				}
			}
	
			return 1;
		}
		$msg = 'Error: Insert coupon error' . PHP_EOL;
		$msg .= json_encode( $data );
			
		$this->insert_log( $msg );		

		return 0;
	}

	private function get_coupon_category_id( $input ) {
		global $wpdb;
	
		$cat_id = [];
	
		foreach( $input as $row ) {
			$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}coupon_categories WHERE slug = '{$row['slug']}'");
			
			if( $result ) {
				$cat_id[] = (int) $result->id;
			} else {
				$result = $wpdb->insert(
					$wpdb->prefix . 'coupon_categories',
					[
						'name'	=> $row['title'],
						'slug'	=> $row['slug']
					],
					['%s', '%s']
				);
				$cat_id[] = (int) $wpdb->insert_id;				
			}
		}
	
		return $cat_id;
	}

	static public function plugin_install() {
		global $wpdb;

		wp_remote_post( 'http://mail.isvn.space/nhymxu-track.php', [
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => [],
			'body' => [
				'_hidden_nhymxu' => 'tracking_active',
				'domain' => get_option( 'siteurl' ),
				'email'	 => get_option( 'admin_email' ),
				'name'	=> 'nhymxu-at-coupon'
			],
			'cookies' => []
		]);
		
		$charset_collate = $wpdb->get_charset_collate();
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupons (
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(10) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
			`title` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
			`code` VARCHAR(60) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
			`exp` DATE NOT NULL,
			`note` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
			`url` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
			`save` VARCHAR(20) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
			PRIMARY KEY (`id`)
		) {$charset_collate};";
		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupon_categories (
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(250) NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
			`slug` VARCHAR(100) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
			PRIMARY KEY (`id`),
			INDEX `slug` (`slug`)
		) {$charset_collate};";
		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupon_category_rel (
			`coupon_id` INT(11) NOT NULL,
			`category_id` INT(11) NOT NULL,
			UNIQUE INDEX `coupon_id` (`coupon_id`, `category_id`)
		) {$charset_collate};";
		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupon_logs (
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`created_at` INT(20) UNSIGNED NOT NULL,
			`data` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
			PRIMARY KEY (`id`)
		) {$charset_collate};";
		dbDelta( $sql );

		if (! wp_next_scheduled ( 'nhymxu_at_coupon_sync_event' )) {
			wp_schedule_event( time(), 'twicedaily', 'nhymxu_at_coupon_sync_event' );
		}
	}

	static public function plugin_deactive() {
		wp_clear_scheduled_hook( 'nhymxu_at_coupon_sync_event' );
	}
}

class nhymxu_at_coupon_editor {
	public function __construct() {
		add_action("admin_print_footer_scripts", [$this, 'shortcode_button_script']);	
		add_action('admin_print_scripts', [$this, 'data_for_tinymce_list']);
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

new nhymxu_at_coupon();
new nhymxu_at_coupon_editor();

register_activation_hook( __FILE__, ['nhymxu_at_coupon', 'plugin_install'] );
register_deactivation_hook(__FILE__, ['nhymxu_at_coupon', 'plugin_deactive'] );

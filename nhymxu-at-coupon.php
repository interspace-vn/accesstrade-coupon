<?php
/*
Plugin Name: ACCESSTRADE Coupon
Plugin URI: http://github.com/nhymxu/accesstrade-coupon
Description: Hệ thống coupon đồng bộ tự động từ ACCESSTRADE
Author: Dũng Nguyễn (nhymxu)
Version: 0.3.3
Author URI: http://dungnt.net
*/

defined( 'ABSPATH' ) || die;
define('NHYMXU_AT_COUPON_VER', "0.3.3");

date_default_timezone_set('Asia/Ho_Chi_Minh');

class nhymxu_at_coupon {

	public function __construct() {
		add_filter( 'http_request_host_is_external', [$this, 'allow_external_update_host'], 10, 3 );
		add_action( 'nhymxu_at_coupon_sync_event', [$this,'do_this_twicedaily'] );
		add_shortcode( 'atcoupon', [$this,'shortcode_callback'] );
		add_shortcode( 'coupon', [$this,'shortcode_callback'] );
		add_action( 'init', [$this, 'init_updater'] );
		add_action( 'wp_ajax_nhymxu_coupons_ajax_forceupdate', [$this, 'ajax_force_update'] );
	}

	public function do_this_twicedaily() {
		global $wpdb;
		$previous_time = get_option('nhymxu_at_coupon_sync_time', 0);
		$current_time = time();

		$url = 'http://sv.isvn.space/api/v1/mars/coupon?from='.$previous_time.'&to='.$current_time;

		$result = wp_remote_get( $url, ['timeout'=>'60'] );

		if ( is_wp_error( $result ) ) {
			$msg = [];
			$msg['previous_time'] = $previous_time;
			$msg['current_time'] = $current_time;
			$msg['error_msg'] = $result->get_error_message();
			$msg['action'] = 'get_remote_data';

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
					$msg = [];
					$msg['previous_time'] = $previous_time;
					$msg['current_time'] = $current_time;
					$msg['error_msg'] = $e->getMessage();
					$msg['action'] = 'insert_data';

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
		$option = get_option('nhymxu_at_coupon', ['uid' => '', 'accesskey' => '','utmsource' => '']);

		if( $option['uid'] == '' ) {
			return $url;
		}

		$utm_source = '';
		if( $option['utmsource'] != '' ) {
			$utm_source = '&utm_source='. $option['utmsource'];
		}

		return 'https://pub.accesstrade.vn/deep_link/'. $option['uid'] .'?url=' . rawurlencode( $url ) . $utm_source;
	}

	/*
	 * Force update coupon from server
	 */
	public function ajax_force_update() {
		$this->do_this_twicedaily();
		echo 'running';
		wp_die();
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

	public function insert_log( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'coupon_logs',
			[
				'created_at'	=> time(),
				'data'	=> json_encode( $data )
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
				'title' => trim($data['title']),
				'code'	=> ($data['coupon_code']) ? trim($data['coupon_code']) : '',
				'exp'	=> $data['date_end'],
				'note'	=> trim($data['coupon_desc']),
				'url'	=> ($data['link']) ? trim($data['link']) : '',
				'save'	=> ($data['coupon_save']) ? trim($data['coupon_save']) : ''
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

		$msg = [];
		$msg['previous_time'] = '';
		$msg['current_time'] = '';
		$msg['error_msg'] = json_encode( $data );
		$msg['action'] = 'insert_coupon';

		$this->insert_log( $msg );

		return 0;
	}

	private function get_coupon_category_id( $input ) {
		global $wpdb;

		$cat_id = [];

		foreach( $input as $row ) {
			$slug = trim($row['slug']);
			$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}coupon_categories WHERE slug = '{$slug}'");

			if( $result ) {
				$cat_id[] = (int) $result->id;
			} else {
				$result = $wpdb->insert(
					$wpdb->prefix . 'coupon_categories',
					[
						'name'	=> trim($row['title']),
						'slug'	=> trim($row['slug'])
					],
					['%s', '%s']
				);
				$cat_id[] = (int) $wpdb->insert_id;
			}
		}

		return $cat_id;
	}
}

$nhymxu_at_coupon = new nhymxu_at_coupon();

if( is_admin() ) {
	require_once __DIR__ . '/editor.php';
	new nhymxu_at_coupon_editor();

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( !is_plugin_active( 'nhymxu-at-coupon-pro/nhymxu-at-coupon-pro.php' ) ) {
		require_once __DIR__ . '/admin.php';
		new nhymxu_at_coupon_admin();
	}
}

require_once __DIR__ . '/install.php';

register_activation_hook( __FILE__, ['nhymxu_at_coupon_install', 'plugin_install'] );
register_deactivation_hook( __FILE__, ['nhymxu_at_coupon_install', 'plugin_deactive'] );
register_uninstall_hook( __FILE__, ['nhymxu_at_coupon_install', 'plugin_uninstall'] );

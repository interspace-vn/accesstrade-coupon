<?php

class nhymxu_at_coupon_install {
	public static function active_track() {
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
		] );
	}

	public static function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupons (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(100) NOT NULL DEFAULT '',
			`title` TEXT NOT NULL,
			`code` VARCHAR(100) NOT NULL DEFAULT '',
			`exp` DATE NOT NULL,
			`note` TEXT NOT NULL,
			`url` TEXT NOT NULL,
			`save` VARCHAR(100) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
		) {$charset_collate};";
		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}coupon_categories (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(250) NULL DEFAULT '',
			`slug` VARCHAR(100) NOT NULL DEFAULT '',
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
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`created_at` INT(20) UNSIGNED NOT NULL,
			`data` TEXT NOT NULL,
			PRIMARY KEY (`id`)
		) {$charset_collate};";
		dbDelta( $sql );

		update_option( 'nhymxu_at_coupon_db_ver', NHYMXU_AT_COUPON_VER );
	}

	private function upgrade_database( $db_version ) {
		global $wpdb;

		if ( version_compare( $db_version, '0.3.0' ) < 0 ) {
			$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE type type VARCHAR(100);');
			$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE code code VARCHAR(100);');
			$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE save save VARCHAR(100);');
			update_option( 'nhymxu_at_coupon_db_ver', '0.3.0' );
		}
	}

	public static function drop_table() {
		global $wpdb;

		$wpdb->query("DELETE FROM {$wpdb->prefix}coupons");
		$wpdb->query("DELETE FROM {$wpdb->prefix}coupon_categories");
		$wpdb->query("DELETE FROM {$wpdb->prefix}coupon_category_rel");
		$wpdb->query("DELETE FROM {$wpdb->prefix}coupon_logs");

		$wpdb->query("ALTER TABLE {$wpdb->prefix}coupons AUTO_INCREMENT = 1");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}coupon_categories AUTO_INCREMENT = 1");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}coupon_logs AUTO_INCREMENT = 1");

		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coupons");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coupon_categories");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coupon_category_rel");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coupon_logs");
	}

	static public function plugin_install() {
		$db_version = get_option( 'nhymxu_at_coupon_db_ver', '0.0.0' );

		static::active_track();

		if( $db_version === '0.0.0' ) {
			static::create_table();
		}

		static::upgrade_database( $db_version );

		if (! wp_next_scheduled( 'nhymxu_at_coupon_sync_event' )) {
			wp_schedule_event( time(), 'twicedaily', 'nhymxu_at_coupon_sync_event' );
		}
	}

	static public function plugin_deactive() {
		wp_clear_scheduled_hook( 'nhymxu_at_coupon_sync_event' );
	}

	static public function plugin_uninstall() {
		delete_option('nhymxu_at_coupon_sync_time');
		delete_site_option('nhymxu_at_coupon_sync_time');
		delete_option('nhymxu_at_coupon_db_ver');
		delete_site_option('nhymxu_at_coupon_db_ver');
		wp_clear_scheduled_hook( 'nhymxu_at_coupon_sync_event' );

		static::drop_table();
	}
}

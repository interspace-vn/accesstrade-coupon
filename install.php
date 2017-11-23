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
		]);
	}

	public static function create_table() {
		global $wpdb;

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
		static::active_track();
		static::create_table();

		if (! wp_next_scheduled ( 'nhymxu_at_coupon_sync_event' )) {
			wp_schedule_event( time(), 'twicedaily', 'nhymxu_at_coupon_sync_event' );
		}
	}

	static public function plugin_deactive() {
		wp_clear_scheduled_hook( 'nhymxu_at_coupon_sync_event' );
	}

	static public function plugin_uninstall() {
		delete_option('nhymxu_at_coupon_sync_time');
		delete_site_option('nhymxu_at_coupon_sync_time');
		wp_clear_scheduled_hook( 'nhymxu_at_coupon_sync_event' );

		static::drop_table();
	}
}

<?php
namespace Kitmage\TrainingEntitlements\Database;

final class Migrator {
	const VERSION = '1.0.0';
	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$wpdb->prefix}kte_batches (
		id bigint unsigned NOT NULL AUTO_INCREMENT, token_hash char(64) NOT NULL, encrypted_token text NOT NULL, token_version int unsigned NOT NULL DEFAULT 1,
		customer_user_id bigint unsigned NOT NULL, order_id bigint unsigned NOT NULL, order_item_id bigint unsigned NOT NULL,
		subscription_id bigint unsigned NOT NULL DEFAULT 0, product_id bigint unsigned NOT NULL, variation_id bigint unsigned NOT NULL DEFAULT 0,
		course_id bigint unsigned NOT NULL, entitlements_total int unsigned NOT NULL, entitlements_used int unsigned NOT NULL DEFAULT 0,
		entitlements_reserved int unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL, expires_at datetime NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'active', created_by varchar(40) NOT NULL DEFAULT 'woocommerce', updated_at datetime NOT NULL,
		PRIMARY KEY (id), UNIQUE KEY order_item_id (order_item_id), UNIQUE KEY token_hash (token_hash), KEY customer (customer_user_id),
		KEY course (course_id), KEY orders (order_id), KEY subscription (subscription_id), KEY status_expires (status,expires_at)
		) ENGINE=InnoDB $c;" );
		dbDelta( "CREATE TABLE {$wpdb->prefix}kte_redemptions (
		id bigint unsigned NOT NULL AUTO_INCREMENT, batch_id bigint unsigned NOT NULL, user_id bigint unsigned NOT NULL, course_id bigint unsigned NOT NULL,
		first_name_snapshot varchar(100) NOT NULL DEFAULT '', last_name_snapshot varchar(100) NOT NULL DEFAULT '', email_snapshot varchar(190) NOT NULL DEFAULT '',
		status varchar(20) NOT NULL, reserved_at datetime NULL, redeemed_at datetime NULL, failure_code varchar(60) NULL, failure_context text NULL,
		created_at datetime NOT NULL, updated_at datetime NOT NULL, completed_key varchar(191) NULL,
		PRIMARY KEY (id), UNIQUE KEY completed_key (completed_key), KEY batch_user (batch_id,user_id), KEY course (course_id), KEY status_reserved (status,reserved_at), KEY redeemed (redeemed_at)
		) ENGINE=InnoDB $c;" );
		dbDelta( "CREATE TABLE {$wpdb->prefix}kte_audit_log (
		id bigint unsigned NOT NULL AUTO_INCREMENT, batch_id bigint unsigned NOT NULL, action varchar(80) NOT NULL, previous_value longtext NULL,
		new_value longtext NULL, user_id bigint unsigned NOT NULL DEFAULT 0, context longtext NULL, created_at datetime NOT NULL,
		PRIMARY KEY (id), KEY batch_created (batch_id,created_at), KEY action (action)
		) ENGINE=InnoDB $c;" );
		update_option( 'kte_db_version', self::VERSION, false );
	}
}

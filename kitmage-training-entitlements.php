<?php
/**
 * Plugin Name: Kitmage Training Entitlements
 * Description: WooCommerce-funded enrollment entitlements for the Kitmage Tutor LMS fork.
 * Version: 1.0.0
 * Author: Kitmage
 * Text Domain: kitmage-training-entitlements
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'KTE_VERSION', '1.0.0' );
define( 'KTE_FILE', __FILE__ );
define( 'KTE_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kitmage\\TrainingEntitlements\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = KTE_PATH . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Kitmage\\TrainingEntitlements\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Kitmage\\TrainingEntitlements\\Plugin', 'deactivate' ) );

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
add_action( 'plugins_loaded', array( 'Kitmage\\TrainingEntitlements\\Plugin', 'boot' ), 20 );

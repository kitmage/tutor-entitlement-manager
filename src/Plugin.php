<?php
namespace Kitmage\TrainingEntitlements;

use Kitmage\TrainingEntitlements\Admin\AdminController;
use Kitmage\TrainingEntitlements\Database\Migrator;
use Kitmage\TrainingEntitlements\Frontend\RedemptionController;
use Kitmage\TrainingEntitlements\Repository\AuditRepository;
use Kitmage\TrainingEntitlements\Repository\BatchRepository;
use Kitmage\TrainingEntitlements\Repository\RedemptionRepository;
use Kitmage\TrainingEntitlements\Service\RedemptionService;
use Kitmage\TrainingEntitlements\Service\ReservationService;
use Kitmage\TrainingEntitlements\Service\TokenService;
use Kitmage\TrainingEntitlements\Service\TutorEnrollmentService;
use Kitmage\TrainingEntitlements\WooCommerce\Integration;

final class Plugin {
	public static function activate() {
		Migrator::migrate();
		RedemptionController::add_rewrite_rule();
		flush_rewrite_rules();
		if ( ! wp_next_scheduled( 'kte_reconcile_reservations' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'kte_reconcile_reservations' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'kte_reconcile_reservations' );
		flush_rewrite_rules();
	}

	public static function boot() {
		if ( get_option( 'kte_db_version' ) !== Migrator::VERSION ) {
			Migrator::migrate();
		}
		$batches = new BatchRepository();
		$redemptions = new RedemptionRepository();
		$audit = new AuditRepository();
		$tokens = new TokenService();
		$tutor = new TutorEnrollmentService();
		$reservations = new ReservationService( $batches, $redemptions );
		$service = new RedemptionService( $batches, $redemptions, $reservations, $tutor );
		add_action( 'kte_reconcile_reservations', array( new \Kitmage\TrainingEntitlements\Service\ReconciliationService( $redemptions, $reservations, $tutor ), 'run' ) );
		( new RedemptionController( $batches, $tokens, $service ) )->register();
		if ( class_exists( 'WooCommerce' ) ) {
			( new Integration( $batches, $audit, $tokens ) )->register();
			( new AdminController( $batches, $redemptions, $audit, $tokens ) )->register();
		}
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
	}

	public static function dependency_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
		$missing = array();
		if ( ! class_exists( 'WooCommerce' ) ) $missing[] = 'WooCommerce';
		if ( ! \Kitmage\TrainingEntitlements\Tutor\Compatibility::supported() ) $missing[] = 'Kitmage Tutor LMS fork';
		if ( $missing ) printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf( __( 'Kitmage Training Entitlements requires: %s.', 'kitmage-training-entitlements' ), implode( ', ', $missing ) ) ) );
	}
}

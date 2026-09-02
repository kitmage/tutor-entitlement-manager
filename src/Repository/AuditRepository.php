<?php
namespace Kitmage\TrainingEntitlements\Repository;

final class AuditRepository {
	public function add( $batch_id, $action, $previous = null, $new = null, $context = array() ) {
		global $wpdb;
		return $wpdb->insert( $wpdb->prefix . 'kte_audit_log', array( 'batch_id'=>(int)$batch_id, 'action'=>sanitize_key($action), 'previous_value'=>wp_json_encode($previous), 'new_value'=>wp_json_encode($new), 'user_id'=>get_current_user_id(), 'context'=>wp_json_encode($context), 'created_at'=>current_time('mysql',true) ) );
	}
	public function for_batch( $id ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kte_audit_log WHERE batch_id=%d ORDER BY id DESC", $id ) ); }
}

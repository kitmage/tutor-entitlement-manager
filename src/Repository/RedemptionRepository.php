<?php
namespace Kitmage\TrainingEntitlements\Repository;

final class RedemptionRepository {
	private function table(){global $wpdb;return $wpdb->prefix.'kte_redemptions';}
	public function completed( $batch,$user ){global $wpdb;return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE batch_id=%d AND user_id=%d AND status='completed'",$batch,$user));}
	public function for_batch($batch,$completed_only=false){global $wpdb;$status=$completed_only?" AND status='completed'":'';return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE batch_id=%d$status ORDER BY id DESC",$batch));}
	public function pending_stale($before){global $wpdb;return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE status='pending' AND reserved_at<%s LIMIT 100",$before));}
}

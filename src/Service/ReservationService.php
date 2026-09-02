<?php
namespace Kitmage\TrainingEntitlements\Service;

use Kitmage\TrainingEntitlements\Repository\BatchRepository;
use Kitmage\TrainingEntitlements\Repository\RedemptionRepository;

final class ReservationService {
	private $batches; private $redemptions;
	public function __construct(BatchRepository $b,RedemptionRepository $r){$this->batches=$b;$this->redemptions=$r;}
	public function reserve($batch,$user){
		global $wpdb;$bt=$wpdb->prefix.'kte_batches';$rt=$wpdb->prefix.'kte_redemptions';$now=current_time('mysql',true);
		$wpdb->query('START TRANSACTION');
		try{
			$locked=$wpdb->get_row($wpdb->prepare("SELECT * FROM $bt WHERE id=%d FOR UPDATE",$batch));
			if(!$locked || 'active'!==$locked->status || $locked->expires_at<=$now || ((int)$locked->entitlements_used+(int)$locked->entitlements_reserved)>=(int)$locked->entitlements_total){$wpdb->query('ROLLBACK');return new \WP_Error('unavailable');}
			if($this->redemptions->completed($batch,$user)){$wpdb->query('ROLLBACK');return new \WP_Error('already_redeemed');}
			$wpdb->query($wpdb->prepare("UPDATE $bt SET entitlements_reserved=entitlements_reserved+1,updated_at=%s WHERE id=%d",$now,$batch));
			$wpdb->insert($rt,array('batch_id'=>(int)$batch,'user_id'=>(int)$user,'course_id'=>(int)$locked->course_id,'status'=>'pending','reserved_at'=>$now,'created_at'=>$now,'updated_at'=>$now));
			$id=(int)$wpdb->insert_id;if(!$id)throw new \RuntimeException('pending_insert');$wpdb->query('COMMIT');return $id;
		}catch(\Throwable $e){$wpdb->query('ROLLBACK');return new \WP_Error('reservation_failed');}
	}
	public function finalize($redemption_id,$batch_id,$user){
		global $wpdb;$bt=$wpdb->prefix.'kte_batches';$rt=$wpdb->prefix.'kte_redemptions';$now=current_time('mysql',true);$u=get_userdata($user);$wpdb->query('START TRANSACTION');
		$key=$batch_id.':'.$user;
		$ok=$wpdb->query($wpdb->prepare("UPDATE $rt SET status='completed',first_name_snapshot=%s,last_name_snapshot=%s,email_snapshot=%s,redeemed_at=%s,updated_at=%s,completed_key=%s WHERE id=%d AND status='pending'",$u?$u->first_name:'',$u?$u->last_name:'',$u?$u->user_email:'',$now,$now,$key,$redemption_id));
		if(1!==$ok){$wpdb->query('ROLLBACK');return false;}
		$batch_changed=$wpdb->query($wpdb->prepare("UPDATE $bt SET entitlements_reserved=GREATEST(0,entitlements_reserved-1),entitlements_used=entitlements_used+1,status=IF(entitlements_used+1>=entitlements_total AND entitlements_reserved<=1,'exhausted',status),updated_at=%s WHERE id=%d AND entitlements_used<entitlements_total AND entitlements_reserved>0",$now,$batch_id));
		if(1!==$batch_changed){$wpdb->query('ROLLBACK');return false;} $wpdb->query('COMMIT');return true;
	}
	public function fail($redemption_id,$batch_id,$code,$context=''){
		global $wpdb;$now=current_time('mysql',true);$wpdb->query('START TRANSACTION');$changed=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}kte_redemptions SET status='failed',failure_code=%s,failure_context=%s,updated_at=%s WHERE id=%d AND status='pending'",$code,$context,$now,$redemption_id));if($changed)$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}kte_batches SET entitlements_reserved=GREATEST(0,entitlements_reserved-1),updated_at=%s WHERE id=%d",$now,$batch_id));$wpdb->query('COMMIT');
	}
}

<?php
namespace Kitmage\TrainingEntitlements\Service;

use Kitmage\TrainingEntitlements\Repository\RedemptionRepository;

final class ReconciliationService {
	private $redemptions,$reservations,$tutor;
	public function __construct(RedemptionRepository $r,ReservationService $s,TutorEnrollmentService $t){$this->redemptions=$r;$this->reservations=$s;$this->tutor=$t;}
	public function run(){
		$before=gmdate('Y-m-d H:i:s',time()-15*MINUTE_IN_SECONDS);
		foreach($this->redemptions->pending_stale($before) as $r){
			try{$enrolled=$this->tutor->is_enrolled($r->course_id,$r->user_id);if($enrolled)$this->reservations->finalize($r->id,$r->batch_id,$r->user_id);else $this->reservations->fail($r->id,$r->batch_id,'stale_reservation');}catch(\Throwable $e){if(function_exists('wc_get_logger'))wc_get_logger()->error('Reservation reconciliation failed.',array('source'=>'kitmage-training-entitlements','redemption_id'=>$r->id));}
		}
	}
}

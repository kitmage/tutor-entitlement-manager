<?php
namespace Kitmage\TrainingEntitlements\Service;

use Kitmage\TrainingEntitlements\Repository\BatchRepository;
use Kitmage\TrainingEntitlements\Repository\RedemptionRepository;
use Kitmage\TrainingEntitlements\Tutor\Compatibility;

final class RedemptionService {
	private $batches,$redemptions,$reservations,$tutor;
	public function __construct(BatchRepository $b,RedemptionRepository $r,ReservationService $s,TutorEnrollmentService $t){$this->batches=$b;$this->redemptions=$r;$this->reservations=$s;$this->tutor=$t;}
	public function redeem($batch,$user){
		if($this->redemptions->completed($batch->id,$user))return array('state'=>'already_redeemed');
		if(!Compatibility::eligible($batch->course_id))return array('state'=>'course_unavailable');
		if($this->tutor->is_enrolled($batch->course_id,$user))return array('state'=>'already_enrolled');
		$id=$this->reservations->reserve($batch->id,$user);if(is_wp_error($id))return array('state'=>$id->get_error_code());
		try{$success=$this->tutor->enroll($batch->course_id,$user);}catch(\Throwable $e){$success=false;}
		if(!$success){$this->reservations->fail($id,$batch->id,'tutor_enrollment_failed');do_action('kitmage_training_entitlements/redemption_failed',(int)$batch->id,(int)$user,(int)$batch->course_id,(int)$id,'tutor_enrollment_failed');return array('state'=>'enrollment_failed');}
		if(!$this->reservations->finalize($id,$batch->id,$user))return array('state'=>'enrollment_failed');
		do_action('kitmage_training_entitlements/redemption_completed',(int)$batch->id,(int)$user,(int)$batch->course_id,(int)$id);return array('state'=>'success');
	}
}

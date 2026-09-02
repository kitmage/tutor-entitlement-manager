<?php
namespace Kitmage\TrainingEntitlements\Service;

final class TutorEnrollmentService {
	public function is_enrolled($course,$user){return class_exists('Tutor\\Models\\EnrollmentModel') && (bool)\Tutor\Models\EnrollmentModel::is_enrolled((int)$course,(int)$user);}
	public function enroll($course,$user){
		if($this->is_enrolled($course,$user)) return true;
		\Tutor\Models\EnrollmentModel::do_enroll((int)$course,0,(int)$user);
		return $this->is_enrolled($course,$user);
	}
}

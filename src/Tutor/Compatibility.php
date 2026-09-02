<?php
namespace Kitmage\TrainingEntitlements\Tutor;

final class Compatibility {
	public static function supported(){return class_exists('Tutor\\Models\\Course') && defined('Tutor\\Models\\Course::PRICE_TYPE_ENTITLEMENT') && 'entitlement'===constant('Tutor\\Models\\Course::PRICE_TYPE_ENTITLEMENT') && class_exists('Tutor\\Models\\EnrollmentModel');}
	public static function eligible($course_id){
		if(!self::supported() || 'courses'!==get_post_type($course_id)) return false;
		$type=get_post_meta($course_id,'_tutor_course_price_type',true);
		if(!$type)$type=get_post_meta($course_id,'tutor_course_price_type',true);
		return (bool)apply_filters('kitmage_training_entitlements/course_eligible','entitlement'===$type,(int)$course_id,$type);
	}
}

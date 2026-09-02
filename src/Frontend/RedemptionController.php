<?php
namespace Kitmage\TrainingEntitlements\Frontend;

use Kitmage\TrainingEntitlements\Repository\BatchRepository;
use Kitmage\TrainingEntitlements\Service\RedemptionService;
use Kitmage\TrainingEntitlements\Service\TokenService;

final class RedemptionController {
	private $batches,$tokens,$service;
	public function __construct(BatchRepository $b,TokenService $t,RedemptionService $s){$this->batches=$b;$this->tokens=$t;$this->service=$s;}
	public static function add_rewrite_rule(){add_rewrite_rule('^training/redeem/([A-Za-z0-9_-]+)/?$','index.php?kte_token=$matches[1]','top');}
	public function register(){add_action('init',array(__CLASS__,'add_rewrite_rule'));add_filter('query_vars',function($v){$v[]='kte_token';return $v;});add_action('template_redirect',array($this,'render'));
	}
	public function render(){
		$token=(string)get_query_var('kte_token');if(!$token)return;$batch=$this->batches->by_hash($this->tokens->hash($token));$state='ready';
		if(!$batch)$state='invalid';elseif('revoked'===$batch->status)$state='revoked';elseif('refunded'===$batch->status)$state='refunded';elseif('exhausted'===$batch->status)$state='exhausted';elseif($batch->expires_at<=current_time('mysql',true)){$state='expired';$this->batches->update($batch->id,array('status'=>'expired'));}
		$course=$batch?get_post($batch->course_id):null;
		if('ready'===$state&&!is_user_logged_in())$state='login_required';
		if('ready'===$state&&'POST'===$_SERVER['REQUEST_METHOD']){check_admin_referer('kte_redeem_'.$batch->id);$result=$this->service->redeem($batch,get_current_user_id());$state=$result['state'];}
		status_header('invalid'===$state?404:200);nocache_headers();include KTE_PATH.'templates/redemption/page.php';exit;
	}
}

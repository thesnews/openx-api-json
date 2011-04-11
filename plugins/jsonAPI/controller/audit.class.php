<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Audit.php';

class audit extends \jsonAPI\controller {

	public function __construct($a) {
		$this->action = $a;
		if( !$this->verifySession() ) {
			throw new \jsonAPI\exception('Invalid session credentials');
		}
	}

	public function main() {
		return new Response(array(
			'widget' => array(
				'void'
			)
		));
	}
	
	public function widget() {
	
		$audit = new \OA_Dll_Audit;
		$opts = array();

		if( \OA_Permission::isAccount(\OA_ACCOUNT_MANAGER) ) {
			$opts['account_id'] = \OA_Permission::getAccountId();
		}
		if( \OA_Permission::isAccount(\OA_ACCOUNT_ADVERTISER) ) {
			$opts['advertiser_account_id'] = \OA_Permission::getAccountId();
		}
		if( \OA_Permission::isAccount(\OA_ACCOUNT_TRAFFICKER) ) {
			$opts['website_account_id'] = \OA_Permission::getAccountId();
		}
		
		$data = $audit->getAuditLogForAuditWidget($opts);
		$out = array();
		
		if( !count($data) ) {
			return new Response(array());
		}
		
		foreach( $data as $k => $val ) {
			$val['action'] = $audit->getActionName($val['actionid']);
			$audit->getParentContextData($val);
			
			$out[] = array(
				'user' => $val['username'],
				'action' => $val['action'],
				'context' => $val['context']
			);
		}
		
		return new Response($out);
	}

}

?>
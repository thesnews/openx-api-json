<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';

class campaign extends \jsonAPI\controller {

	public function __construct($a) {
		$this->action = $a;
		if( !$this->verifySession() ) {
			throw new \jsonAPI\exception('Invalid session credentials');
		}
	}

	public function main() {
		return new Response(array(
			'active' => array(
				'void'
			)
		));
	}
	
	public function active() {
		
		$campObj = \OA_Dal::factoryDO('campaigns');
		$clientObj = \OA_Dal::factoryDO('clients');
		$agencyObj = \OA_Dal::factoryDO('agency');

		$accts = \OA_Permission::getOwnedAccounts(
			\OA_Permission::getAccountId()
		);
		
		$agencyObj->account_id = \OA_Permission::getAccountId();
		$clientObj->joinAdd($agencyObj);
		$campObj->joinAdd($clientObj);
		$campObj->status = \OA_ENTITY_STATUS_RUNNING;
		$campObj->find();
		
		$return = array();
		
		while($campObj->fetch() ) {
			$return[] = $campObj->toArray();
		}
		
		return new Response($return);
	}

}

?>
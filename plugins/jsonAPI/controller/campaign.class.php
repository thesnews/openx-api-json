<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OX/Translation.php';
require_once MAX_PATH.'/lib/OX/Util/Utils.php';

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
		$tx = new \OX_Translation;
		
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
			$item = $campObj->toArray();
			
			$k = \OX_Util_Utils::getCampaignTypeTranslationKey(
				$item['priority']
			);
			
			$item['string_type'] = $GLOBALS[$k];
			
			$k = \OX_Util_Utils::getCampaignStatusTranslationKey(
				$item['status']
			);

			$item['string_status'] = $GLOBALS[$k];
			
			$type = '';
			switch($item['revenue_type']) {
				case MAX_FINANCE_CPM:
					$type = 'CPM';
					break;
				case MAX_FINANCE_CPC:
					$type = 'CPC';
					break;
				case MAX_FINANCE_CPA:
					$type = 'CPA';
					break;
				case MAX_FINANCE_MT:
					$type = 'Tenancy';
					break;
			}
			$item['string_revenueType'] = $type;
			
			$return[] = $item;
		}
		
		
		return new Response($return);
	}

}

?>
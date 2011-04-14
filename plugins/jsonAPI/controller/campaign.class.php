<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH.'/lib/OX/Translation.php';
require_once MAX_PATH.'/lib/OX/Util/Utils.php';
//require_once MAX_PATH.'/lib/OA/Dal.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/campaign.class.php';

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
			),
			'listall' => array(
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

			if( $item['priority'] == -1 ) {
				$item['string_priority'] = 'Exclusive';
			} elseif( $item['priority'] == -2 ) {
				$item['string_priority'] = 'ECPM';
			} elseif( $item['priority'] == 0 ) {
				$item['string_priority'] = 'Low';
			} else {
				$item['string_priority'] = 'High ('.$item['priority'].')';
			}

			$return[] = $item;
		}
		
		
		return new Response($return);
	}

	public function listall() {
		$agencyID = \OA_Permission::getAgencyId();
		$campaigns = \OA_Dal::factoryDO('campaigns');
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');

		// only campaigns for this user
		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);
		$campaigns->joinAdd($clients);
		
//		$campaigns->selectAdd();
//		$campaigns->selectAdd('campaignId');
		
		$campaigns->type = \DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;
//		$campaigns->status = \OA_ENTITY_STATUS_RUNNING;
		$campaigns->find();
		
		$out = array();
		
		while($campaigns->fetch()) {
			$o = new \jsonAPI\model\campaign($campaigns->toArray());
			
			$out[] = $o;
		}

		return new Response($out);
	}

}

?>
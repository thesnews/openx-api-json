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
			'listall' => array(
				'status (bool)',
				'sort (string)',
				'offset (int)'
			)
		));
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

		// excludes the OpenXMarkert stuff		
		$campaigns->type = \DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;

		if( $_POST['status'] == 'active' ) {
			$campaigns->status = \OA_ENTITY_STATUS_RUNNING;
		} elseif( $_POST['status'] == 'inactive' ) {
			$campaigns->whereAdd('status != '.\OA_ENTITY_STATUS_RUNNING);
		}
		
		$order = 'campaignname asc';
		
		switch( $_POST['sort'] ) {
			case 'start':
				$order = 'activate_time asc';
				break;
			case 'end':
				$order = 'expire_time asc';
				break;
			case 'client':
				$order = 'clientname asc';
				break;
		}
		
		$campaigns->orderBy($order);

		$campaigns->find();
		
		$out = array();
		
		while($campaigns->fetch()) {
			$o = new \jsonAPI\model\campaign($campaigns->toArray());
			
			$out[] = $o;
		}
		
		$campaigns->free();

		return new Response($out);
	}

}

?>
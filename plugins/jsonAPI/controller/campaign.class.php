<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/CampaignInfo.php';
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
			),
			'fetch' => array(
				'campaignid (int)'
			)
		));
	}
	
	public function fetch() {
		$campaignID = $this->filterNum($_POST['campaignid']);
		if( !$campaignID ) {
			return $this->respondWithError('No campaign id supplied');
		}
		
		$agencyID = \OA_Permission::getAgencyId();
		$campaigns = \OA_Dal::factoryDO('campaigns');
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');

		// only campaigns for this user
		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);
		$campaigns->joinAdd($clients);

		// name collisions with the commennts attrib, so we have to add a
		// specific add call with the full namespaced table
		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$campaigns->selectAdd($ns.'campaigns.comments as description_comments');
		
		$campaigns->campaignid = $campaignID;
		
		$campaigns->find();
		
		$out = array();
		
		while($campaigns->fetch()) {
			$o = new \jsonAPI\model\campaign($campaigns->toArray());
			
			$out[] = $o;
		}
		
		$campaigns->free();

		return new Response($out);
		
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

		// name collisions with the commennts attrib, so we have to add a
		// specific add call with the full namespaced table
		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$campaigns->selectAdd($ns.'campaigns.comments as description_comments');

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
	
	public function save() {
		$id = $this->filterNum($_POST['campaignId']);
		
		if( !$id ) {
			return $this->respondWithError('No id found');
		}
		
		// permission check, yo
		if( !\OA_Permission::hasAccessToObject('campaigns', $id) ) {
			return $this->respondWithError('No campaign found');
		}

		$campaignDLL = new \OA_Dll_Campaign;
		$campaignInfo = new \OA_Dll_CampaignInfo;

		$campaignInfo->campaignId = $id;
		$campaignInfo->campaignName = $this->filterString($_POST['name']);
		$campaignInfo->startDate = $this->filterString($_POST['startDate']);
		$campaignInfo->endDate = $this->filterString($_POST['endDate']);
		$campaignInfo->impressions = $this->filterNum($_POST['impressions']);
		$campaignInfo->clicks = $this->filterNum($_POST['clicks']);
		$campaignInfo->priority = $this->filterNum($_POST['priority']);
		$campaignInfo->weight = $this->filterNum($_POST['weight']);
		$campaignInfo->targetImpressions = $this->filterNum(
			$_POST['targetImpressions']
		);
		$campaignInfo->targetClicks = $this->filterNum($_POST['targetClicks']);
		$campaignInfo->targetConversions = $this->filterNum(
			$_POST['targetConversions']
		);
		$campaignInfo->revenueType = $this->filterNum($_POST['revenueType']);
		$campaignInfo->capping = $this->filterNum($_POST['capping']);
		$campaignInfo->sessionCapping = $this->filterNum(
			$_POST['sessionCapping']
		);
		$campaignInfo->block = $this->filterNum($_POST['block']);
		$campaignInfo->comments = $this->filterString($_POST['comments']);
		$campaignInfo->viewWindow = $this->filterNum($_POST['viewWindow']);
        $campaignInfo->clickWindow = $this->filterNum($_POST['clickWindow']);
        
		if( $campaignDLL->modify(&$campaignInfo) ) {
			return new Response(true);
		}
		
		return $this->respondWithError(false);
		
	}

}

?>
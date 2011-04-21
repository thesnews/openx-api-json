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
				'campaignId (int)'
			),
			'save' => array(
				'campaignId (int)',
				'campaignName (string)',
				'impressions (int)',
				'clicks (int)',
				'conversions (int)',
				'priority (int)',
				'weight (int)',
				'targetImpressions (int)',
				'targetClicks (int)',
				'targetConversions (int)',
				'revenueType (int)',
				'comments (string)',
				'viewWindow (int)',
				'clickWindow (int)',
				'block (int)',
				'capping (int)',
				'sessionCapping (int)',
				'startDate (string [YYYY-MM-DD] or empty)',
				'endDate (string [YYYY-MM-DD] or empty)',
			)
		));
	}
	
	public function fetch() {
		$campaignID = $this->filterNum($_POST['campaignId']);
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
		
		// permission check, yo
		if( $id && !\OA_Permission::hasAccessToObject('campaigns', $id) ) {
			return $this->respondWithError('No campaign found');
		}

		$campaignDLL = new \OA_Dll_Campaign;
		$campaignInfo = new \OA_Dll_CampaignInfo;
		
		if( $id ) {
			$campaignInfo->campaignId = $id;
		}
		
		$campaignInfo->campaignName = $this->filterString(
			$_POST['campaignName']
		);

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
		$campaignInfo->comments = $this->filterString($_POST['comments']);
		$campaignInfo->viewWindow = $this->filterNum($_POST['viewWindow']);
        $campaignInfo->clickWindow = $this->filterNum($_POST['clickWindow']);

		$imp = -1;
		if( $_POST['impressions'] ) {
			$imp = $this->filterNum($_POST['impressions']);
		}
		$campaignInfo->impressions = $imp;
		
		$clk = -1;
		if( $_POST['clicks'] ) {
			$clk = $this->filterNum($_POST['clicks']);
		}
		$campaignInfo->clicks = $clk;
		
		$cnv = -1;
		if( $_POST['conversions'] ) {
			$cnv = $this->filterNum($_POST['conversions']);
		}
		$campaignInfo->conversions = $cnv;

		if( $this->filterNum($_POST['advertiserId']) ) {
			// need to ensure you can't add or move a campaign to another
			// agency's client
			$clientid = $this->filterNum($_POST['advertiserId']);
			if( !\OA_Permission::hasAccessToObject('clients', $clientid) ) {
				return $this->respondWithError('Cannot use client');
			}
			
			$campaignInfo->advertiserId = $clientid;
		}

		if( $this->filterNum($_POST['block']) ) {
			$campaignInfo->block = $this->filterNum($_POST['block']);
		}
		if( $this->filterNum($_POST['capping']) ) {
			$campaignInfo->capping = $this->filterNum($_POST['capping']);
		}
		if( $this->filterNum($_POST['sessionCapping']) ) {
			$campaignInfo->sessionCapping = $this->filterNum(
				$_POST['sessionCapping']
			);
		}		
		
		if( $this->filterString($_POST['startDate']) ) {
			$dt = new \Date($this->filterString($_POST['startDate']));		
			$campaignInfo->startDate = $dt;
		} else {
			$campaignInfo->startDate = new \Date(time());
		}
		
		if( $this->filterString($_POST['endDate']) ) {
			$dt = new \Date($this->filterString($_POST['endDate']));
			$campaignInfo->endDate = $dt;
		} else {
			$campaignInfo->endDate = '';
		}
        
		if( $campaignDLL->modify(&$campaignInfo) ) {
			/*
				HACKERY!
				
				Ok, this is a hack because the published API is broken. It'd be
				easy to fix this (a single line) but the goal here is to not modify ANY existing code... so we kludge.
				
				In any case, the API doesn't allow you to unset an end date, so
				we have to hack it.
			*/
			
			if( $id && !$this->filterString($_POST['endDate']) ) {
				$tmp = \OA_Dal::factoryDO('campaigns');
				$tmp->campaignid = $id;
				$tmp->expire_time = '';
				$tmp->update();
			}
			
			return new Response(
				array('campaignId' => $campaignInfo->campaignId)
			);
		}
		
		return $this->respondWithError(false);
		
	}

}

?>
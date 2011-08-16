<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/Banner.php';
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
			'delete' => array(
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

		if( isset($_POST['advertiserId']) ) {
			// need to ensure you can't add or move a campaign to another
			// agency's client
			$clientid = $this->filterNum($_POST['advertiserId']);
			if( !\OA_Permission::hasAccessToObject('clients', $clientid) ) {
				return $this->respondWithError('Cannot use client');
			}

			$campaignInfo->advertiserId = $clientid;
		}

		if( isset($_POST['priority']) ) {
			$campaignInfo->priority = $this->filterNum($_POST['priority']);
		}

		if( isset($_POST['campaignName']) ) {
			$campaignInfo->campaignName = $this->filterString(
				$_POST['campaignName']
			);
		}
		if( isset($_POST['weight']) ) {
			$campaignInfo->weight = $this->filterNum($_POST['weight']);
		}

		if( isset($_POST['targetImpressions']) ) {
			$campaignInfo->targetImpressions = $this->filterNum(
				$_POST['targetImpressions']
			);
		}

		if( isset($_POST['targetClicks']) ) {
			$campaignInfo->targetClicks = $this->filterNum(
				$_POST['targetClicks']
			);
		}

		if( isset($_POST['targetConversions']) ) {
			$campaignInfo->targetConversions = $this->filterNum(
				$_POST['targetConversions']
			);
		}

		if( isset($_POST['revenueType']) ) {
			$campaignInfo->revenueType = $this->filterNum(
				$_POST['revenueType']
			);
		}

		if( isset($_POST['comments']) ) {
			$campaignInfo->comments = $this->filterString($_POST['comments']);
		}

		if( isset($_POST['viewWindow']) ) {
			$campaignInfo->viewWindow = $this->filterNum($_POST['viewWindow']);
		}

		if( isset($_POST['clickWindow']) ) {
	        $campaignInfo->clickWindow = $this->filterNum(
	        	$_POST['clickWindow']
	        );
	    }

		if( isset($_POST['impressions']) ) {
			$imp = -1;
			if( $_POST['impressions'] ) {
				$imp = $this->filterNum($_POST['impressions']);
			}
			$campaignInfo->impressions = $imp;
		}

		if( isset($_POST['clicks']) ) {
			$clk = -1;
			if( $_POST['clicks'] ) {
				$clk = $this->filterNum($_POST['clicks']);
			}
			$campaignInfo->clicks = $clk;
		}

		if( isset($_POST['conversions']) ) {
			$cnv = -1;
			if( $_POST['conversions'] ) {
				$cnv = $this->filterNum($_POST['conversions']);
			}
			$campaignInfo->conversions = $cnv;
		}

		if( isset($_POST['block']) && $this->filterNum($_POST['block']) ) {
			$campaignInfo->block = $this->filterNum($_POST['block']);
		}

		if( isset($_POST['capping']) && $this->filterNum($_POST['capping'])) {
			$campaignInfo->capping = $this->filterNum($_POST['capping']);
		}

		if( isset($_POST['sessionCapping'])
			&& $this->filterNum($_POST['sessionCapping']) ) {
			$campaignInfo->sessionCapping = $this->filterNum(
				$_POST['sessionCapping']
			);
		}


		if( isset($_POST['startDate']) ) {
			if( $this->filterString($_POST['startDate']) ) {
				$dt = new \Date($this->filterString($_POST['startDate']));
				$campaignInfo->startDate = $dt;
			} else {
				$campaignInfo->startDate = new \Date(time());
			}
		}

		if( isset($_POST['endDate']) ) {
			if( $this->filterString($_POST['endDate']) ) {
				$dt = new \Date($this->filterString($_POST['endDate']));
				$campaignInfo->endDate = $dt;
			} else {
				$campaignInfo->endDate = '';
			}
		}

		if( $campaignDLL->modify(&$campaignInfo) ) {
			/*
				HACKERY!

				Ok, this is a hack because the published API is broken. It'd be
				easy to fix this (a single line) but the goal here is to not modify ANY existing code... so we kludge.

				In any case, the API doesn't allow you to unset an end date, so
				we have to hack it.
			*/

			if( $id && isset($_POST['endDate'])
				&& !$this->filterString($_POST['endDate'])
			) {
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

	public function delete() {
		$id = $this->filterNum($_POST['campaignId']);

		// permission check, yo
		if( $id && !\OA_Permission::hasAccessToObject('campaigns', $id) ) {
			return $this->respondWithError('No campaign found');
		}

		$campaignDLL = new \OA_Dll_Campaign;
		if( !$campaignDLL->delete($id) ) {
			return $this->respondWithError('Unable to delete campaign');
		}

		return new Response(array(
			'campaignId' => $id
		));
	}

	public function deactivate() {
		$id = $this->filterNum($_POST['campaignId']);

		// permission check, yo
		if( $id && !\OA_Permission::hasAccessToObject('campaigns', $id) ) {
			return $this->respondWithError('No campaign found');
		}

		$bannerDLL = new \OA_Dll_Banner;

		$banners = \OA_Dal::factoryDO('banners');
		$banners->status = \OA_ENTITY_STATUS_PAUSED;
		$banners->whereAdd('campaignid = ' . $id);

		$banners->update(\DB_DATAOBJECT_WHEREADD_ONLY);

		return new Response(array(
			'campaignId' => $id
		));
	}

	public function stats() {

		$campaignID = $this->filterNum($_POST['campaignid']);
		$campaign = new \OA_Dll_Campaign;

		$start = $this->filterString($_POST['start']);
		$end = $this->filterString($_POST['end']);

		// this may all see a bit heavy handed, but I just want to make sure
		// everything is UTC time
		$tz = new \DateTimeZone('UTC');

		if( !$start ) {
			$start = new \DateTime(date('Y-m-d'), $tz);
			$start->modify('-1 day');
		} else {
			$start = new \DateTime($start, $tz);
		}

		if( !$end ) {
			$end = new \DateTime($start->format('Y-m-d'), $tz);
			$end->modify('+1 day');
		} else {
			$end = new \DateTime($end, $tz);
		}

		$data = array();

		$s = new \Date($start->format('Y-m-d'));
		$e = new \Date($end->format('Y-m-d'));
		$ret = $campaign->getCampaignDailyStatistics(
			$campaignID, $s, $e, true, &$data
		);

		if( $ret == false ) {
			return $this->respondWithError('Unable to fetch stats data');
		}

		return new Response($data);
	}

}

?>
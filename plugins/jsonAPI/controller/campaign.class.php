<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH.'/lib/OX/Translation.php';
require_once MAX_PATH.'/lib/OX/Util/Utils.php';
//require_once MAX_PATH.'/lib/OA/Dal.php';

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
		$campaignDLL = new \OA_DLL_Campaign;
		$campaignDAL = \OA_Dal::factoryDAL('campaigns');

		$clientDLL = new \OA_DLL_Advertiser;		

		$dbh = \OA_DB::singleton();
		$tableM = $dbh->quoteIdentifier(\OA_Dal::getTablePrefix().'campaigns', true);
		$tableC = $dbh->quoteIdentifier(\OA_Dal::getTablePrefix().'clients', true);
		
		$q =  "SELECT m.campaignId as id, m.status as status, m.revenue_type as revenue_type".
            " FROM ".$tableM." AS m".
            ",".$tableC." AS c".
            " WHERE m.clientid=c.clientid".
            " AND c.agencyid=". \DBC::makeLiteral($agencyID) .
//            " AND m.status=".\OA_ENTITY_STATUS_RUNNING .
	        " AND m.type = ". \DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;
		$ret = $dbh->query($q);
		
		$return = array();
		
		while( ($row = $ret->fetchRow())) {
			$data = false;
			$campaignDLL->getCampaign($row['id'], &$data);
			
			$clientData = false;
			$clientDLL->getAdvertiser($data->advertiserId, &$clientData);
			$data->client = $clientData;

			if( $data->startDate && is_object($data->startDate) ) {
				$data->startDate = $data->startDate->getTime();
			}
			if( $data->endDate && is_object($data->endDate) ) {
				$data->endDate = $data->endDate->getTime();
			}

			$desc = array();

			$k = \OX_Util_Utils::getCampaignTypeTranslationKey(
				$data->priority
			);
			
			$desc['type'] = $GLOBALS[$k];
			
			$k = \OX_Util_Utils::getCampaignStatusTranslationKey(
				$row['status']
			);

			$desc['status'] = $GLOBALS[$k];
			
			if( $data->priority == -1 ) {
				$desc['priority'] = 'Exclusive';
			} elseif( $data->priority == -2 ) {
				$desc['priority'] = 'ECPM';
			} elseif( $data->priority == 0 ) {
				$desc['priority'] = 'Low';
			} else {
				$desc['priority'] = 'High ('.$data->priority.')';
			}

			switch( $row['revenue_type'] ) {
				case \MAX_FINANCE_CPM:
					$desc['revenueType'] = 'CPM (Impressions)';
					break;
				case \MAX_FINANCE_CPC:
					$desc['revenueType'] = 'CPC (Clicks)';
					break;
				case \MAX_FINANCE_CPA:
					$desc['revenueType'] = 'CPA (Activity)';
					break;
				case \MAX_FINANCE_MT:
				default:
					$desc['revenueType'] = 'Tenancy';
					break;
			}

			$data->description = $desc;

			$return[] = $data;
		}
		
		$ret->free();

		return new Response($return);
	}

}

?>
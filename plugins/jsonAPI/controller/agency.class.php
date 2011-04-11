<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH . '/lib/OA/Dll/Agency.php';
require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';


class agency extends \jsonAPI\controller {

	public function __construct($a) {
		$this->action = $a;
		if( !$this->verifySession() ) {
			throw new \jsonAPI\exception('Invalid session credentials');
		}
	}

	public function main() {
		return new Response(array(
			'listall' => array(
				'void'
			),
			'stats' => array(
				'string (type)',
				'range (string)',
				'int (start)',
				'int (end)'
			),
			'advertisers' => array(
				'void'
			),
			'campaigns' => array(
				'void'
			)
		));
	}
	
	public function stats() {
	
		$agencyID = $this->getThisUser()->aAccount['agency_id'];

		$start = $this->filterNum(
			$_POST['start'], strtotime('00:00:00 Yesterday')
		);
		$end = $this->filterNum(
			$_POST['end'], strtotime('23:59:59 Yesterday')
		);
		
		$agency = new \OA_Dll_Agency;
		
		if( $_POST['range'] == 'week' ) {
			
			$start = strtotime('-7 days', $start);
			for( $i=0; $i<7; $i++ ) {
				$data = array();

				$startDate = strtotime('+'.$i.' days 00:00:00', $start);
				$endDate = strtotime('+'.$i.' days 23:59:59', $start);
				
				$agency->getAgencyDailyStatistics(
					$agencyID, new \Date($startDate), new \Date($endDate),
					true, &$data
				);
				
				$return[] = $data;
			}
			
			return new Response($return);
		}
		
		$data = false;
		
		$agency->getAgencyDailyStatistics(
			$agencyID, new \Date($start), new \Date($end), true, &$data
		);

		return new Response($data);
	}
	
	public function advertisers() {
		$agencyID = \OA_Permission::getAgencyId();
		$agencyLib = new \OA_Dll_Agency;
		$advLib = new \OA_Dll_Advertiser;
		$campLib = new \OA_Dll_Campaign;
		
		$advertisers = array();
		
		$advLib->getAdvertiserListByAgencyId($agencyID, &$advertisers);
		
		return new Response($advertisers);
	}
	
	public function campaigns() {
		$agencyID = \OA_Permission::getAgencyId();
		$agencyLib = new \OA_Dll_Agency;
		$advLib = new \OA_Dll_Advertiser;
		$campLib = new \OA_Dll_Campaign;
		
		$advertisers = array();
		
		$advLib->getAdvertiserListByAgencyId($agencyID, &$advertisers);
		$return = array();
		
		foreach( $advertisers as $advertiser ) {
			$entry = array();
			$entry['advertiser'] = $advertiser;
			
			$campaigns = array();
			$campLib->getCampaignListByAdvertiserId(
				$advertiser->advertiserId, &$campaigns
			);
			
			$entry['campaigns'] = $campaigns;
			
			$return[] = $entry;
		}
		
		return new Response($return);
	}

}

?>
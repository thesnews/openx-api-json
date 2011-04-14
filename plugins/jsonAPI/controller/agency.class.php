<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH . '/lib/OA/Dll/Agency.php';
require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';

require_once MAX_PATH.'/lib/OA/Dal/Statistics/Agency.php';


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
				'range (string)',
				'start (string [YYYY-MM-DD])',
				'end (string [YYYY-MM-DD])'
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
	
		$agencyID = \OA_Permission::getAgencyId();
		$agency = new \OA_Dll_Agency;
		
		$range = false;
		$offset = false;
		switch( $this->filterString($_POST['range']) ) {
			case 'week':
				$offset = '-1 week';
			case 'day':
				$offset = '-1 day';
			case 'month':
				$offset = '-1 month';
				$range = $this->filterString($_POST['range']);
				break;
		}

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
		
		if( $range ) {
			$data = array();
			
			$e = new \Date($start->format('Y-m-d'));
			$s = new \Date($start->modify($offset)->format('Y-m-d'));

			$agency->getAgencyDailyStatistics($agencyID, $s, $e, true, &$data);

			return new Response($data);
			
		}
		
		$data = array();
		
		$s = new \Date($start->format('Y-m-d'));
		$e = new \Date($end->format('Y-m-d'));
		$agency->getAgencyDailyStatistics($agencyID, $s, $e, true, &$data);

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
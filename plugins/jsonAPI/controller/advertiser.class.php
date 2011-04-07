<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';

class advertiser extends \jsonAPI\controller {

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
				'int (start)',
				'int (end)'
			)
		));
	}
	
	public function stats() {
		$agencyID = $this->getThisUser()->aAccount['agency_id'];

		$advertisers = false;
		$campaigins = false;
		$banners = false;
		
		$advDLL = new \OA_Dll_Advertiser;
		$campDLL = new \OA_Dll_Campaign;
		$banDLL = new \OA_Dll_Banner;
		
		$advDLL->getAdvertiserListByAgencyId($agencyID, &$advertisers);
		
		$statsAll = array();
		
		foreach( $advertisers as $advertiser ) {

			$advDLL->getAdvertiserBannerStatistics(
				$advertiser->advertiserId,
				new \Date('00:00:00 Yesterday'),
				new \Date('00:00:00 Today'),
				true,
				&$data
			);
			
			$data->find();
			
			$info = array();
			$info[] = $advertiser;

            while( $data->fetch() ) {
            	$info[] = 'foo';
				$info[] = $data->toArray();
			}
			
			$statsAll[] = $info;
		}
		
		return new Response($statsAll);
	}

}

?>
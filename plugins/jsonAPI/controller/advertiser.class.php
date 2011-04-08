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

		$start = $this->filterNum(
			$_POST['start'], strtotime('00:00:00 Yesterday')
		);
		$end = $this->filterNum(
			$_POST['end'], strtotime('23:59:59 Yesterday')
		);

		$advertisers = false;
		$campaigins = false;
		$banners = false;
		
		$advDLL = new \OA_Dll_Advertiser;
		$campDLL = new \OA_Dll_Campaign;
		$banDLL = new \OA_Dll_Banner;
		
		$advDLL->getAdvertiserListByAgencyId($agencyID, &$advertisers);
		
		$statsAll = array();
		
		foreach( $advertisers as $advertiser ) {
			
			$data = array();
			
			$advDLL->getAdvertiserBannerStatistics(
				$advertiser->advertiserId,
				new \Date($start),
				new \Date($end),
				true,
				&$data
			);
			
			$info = array();
			
			$info['advertiser'] = $advertiser;
			$info['stats'] = array();

			if( get_class($data) !== 'MDB2RecordSet' ) {
				$statsAll[] = $info;
				continue;
			}

			$data->find();

            while( $data->fetch() ) {
				$info['stats'][] = $data->toArray();
			}
			
			$statsAll[] = $info;
		}
		
		return new Response($statsAll);
	}

}

?>
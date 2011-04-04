<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';

class banner extends \jsonAPI\controller {

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
			'info' => array(
				'int'
			),
			'add' => array(
				'(struct) OA_Dll_BannerInfo',
			),
			'edit' => array(
				'(struct) OA_Dll_BannerInfo',
			),
			'stats' => array(
				'int (optional)',
				'string (optional)'
			)
		));
	}
	
	public function listall() {
		$agencyID = $this->getThisUser()->aAccount['agency_id'];

		$advertisers = false;
		$campaigins = false;
		$banners = false;
		
		$advDLL = new \OA_Dll_Advertiser;
		$campDLL = new \OA_Dll_Campaign;
		$banDLL = new \OA_Dll_Banner;
		
		$advDLL->getAdvertiserListByAgencyId($agencyID, &$advertisers);
		
		return new Response($advertisers);
	}

}

?>
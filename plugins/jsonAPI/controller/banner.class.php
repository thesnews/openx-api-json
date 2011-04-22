<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/Banner.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/banner.class.php';

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
		$agencyID = \OA_Permission::getAgencyId();

		$campaigns = \OA_Dal::factoryDO('campaigns');
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');
		$banners = \OA_Dal::factoryDO('banners');
		
		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);

		$campaigns->type = \DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;

		$campaigns->joinAdd($clients);
		$banners->joinAdd($campaigns);
		
		$banners->find();
		
		$out = array();
		
		while( $banners->fetch() ) {
			$out[] = new \jsonAPI\model\banner($banners->toArray());
		}
				
		return new Response($out);

	}

}

?>
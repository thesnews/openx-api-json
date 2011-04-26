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
	
	public function fetch() {
		$bannerId = $this->filterNum($_POST['bannerId']);
		if( !$bannerId ) {
			return $this->respondWithError('No banner id supplied');
		}

		if( $bannerId 
			&& !\OA_Permission::hasAccessToObject('banners', $bannerId) ) {
			return $this->respondWithError('No banner found');
		}

		$campaigns = \OA_Dal::factoryDO('campaigns');
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');
		$banners = \OA_Dal::factoryDO('banners');
		
		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);

		$campaigns->type = \DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;

		$campaigns->joinAdd($clients);
		$banners->joinAdd($campaigns);
		
		$banners->bannerid = $bannerId;

		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$banners->selectAdd($ns.'banners.comments as banner_comments');
		$banners->selectAdd($ns.'banners.weight as banner_weight');
		
		$banners->find();
		
		$out = array();
		
		while($banners->fetch()) {
			$o = new \jsonAPI\model\banner($banners->toArray());
			
			$out[] = $o;
		}
		
		$banners->free();

		return new Response($out);
		
	}
	
	public function save() {
		$id = $this->filterNum($_POST['bannerId']);
		
		// permission check, yo
		if( $id && !\OA_Permission::hasAccessToObject('banners', $id) ) {
			return $this->respondWithError('No banner found');
		}
		
		$bannerDLL = new \OA_Dll_Banner;
		$bannerInfo = new \OA_Dll_BannerInfo;
		
		if( $id ) {
			$bannerInfo->bannerId = $id;
		}
		
//		if( isset($_POST['bannerName']) ) {
			$bannerInfo->bannerName = $this->filterString(
				$_POST['bannerName']
			);
//		}
		
		if( isset($_POST['url']) ) {
			$bannerInfo->url = $this->filterString($_POST['url']);
		}
		
		if( isset($_POST['alt']) ) {
			$bannerInfo->alt = $this->filterString($_POST['alt']);
		}
		
		if( isset($_POST['keyword']) ) {
			$bannerInfo->keyword = $this->filterString($_POST['keyword']);
		}
		
		if( isset($_POST['target']) && $this->filterString($_POST['target']) ) {
			$bannerInfo->target = $this->filterString($_POST['target']);
		} else {
			$bannerInfo->target = '';
		}
		
		if( isset($_POST['width']) && $this->filterNum($_POST['width']) ) {
			$bannerInfo->width = $this->filterNum($_POST['width']);
		}
		
		if( isset($_POST['height']) && $this->filterNum($_POST['height']) ) {
			$bannerInfo->height = $this->filterNum($_POST['height']);
		}

		if( isset($_POST['weight']) ) {
			$bannerInfo->weight = $this->filterNum($_POST['weight']);
		}
		
		if( isset($_POST['comments']) ) {
			$bannerInfo->comments = $this->filterString($_POST['comments']);
		}
		
		if( $bannerDLL->modify(&$bannerInfo) ) {
			
			return new Response(array('bannerId' => $bannerInfo->bannerId));
		}
		
		return $this->respondWithError('Unable to save banner');
	}

}

?>
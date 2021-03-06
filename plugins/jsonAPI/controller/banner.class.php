<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';
require_once MAX_PATH.'/lib/OA/Dll/Banner.php';
require_once MAX_PATH.'/lib/OA/Dll/Publisher.php';
require_once MAX_PATH.'/lib/OA/Dll/Zone.php';
require_once LIB_PATH.'/Plugin/Component.php';
require_once MAX_PATH.'/lib/OX/Util/Utils.php';

require_once MAX_PATH.'/lib/max/Admin_DA.php';

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

		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$banners->selectAdd($ns.'banners.comments as banner_comments');
		$banners->selectAdd($ns.'banners.weight as banner_weight');
		$banners->selectAdd($ns.'banners.status as banner_status');


		if( $_POST['status'] == 'active' ) {
			$banners->whereAdd(
				$ns.'banners.status = '.\OA_ENTITY_STATUS_RUNNING
			);
		} elseif( $_POST['status'] == 'inactive' ) {
			$banners->whereAdd(
				$ns.'banners.status != '.\OA_ENTITY_STATUS_RUNNING
			);
		}

		$order = 'description asc';

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
			case 'size':
				$order = 'width desc, height desc';
				break;
			case 'campaign':
				$order = 'campaignname asc';
				break;
		}

		$banners->orderBy($order);

		if( $_POST['filter'] ) {
			$q = $_POST['filter'];
			$banners->whereAdd(
				'('
					.$ns."banners.filename like '%".$banners->escape($q)
						."%' or "
					.$ns."banners.alt like '%".$banners->escape($q)."%' or "
					.$ns."banners.statustext like '%".$banners->escape($q)
						."%' or "
					.$ns."banners.description like '%".$banners->escape($q)
						."%' or "
					.$ns."banners.comments like '%".$banners->escape($q)
						."%' or "
					.$ns."banners.keyword like '%".$banners->escape($q)."%'"
				.')'
			);
		}


		if( isset($_POST['limit']) ) {
			if( isset($_POST['offset']) ) {
				$banners->limit(
					$this->filterNum($_POST['offset']),
					$this->filterNum($_POST['limit'])
				);
			} else {
				$banners->limit(
					$this->filterNum($_POST['offset'])
				);
			}
		}


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
		$banners->selectAdd($ns.'banners.status as banner_status');
		$banners->selectAdd($ns.'banners.block as banner_block');
		$banners->selectAdd($ns.'banners.capping as banner_capping');
		$banners->selectAdd($ns.'banners.session_capping as banner_session_capping');

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

		if( isset($_POST['bannerName']) ) {
			$bannerInfo->bannerName = $this->filterString(
				$_POST['bannerName']
			);
		}

		if( isset($_POST['campaignId']) ) {
			// need to ensure you can't add or move a banner to another
			// agency's campaign
			$campaignId = $this->filterNum($_POST['campaignId']);
			if( !\OA_Permission::hasAccessToObject('campaigns', $campaignId) ) {
				return $this->respondWithError('Cannot use client');
			}

			$bannerInfo->campaignId = $campaignId;
		}

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

		if( isset($_POST['block']) && $this->filterNum($_POST['block']) ) {
			$bannerInfo->block = $this->filterNum($_POST['block']);
		}

		if( isset($_POST['capping']) && $this->filterNum($_POST['capping'])) {
			$bannerInfo->capping = $this->filterNum($_POST['capping']);
		}

		if( isset($_POST['sessionCapping'])
			&& $this->filterNum($_POST['sessionCapping']) ) {
			$bannerInfo->sessionCapping = $this->filterNum(
				$_POST['sessionCapping']
			);
		}

		if( isset($_POST['comments']) ) {
			$bannerInfo->comments = $this->filterString($_POST['comments']);
		}

		if( isset($_POST['status']) ) {
			if( $_POST['status'] == \OA_ENTITY_STATUS_RUNNING ) {
				$bannerInfo->status = \OA_ENTITY_STATUS_RUNNING;
			} else {
				$bannerInfo->status = 1;
			}
		}

		if( isset($_POST['fileData']) && is_array($_POST['fileData']) ) {

			$fileData = array(
				'filename' => $this->filterString(
					$_POST['fileData']['filename']
				),
				'content' => base64_decode($_POST['fileData']['content']),
				'editswf' => ($_POST['editswf']) ? true : false
			);

			$bannerInfo->storageType = 'web';
			$bannerInfo->aImage = $fileData;
		}

		if( isset($_POST['htmlTemplate']) ) {
			$code = $_POST['htmlTemplate'];

			$bannerInfo->storageType = 'html';
			$bannerInfo->htmlTemplate = $code;
		}


		if( $bannerDLL->modify(&$bannerInfo) || isset($_POST['zones'])  ) {

			// make sure the banner is linked to the zone
			$agencies = \OA_Dal::factoryDO('agency');
			$publishers = \OA_Dal::factoryDO('affiliates');
			$zones = \OA_Dal::factoryDO('zones');

			$agencies->account_id = \OA_Permission::getAccountId();
			$publishers->joinAdd($agencies);
			$zones->joinAdd($publishers);

			// fetch banner data again to make sure the size is correct
			$bannerData = false;
			$bannerDLL->getBanner($bannerInfo->bannerId, &$bannerData);

			if( isset($_POST['zones']) && is_array($_POST['zones']) ) {
				$zones = $_POST['zones'];
				$toLink = array();
				$toUnLink = array();

				$currentlyLinked = \Admin_DA::getAdZones(
					array('ad_id' => $bannerInfo->bannerId), false, 'zone_id'
				);

				$tmp = array();
				foreach( $currentlyLinked as $z ) {
					$tmp[] = $z['zone_id'];
				}
				$currentlyLinked = $tmp;

				foreach( $zones as $zone ) {
					if( !in_array($zone, $currentlyLinked) ) {
						$z = new \OA_Dll_Zone;
						$z->linkBanner($zone, $bannerInfo->bannerId);
					}
				}
				foreach( $currentlyLinked as $zone ) {
					if( !in_array($zone, $zones) ) {
						$z = new \OA_Dll_Zone;
						$z->unlinkBanner($zone, $bannerInfo->bannerId);
					}
				}
			}
			// set the targeting
			if( isset($_POST['targeting']) && is_array($_POST['targeting']) ) {
				$targeting = $_POST['targeting'];


 				// have to convert the targeting array to an object
 				foreach( $targeting as $order => $data ) {
 					$ti = new \OA_Dll_TargetingInfo;
 					$ti->logical = $data['logical'];
 					$ti->type = $data['type'];
 					$ti->comparison = $data['comparison'];
 					$ti->data = $data['value'];

 					$targeting[$order] = $ti;
 				}

 				ksort($targeting);
 				$targeting = array_values($targeting);

				$ret = $bannerDLL->setBannerTargeting(
					$bannerInfo->bannerId, &$targeting
				);

				if( !$ret ) {
					error_log('error setting targeting');
				}
			} else {
				$t = array();
				$bannerDLL->setBannerTargeting(
					$bannerInfo->bannerId, $t
				);
			}


			return new Response(array('bannerId' => $bannerInfo->bannerId));
		}

		return $this->respondWithError('Unable to save banner');
	}

	public function delete() {
		$id = $this->filterNum($_POST['bannerId']);

		// permission check, yo
		if( $id && !\OA_Permission::hasAccessToObject('banners', $id) ) {
			return $this->respondWithError('No banner found');
		}

		$bannerDLL = new \OA_Dll_Banner;
		if( !$bannerDLL->delete($id) ) {
			return $this->respondWithError('Unable to delete banner');
		}

		return new Response(array(
			'bannerId' => $id
		));
	}

}

?>
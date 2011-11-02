<?php
namespace jsonAPI\model;

require_once MAX_PATH.'/lib/max/resources/res-iab.inc.php';
require_once MAX_PATH.'/lib/OA/Dll/Banner.php';

require_once MAX_PATH.'/lib/max/Admin_DA.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/generic.class.php';

class banner extends \jsonAPI\model {
	public static $IABSizes;
	public function __init() {
		$desc = array();

        $this->stack['htmlTemplate'] = $this->stack['htmltemplate'];
        $this->stack['imageURL'] = $this->stack['imageurl'];
        $this->stack['storageType'] = $this->stack['storagetype'];
        $this->stack['bannerName'] = $this->stack['description'];
        $this->stack['campaignId'] = $this->stack['campaignid'];
        $this->stack['bannerId'] = $this->stack['bannerid'];
        $this->stack['bannerText'] = $this->stack['bannertext'];
		$this->stack['capping'] = $this->stack['banner_capping'];
		$this->stack['sessionCapping'] = $this->stack['banner_session_capping'];
		$this->stack['session_capping'] =
			$this->stack['banner_session_capping'];
        $this->stack['block'] = $this->stack['banner_block'];
        $this->stack['alt'] = $this->stack['alt'];

        $this->stack['bannername'] = $this->stack['description'];

		$this->stack['campaignstatus'] = $this->stack['status'];
		$this->stack['status'] = $this->stack['banner_status'];

		$this->stack['active'] = (
			$this->stack["status"] == \OA_ENTITY_STATUS_RUNNING
		);

		$this->stack['weight'] = $this->stack['banner_weight'];
		$this->stack['comments'] = $this->stack['banner_comments'];

		$p = $GLOBALS['_MAX']['CONF']['webpath']['images'];
		$this->stack['filepath'] = $p."/".$this->stack['filename'];


		// mostly borrowed from www/admin/lib-size.inc.php:35
        if( isset($this->stack['width']) && isset($this->stack['height']) ) {
            $width = $this->stack['width'];
            $height = $this->stack['height'];

            if( $width == -1 ) {
            	$width = '*';
            }
            if( $height == -1 ) {
            	$height = '*';
            }

			$size = sprintf('Custom (%s x %s', $width, $height);

			foreach( self::$IABSizes as $key => $sizes ) {
				if( $sizes['width'] == $width
					&& $sizes['height'] == $height
				) {

					$size = $GLOBALS['strIab'][$key];
				}
			}

  			$desc['size'] = $size;

        }

		$bannerDll = new \OA_Dll_Banner;
		$stats = array();

		$bannerDll->getBannerPublisherStatistics(
			$this->stack['bannerid'], new \Date($this->stack['activate_time']),
			new \Date, true, &$stats
		);
		if( $stats ) {
			$stats->find();
			$stats->fetch();


			$desc['statistics'] = $stats->toArray();
		}

		$this->stack['description'] = $desc;

		$targetingData = array();
		$bannerDll->getBannerTargeting(
			$this->stack['bannerid'], &$targetingData
		);

		$this->stack['targeting'] = $targetingData;

		// get the linked zones
	    $aLinkedZones = \Admin_DA::getAdZones(
	    	array('ad_id' => $this->stack['bannerId']), false, 'zone_id'
	    );

/*		$zones = array();
		foreach( $aLinkedZones as $zone ) {
			if( $zone['zone_id'] ) {
				$zones[] = new \jsonAPI\model\generic($zone);
			}
		}
*/
		$this->stack['zones'] = $aLinkedZones;

	}

}

banner::$IABSizes = $phpAds_IAB;
?>
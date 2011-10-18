<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH.'/lib/OA/Dll/Publisher.php';
require_once MAX_PATH.'/lib/OA/Dll/Zone.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/generic.class.php';


class zone extends \jsonAPI\controller {

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
			)
		));
	}

	public function listall() {
		$agencyID = \OA_Permission::getAgencyId();

		$agencies = \OA_Dal::factoryDO('agency');
		$publishers = \OA_Dal::factoryDO('affiliates');
		$zones = \OA_Dal::factoryDO('zones');

		$agencies->account_id = \OA_Permission::getAccountId();
		$publishers->joinAdd($agencies);
		$zones->joinAdd($publishers);

		$zones->find();

		$out = array();

		while( $zones->fetch() ) {
			$out[] = new \jsonAPI\model\generic($zones->toArray());
		}

		return new Response($out);
    }

}

?>

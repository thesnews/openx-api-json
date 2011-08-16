<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/client.class.php';

class client extends \jsonAPI\controller {

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

	}

	public function listall() {
		$agencyID = \OA_Permission::getAgencyId();
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');

		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);

		// omit the market client
		$clients->type = \DataObjects_Clients::ADVERTISER_TYPE_DEFAULT;

		// name collisions with the commennts attrib, so we have to add a
		// specific add call with the full namespaced table
		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$clients->selectAdd($ns.'clients.contact as _contact');
		$clients->selectAdd($ns.'clients.email as _email');

		$order = 'clientname asc';

		switch( $_POST['sort'] ) {
			case 'contact':
				$order = '_contact asc';
				break;
			case 'email':
				$order = '_email asc';
				break;
		}

		$clients->orderBy($order);

		$clients->find();

		$out = array();
		while( $clients->fetch() ) {
			$out[] = new \jsonAPI\model\client($clients->toArray());
		}

		return new Response($out);
	}

}

?>
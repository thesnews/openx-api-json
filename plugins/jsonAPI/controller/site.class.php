<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/lib/OA/Dll/Channel.php';

require_once MAX_PATH.'/plugins/jsonAPI/model/channel.class.php';


class site extends \jsonAPI\controller {

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

	public function listChannels() {
		$agencyID = \OA_Permission::getAgencyId();

		$channel = \OA_Dal::factoryDO('channel');

		$channel->agencyid = $agencyID;
		$channel->find();

		$out = array();

		while( $channel->fetch() ) {
			$out[] = new \jsonAPI\model\channel($channel->toArray());
		}

		return new Response($out);

    }

}


?>

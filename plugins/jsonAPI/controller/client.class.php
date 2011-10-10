<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

//require_once MAX_PATH.'/lib/OA/Dll/Advertiser.php';

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

		if( $_POST['filter'] ) {
			$q = $_POST['filter'];
			$clients->whereAdd(
				'('
					.$ns."clients.clientname like '%".$clients->escape($q)
						."%' or "
					.$ns."clients.contact like '%".$clients->escape($q)
						."%' or "
					.$ns."clients.email like '%".$clients->escape($q)
						."%' or "
					.$ns."clients.comments like '%".$clients->escape($q)
						."%'"
				.')'
			);
		}

		$clients->find();

		$out = array();
		while( $clients->fetch() ) {
			$out[] = new \jsonAPI\model\client($clients->toArray());
		}

		return new Response($out);
	}

	public function fetch() {

		$clientID = $this->filterNum($_POST['clientId']);
		if( !$clientID ) {
			return $this->respondWithError('No client id supplied');
		}

		$agencyID = \OA_Permission::getAgencyId();
		$clients = \OA_Dal::factoryDO('clients');
		$agencies = \OA_Dal::factoryDO('agency');

		// only campaigns for this user
		$agencies->account_id = \OA_Permission::getAccountId();
		$clients->joinAdd($agencies);

		// name collisions with the commennts attrib, so we have to add a
		// specific add call with the full namespaced table
		$ns = $GLOBALS['_MAX']['CONF']['table']['prefix'];
		$clients->selectAdd($ns.'clients.contact as _contact');
		$clients->selectAdd($ns.'clients.email as _email');

		$clients->clientid = $clientID;

		$clients->find();

		$out = array();

		while($clients->fetch()) {
			$o = new \jsonAPI\model\client($clients->toArray());

			$out[] = $o;
		}

		$clients->free();

		return new Response($out);
	}

	public function save() {
/*
        $aAdvertiser['clientname'] = $aFields['clientname'];
    }
    // Default fields
    $aAdvertiser['contact']  = $aFields['contact'];
    $aAdvertiser['email']    = $aFields['email'];
    $aAdvertiser['comments'] = $aFields['comments'];

    // Same advertiser limitation
    $aAdvertiser['advertiser_limitation']  = $aFields['advertiser_limitation'] == '1' ? 1 : 0;

    // Reports
    $aAdvertiser['report'] = $aFields['report'] == 't' ? 't' : 'f';
    $aAdvertiser['reportdeactivate'] = $aFields['reportdeactivate'] == 't' ? 't' : 'f';
    $aAdvertiser['reportinterval'] = (int)$aFields['reportinterval'];
    if ($aAdvertiser['reportinterval'] == 0 ) {
       $aAdvertiser['reportinterval'] = 1;
    }
    if ($aFields['reportlastdate'] == '' || $aFields['reportlastdate'] == '0000-00-00' ||  $aFields['reportprevious'] != $aAdvertiser['report']) {
        $aAdvertiser['reportlastdate'] = date ("Y-m-d");
    }
*/
		$id = $this->filterNum($_POST['clientId']);

		$agencyID = \OA_Permission::getAgencyId();

        $doClients = \OA_Dal::factoryDO('clients');

        if( $id ) {
	        $doClients->get($id);
			// permission check, yo
	        if( $doClients->agencyid != $agencyID ) {
				return $this->respondWithError('No client found');
	        }
	    }

	    $save = array();

	    $save['agencyid'] = $agencyID;

		if( isset($_POST['clientName']) ) {
			$save['clientname'] = $this->filterString($_POST['clientName']);
		}

		if( isset($_POST['contact']) ) {
			$save['contact'] = $this->filterString($_POST['contact']);
		}

		if( isset($_POST['email']) ) {
			$save['email'] = $this->filterString($_POST['email']);
		}

		if( isset($_POST['limitation']) ) {
			$save['advertiser_limitation'] = 0;
			if( $_POST['limitation'] == 1 ) {
				$save['advertiser_limitation'] = 1;
			}
		}

		if( isset($_POST['report']) ) {
			$save['report'] = $this->filterString($_POST['report']);
		}

		$save['agencyid'] = $agencyID;

        $doClients->setFrom($save);
        $doClients->updated = \OA::getNow();

        if( $id ) {
        	if( $doClients->update() ) {
				return new Response(
					array('clientId' => $id)
				);
        	}
        } else {
        	if( ($id = $doClients->insert()) ) {
				return new Response(
					array('clientId' => $id)
				);
        	}
        }

		return $this->respondWithError(false);


	}

}

?>
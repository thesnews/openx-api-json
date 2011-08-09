<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

require_once MAX_PATH.'/plugins/jsonAPI/model/generic.class.php';

class targeting extends \jsonAPI\controller {

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
		));
	}

	public function getResource() {
		$parts = explode(':', $_POST['for']);

		if( count($parts) != 2 ) {
			return $this->respondWithError('Invalid type');
		}

        $file = \MAX_PATH
        	.$GLOBALS['_MAX']['CONF']['pluginPaths']['plugins']
        	.'/deliveryLimitations/'
        	.$parts[0]
        	.'/'
        	.$parts[1]
        	.'.res.inc.php';

        if( is_readable($file) ) {
			include $file;

            $out = array();
            foreach( $res as $cc => $label ) {
            	if( $cc == 'US' ) {
            		// USA! USA! USA!
            		array_unshift($out, array('code'=> $cc, 'label' => $label));
            		continue;
            	}

            	$out[] = array('code'=> $cc, 'label' => $label);
            }

			return new Response($out);
        }

        return new Response(array());

	}

	public function listCountries() {
        $file = \MAX_PATH
        	.$GLOBALS['_MAX']['CONF']['pluginPaths']['plugins']
        	.'/deliveryLimitations/Geo/City.res.inc.php';

        if( is_readable($file) ) {
			include $file;

            $out = array();
            foreach( $res as $cc => $label ) {
            	if( $cc == 'US' ) {
            		// USA! USA! USA!
            		array_unshift($out, array('code'=> $cc, 'label' => $label));
            		continue;
            	}

            	$out[] = array('code'=> $cc, 'label' => $label);
            }

			return new Response($out);
        }

        return new Response(array());

	}

	public function listDMAs() {
        $file = \MAX_PATH
        	.$GLOBALS['_MAX']['CONF']['pluginPaths']['plugins']
        	.'/deliveryLimitations/Geo/Dma.res.inc.php';

        if( is_readable($file) ) {
			include $file;

            $out = array();
            foreach( $res as $cc => $label ) {
            	$out[] = array('code'=> $cc, 'label' => $label);
            }

			return new Response($out);
        }

        return new Response(array());

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

	public function translate($s) {
		return $s;
	}
}


?>

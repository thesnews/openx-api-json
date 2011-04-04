<?php
date_default_timezone_set('America/New_York');

require_once '../../../init.php';
require_once LIB_PATH.'/Plugin/Component.php';
require_once MAX_PATH . '/lib/OA/BaseObjectWithErrors.php';

// Init required files
require_once MAX_PATH . '/lib/max/language/Loader.php';
require_once MAX_PATH . '/lib/max/other/lib-io.inc.php';
require_once MAX_PATH . '/lib/max/other/lib-userlog.inc.php';

require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Preferences.php';
require_once MAX_PATH . '/lib/OA/Auth.php';

require_once MAX_PATH . '/www/admin/lib-gui.inc.php';

require_once OX_PATH.'/plugins/jsonAPI/controller.class.php';

$fc = new \jsonAPI\controller($_SERVER['REQUEST_URI']);

try {
	$controller = $fc->getController();
	
	if( $controller ) {
		$payload = $controller->callAction();
	} else {
		$payload = new \jsonAPI\response;
		$payload->setError(true)
			->setMessage('No or invalid request');
	}
	
	echo $payload;
} catch( \jsonAPI\exception $e ) {
	echo new \jsonAPI\response($e->getMessage());
}
?>
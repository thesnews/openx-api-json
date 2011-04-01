<?php
namespace jsonAPI\controller;
use jsonAPI\response as Response;

class main extends \jsonAPI\controller {

	public function __construct($a) {
		$this->action = $a;
	}
	
	public function main() {
		return new Response(array(
			'version' => \jsonAPI\VERSION,
			'buid' => \jsonAPI\BUILDID,
			'servertime' => time()
		));
	}
	
	public function authenticate() {
		$un = $this->filterString($_POST['username']);
		$pw = $this->filterString($_POST['password']);

		if( $this->internalAuth($un, $pw) ) {
			return new Response($_COOKIE['sessionID']);
		}
		
		return $this->respondWithError('Invalid authentication data');
	}
	
	public function deauthenticate() {

		if( !$this->verifySession() ) {
			return $this->respondWithInvalidSession();
		}

		\phpAds_SessionDataDestroy();
		unset($GLOBALS['session']);
		
		return new Response('Logged out');
	}
	
	public function testlog() {
		
		if( $this->verifySession() ) {
			return new Response('yep');
		}
		
		return $this->respondWithInvalidSession();
	}
	
	
	// this is the _internalLogin method from the LogonServiceImpl file
    private function internalAuth($username, $password) {

        // Require the default language file.
        include_once MAX_PATH.'/lib/max/language/Loader.php';
        // Load the required language file.
        \Language_Loader::load('default');

        $oPlugin = \OA_Auth::staticGetAuthPlugin();

        $doUser = $oPlugin->checkPassword($username, $password);
        if ($doUser) {
            \phpAds_SessionDataRegister(\OA_Auth::getSessionData($doUser));
            return true;
        } else {
            return false;
        }
    }

}


?>
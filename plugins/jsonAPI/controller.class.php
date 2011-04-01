<?php
namespace jsonAPI;

const VERSION = '1.0';
const BUILDID = '20110401';

class controller {
	
	private $path = false;
	private $controller = 'main';

	protected $action = 'main';
	
	private $parsedRequest = array(
		'main', 'main'
	);
	
	protected $sessionID = false;
	
	public function __construct($uri) {
		$this->path = OX_PATH.'/plugins/jsonAPI';

		if( substr($uri, strlen($uri)-1) == '/' ) {
			$uri = substr($uri, 0, strlen($uri)-1);
		}
		
		$parts = explode('api/json/index.php/', $uri, 2);
		if( !isset($parts[1]) ) {
			return;
		}
		
		if( substr($parts[1], strlen($parts[1])-1) == '/' ) {
			$parts[1] = substr($parts[1], 0, strlen($parts[1])-1);
		}
		$parts = explode('/', $parts[1]);
		
		$parts = array_filter($parts, function($item) {
			return \jsonAPI\controller::filterString($item);
		});
		
		$this->parsedRequest = $parts;
	}
	
	public function getController() {
		$c = $this->parsedRequest[0];
		$a = 'main';
		if( $this->parsedRequest[1] ) {
			$a = $this->parsedRequest[1];
		}
		
		$file = sprintf('%s/controller/%s.class.php', $this->path, $c);

		if( !file_exists($file) ) {
			return false;
		}
		
		require_once $file;
		$cls = sprintf('\\jsonAPI\\controller\\%s', $c);

		$o = new $cls($a);
		return $o;			
		
		return true;
	}
	
	public function callAction() {
		if( method_exists($this, $this->action) ) {
			return call_user_func(array($this, $this->action));
		}
		
		return call_user_func(array($this, 'main'));
	}
	
	protected function verifySession($s=false) {
		if( !$s ) {
			$s = $_POST['sessionID'];
		}
		
		if( strlen($s) > 32 ) {
			return false;
		}
		
		$_COOKIE['sessionID'] = $s;
		
		\OA_Preferences::loadPreferences();
		unset($GLOBALS['session']);
		\phpAds_SessionDataFetch();
		
		if( \OA_Auth::isLoggedIn() ) {
			$this->sessionID = $s;
		
			return true;
		}
		
		return false;
	}
	
	protected function respondWithError($err) {
		$r = new Response;
		$r->setError($err)->setMessage($err);
		
		return $r;
	}
	
	protected function respondWithInvalidSession() {
		return $this->respondWithError('Invalid or malformed request');
	}
	
	// filter methods
	
	public function filterString($string) {
		$string = stripslashes(urldecode($string));
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
	
	}

	public function filterNum($num, $extra = false) {
		if( is_null($num) ) {
			return $extra;
		}
		
		if( is_numeric($num) ) {
			return $num;
		}
		
		return preg_replace('/[^0-9\.]/', '', $num);
	}
	
	public function filterBool($num) {
		if( $num === 1 || $num === true || strtolower($num) === 'yes' ||
			$num === 'true' || $num === '1' ) {
			return true;
		}

		if( $num === 0 || $num === false || strtolower($num) === 'no' ||
			$num === 'false' || $num === '0' ) {
			return false;
		}

		return null;
	}
	
	public function filterAlpha($string, $extra=false) {
		if( is_null($string) ) {
			return $extra;
		}
		
		return preg_replace(
			'/[^a-zA-Z_]/',
			'',
			$string
		);
	}
	
	public static function filterAlnum($string, $extra = false) {
		if( is_null($string) ) {
			return $extra;
		}

		return preg_replace(
			'/[^a-zA-Z0-9_\-\.]/',
			'',
			$string
		);
	}
	
}

class response {

	private $payload = array(
		'isError' => false,
		'message' => false,
		'data' => array()
	);
	
	public function __construct($data=array()) {
		$this->setData($data);
	}
	
	public function setError($b) {
		if( !is_bool($b) ) {
			return $this;
		}
		$this->payload['isError'] = $b;
		
		return $this;
	}
	
	public function setMessage($m) {
		if( !is_string($m) ) {
			return $this;
		}

		$this->payload['message'] = $m;
		
		return $this;
	}
	
	public function setData($data) {
		$this->payload['data'] = $data;
		
		return $this;
	}
	
	public function __toString() {
		return json_encode($this->payload);
	}

}

class registry {

	private static $path = false;
	private static $dbh = false;

	public static function getHandle() {
		if( self::$dbh ) {
			return self::$dbh;
		}
		
		$p = MAX_PATH.'/var/plugins/jsonAPI';

		if( !is_dir($p) ) {
			mkdir($p);
		}
		
		self::$dbh = new PDO(sprintf('sqlite:%s/store.sqlite', $p), null, null);
		
		$q = 'describe jsonAPI_registry';
		$stmt = self::$dbh->prepare($q);
		$stmt->execute();
		
		if( !$stmt->fetch() ) {
			$q = 'CREATE TABLE jsonAPI_registry (
				uid int(11) NOT NULL auto_increment,
				name varchar(255) default NULL,
				data text,
				modified int(11) default NULL,
				PRIMARY KEY (uid),
				UNIQUE KEY name (name)
			)';
			self::$dbh->exec($q);
		}

		return self::$dbh;
	}
	
	/*
	 Method: get
	  Get a registry value. Second optional parameter contains the last
	  modified timestamp of the value.
	 
	 Access:
	  public
	 
	 Parameters:
	  k - _string_ registry key
	 
	 Returns:
	  _string_
	*/
	public static function get($k) {
		
		$q = 'select data, modified from jsonAPI_registry '.
			'where name = :nm limit 1';
		$stmt = self::getHandle()->prepare($q);
		$stmt->bindParam(':nm', $k);
		
		$stmt->execute(null, false, false);
		
		$row = $stmt->fetch();
		
		return $row['data'];
		
	}

	/*
	 Method: set
	  Set a registry value
	 
	 Access:
	  public
	 
	 Parameters:
	  k - _string_ registry key
	  v - _string_ value
	 
	 Returns:
	  _mixed_ value
	*/
	public static function set($k, $v) {

		if( self::get($k) ) {
			$q = 'update jsonAPI_registry set data = :dta, modified = :mod'.
				' where name = :nm';
		} else {
			$q = 'insert into jsonAPI_registry set data = :dta, '.
				'modified = :mod, name = :nm';
		}
		
		
		$stmt = self::getHandle()->prepare($q);
		$stmt->bindParam(':dta', $v);
		$stmt->bindParam(':nm', $k);
		$stmt->bindParam(':mod', time());
		$stmt->execute();
		
		return $v;
		
	}
	
	/*
	 Method: remove
	  Remove a registry value
	 
	 Access:
	  public
	 
	 Parameters:
	  k - _string_ registry key
	 
	 Returns:
	  _void_
	*/
	public static function remove($k) {

		$q = 'delete from jsonAPI_registry where name = :nm';
		
		$stmt = self::getHandle()->prepare($q);
		$stmt->bindParam(':nm', $k);
		$stmt->execute();
		
		return $v;
	}

}

?>
<?php
namespace jsonAPI\model;

class channel extends \jsonAPI\model {

	public function __init() {
        $this->stack['channelId'] = $this->stack['channelid'];
	}

}

?>
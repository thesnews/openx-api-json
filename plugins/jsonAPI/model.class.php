<?php

namespace jsonAPI;

class model {

	protected $stack = array();

	public function __construct($data) {
		$this->stack = $data;
		
		$this->__init();
	}
	
	
	public function toArray() {
		return $this->stack;
	}

	public function __toString() {
		return json_encode($this->stack);
	}

	public function __get($k) {
		if( isset($this->stack[$k]) ) {
			return $this->stack[$k];
		}
		
		return null;
	}

}

?>
<?php
namespace jsonAPI\model;

class client extends \jsonAPI\model {

	public function __init() {

		$this->stack['contact'] = $this->stack['_contact'];
		$this->stack['email'] = $this->stack['_email'];

        $this->stack['advertiserName'] = $this->stack['clientname'];
        $this->stack['agencyName'] = $this->stack['name'];
        $this->stack['contactName'] = $this->stack['contact'];
        $this->stack['emailAddress'] = $this->stack['email'];
        $this->stack['agencyId'] = $this->stack['agencyid'];
        $this->stack['advertiserId'] = $this->stack['clientid'];
        $this->stack['accountId'] = $this->stack['account_id'];

	}

}
?>
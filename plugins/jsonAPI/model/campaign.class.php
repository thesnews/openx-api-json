<?php
namespace jsonAPI\model;

class campaign extends \jsonAPI\model {
	
	public function __init() {
		$desc = array(
			'start' => false,
			'end' => false
		);

		if( $this->stack['activate_time'] ) {
			$tz = new \DateTimeZone(date_default_timezone_get());
			$t = new \DateTime($this->stack['activate_time']);
			$t->setTimezone($tz);
			
			$desc['start'] = $t->format('c');
		}
		
		if( $this->stack['expire_time'] ) {
			$tz = new \DateTimeZone(date_default_timezone_get());
			$t = new \DateTime($this->stack['expire_time']);
			$t->setTimezone($tz);
			
			$desc['end'] = $t->format('c');
		}

		if( $this->stack['views'] >= 1 ) {
			$this->stack['impressions'] = $this->stack['views'];
		}

		$k = \OX_Util_Utils::getCampaignTypeTranslationKey(
			$this->stack['priority']
		);
		
		if( $GLOBALS[$k] ) {
			$desc['type'] = $GLOBALS[$k];
		}
		
		$k = \OX_Util_Utils::getCampaignStatusTranslationKey(
			$this->stack['status']
		);

		if( $GLOBALS[$k] ) {
			$desc['status'] = $GLOBALS[$k];
		}

		if( $this->stack['priority'] == -1 ) {
			$desc['priority'] = 'Exclusive';
		} elseif( $row['priority'] == -2 ) {
			$desc['priority'] = 'ECPM';
		} elseif( $row['priority'] == 0 ) {
			$desc['priority'] = 'Low';
		} else {
			$desc['priority'] = 'High ('.$this->stack['priority'].')';
		}

		switch( $this->stack['revenue_type'] ) {
			case \MAX_FINANCE_CPM:
				$desc['revenue_type'] = 'CPM (Impressions)';
				break;
			case \MAX_FINANCE_CPC:
				$desc['revenue_type'] = 'CPC (Clicks)';
				break;
			case \MAX_FINANCE_CPA:
				$desc['revenue_type'] = 'CPA (Activity)';
				break;
			case \MAX_FINANCE_MT:
			default:
				$desc['revenue_type'] = 'Tenancy';
				break;
		}
		
		$this->stack['description'] = $desc;	
	}
}
?>
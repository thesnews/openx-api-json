<?php
namespace jsonAPI\model;
require_once MAX_PATH.'/lib/OA/Dll/Campaign.php';

class campaign extends \jsonAPI\model {
	
	public function __init() {
		$desc = array(
			'start' => false,
			'end' => false
		);

        $this->stack['campaignId'] = $this->stack['campaignid'];
        $this->stack['campaignName'] = $this->stack['campaignname'];
        $this->stack['advertiserId'] = $this->stack['clientid'];
        $this->stack['startDate'] = $this->stack['activate_time'];
        $this->stack['endDate'] = $this->stack['expire_time'];
        $this->stack['impressions'] = $this->stack['views'];
        $this->stack['targetImpressions'] = $this->stack['target_impression'];
        $this->stack['targetClicks'] = $this->stack['target_click'];
        $this->stack['targetConversions']  = $this->stack['target_conversion'];
        $this->stack['capping'] = $this->stack['capping'];
        $this->stack['sessionCapping'] = $this->stack['session_capping'];
        $this->stack['block'] = $this->stack['block'];
        $this->stack['viewWindow'] = $this->stack['viewwindow'];
        $this->stack['clickWindow'] = $this->stack['clickwindow'];

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
		
		if( $this->stack['description_comments'] ) {
			$desc['comments'] = $this->stack['description_comments'];
		}

		if( $this->stack['impressions'] > 0 || $this->stack['clicks'] > 0
			|| $this->stack['conversions'] > 0
		) {
        	$tmp = \OA_Dal::factoryDAL('data_intermediate_ad');
	        $dta = $tmp->getDeliveredByCampaign($this->stack['campaignid']);
	        $dta = $dta->toArray();

			if( $this->stack['impressions'] != -1 ) {
	            $this->stack['impressionsRemaining'] = (
	            	$this->stack['impressions'] - $dta['impressions_delivered']
	            );
			} else {
            	$this->stack['impressionsRemaining'] = '';
			}
			
			if( $this->stack['clicks'] != -1 ) {
				$this->stack['clicksRemaining'] = (
					$this->stack['clicks'] - $dta['clicks_delivered']
				);
			} else {
				$this->stack['clicksRemaining'] = '';
			}


			if( $this->stack['conversions'] != -1 ) {
				$this->stack['conversionsRemaining'] = (
					$this->stack['conversions'] - $dta['conversions_delivered']
				);
			} else {
				$this->stack['conversionsRemaining'] = '';
        	}
        	
			$this->stack['impressions_delivered'] =
				$dta['impressions_delivered'];
			$this->stack['clicks_delivered'] = 
				$dta['clicks_delivered'];
			$this->stack['conversions_delivered'] =
				$dta['conversions_delivered'];
	    }

		if( $this->stack['status'] ) {
			$desc['reasons'] = $this->getInactiveString();
		}

		$this->stack['active'] = ($this->stack['status']) ? false : true;

		$campDll = new \OA_Dll_Campaign;
		$stats = array();
		
		$desc['statistics'] = array();
		
		$campDll->getCampaignPublisherStatistics(
			$this->stack['campaignid'], 
			new \Date($this->stack['activate_time']), new \Date, true, &$stats
		);
		if( $stats ) {
			$stats->find();
			$stats->fetch();
			
			$desc['statistics'] = $stats->toArray();
		}
		
		$this->stack['description'] = $desc;	
	}
	
	// adapted from www/admin/campaign-edit.php:1108
	public function getInactiveString() {
	
		$prefs = $GLOBALS['_MAX']['PREF'];
		$raisins = array(); // we bring the funny
	
		if( ($this->impressions != -1) && ($this->impressionsRemaining <= 0) ) {
			$raisins[] = $GLOBALS['strNoMoreImpressions'];
		}
		
		if( ($this->clicks != -1) && ($this->clicksRemainging <= 0) ) {
			$raisins[] = $GLOBALS['strNoMoreClicks'];
		}
		
		if( ($this->conversions != -1) & ($this->conversionsRemaining <= 0) ) {
			$raisins[] = $GLOBALS['strNoMoreConversions'];
		}
		
		if( strtotime($this->activate_time) > time() ) {
			$raisins[] = $GLOBALS['strBeforeActivate'];
		}
		
		if( strtotime($this->expire_time) < time() ) {
			$raisins[] = $GLOBALS['strAfterExpire'];
		}
	
		if( ($this->priority == 0 || $this->priority == -1)
			&& $this->weight == 0
		) {
			$raisins[] = $GLOBALS['strWeightIsNull'];
		}
		
		if( ($this->priority > 0) 
			&& ($this->target_value == 0 || $this->target_value == '-') 
			&& ($this->impressions == -1)
			&& ($this->clicks == -1)
			&& ($this->conversions == -1)
		) {
			$raisins[] = $GLOBALS['strTargetIsNull'];
		}
		
		return $raisins;
	}
	
}
?>
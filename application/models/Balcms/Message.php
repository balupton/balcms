<?php

/**
 * Balcms_Message
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
class Balcms_Message extends Base_Balcms_Message
{
	
	/**
	 * Fetch Message Query
	 * @return string
	 */
	static public function fetchQuery ( $Query = null ) {
		if ( !(is_object($Query) && $Query instanceOf Doctrine_Query) ) {
			$Query = Doctrine_Query::create()
				->from('Message m');
		}
		return $Query->orderBy('m.send_on DESC');
	}

	/**
	 * Set Message Code: content-subscription
	 * @return string
	 */
	protected function _templateContentSubscription ( &$params, &$data ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$View = Bal_App::getView(false);
		$Message = $this;
		
		# Prepare Urls
		$rootUrl = $View->app()->getRootUrl();
		$baseUrl = $View->app()->getBaseUrl(true);
		
		# --------------------------
		
		# Prepare
		$Content = $this->Content;
		
		# Prepare URL
		$contentUrl = $rootUrl.$View->url()->content($Content)->toString();
		$params['Content_url'] = $contentUrl;
		
		# Send On
		$send_on = strtotime($Content->published_at);
		$this->send_on = doctrine_timestamp($send_on);
		
		# Adjust Dates
		$params['Message']['Content']['published_at'] = $Locale->date($params['Message']['Content']['published_at']);
		
		
		# --------------------------
		
		return true;
	}
	
}
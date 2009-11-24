<?php
class Bal_View_Helper_Bal extends Zend_View_Helper_Abstract {

	/**
	 * The App Plugin
	 * @var Bal_Controller_Plugin_App
	 */
	protected $_App = null;
	
	/**
	 * The current User
	 * @var Doctrine_Record
	 */
	protected $_User = null;
	
	
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;
	
	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $view) {
		# Apply
		$this->_App = Zend_Controller_Front::getInstance()->getPlugin('Bal_Controller_Plugin_App');
		
		# Set
		$this->view = $view;
		
		# Chain
		return $this;
	}
	
	/**
	 * Returns @see Bal_Controller_Plugin_App
	 */
	public function getApp(){
		# Done
		return $this->_App;
	}
	
	/**
	 * Self reference
	 */
	public function bal ( ) {
		# Chain
		return $this;
	}

	/**
	 * Get a base_url for an area
	 * @see getThemeUrl
	 * @param string $area
	 * @param bool $root_url
	 * @return string
	 */
	public function getBaseUrl ( $area = null, $root_url = false ) {
		$theme = null;
		switch ( $area ) {
			case 'front':
				$theme = $this->getConfig('bal.themes.front');
				break;
			case 'back':
				$theme = $this->getConfig('bal.themes.back');
				break;
			default:
				break;
		}
		return $this->getThemeUrl($theme, $root_url);
	}
	
	/**
	 * Get a base_url for a theme
	 * @param string $theme
	 * @param bool $root_url
	 * @return string
	 */
	public function getThemeUrl ( $theme = null, $root_url = false ) {
		$baseUrl = rtrim(Zend_Controller_Front::getInstance()->getBaseUrl(),'/');
		$prefix = $suffix = '';
		if ( !empty($theme) ) {
			$themes_url = $this->getConfig('bal.themes.themes_url');
			$suffix = $themes_url.'/'.$theme;
		}
		if ( $root_url && defined('ROOT_URL') ) {
			$prefix = ROOT_URL;
		}
		return $prefix.$baseUrl.$suffix;
	}
	
	/**
	 * Get the application Config or a specific config variable
	 * @param string $confs [optional]
	 * @return mixed
	 */
	public function getConfig ( $confs = null ) {
		return $this->getApp()->getConfig($confs);
	}
	
	/**
     * Get the Current User
     * @return array
	 */
	public function getUser ( ) {
		# Prepare
		$user = array();
		
		# Load
		if ( $this->_User === null ) {
			$this->_User = $this->getApp()->getUser();
		}
		
		# Check
		if ( $this->_User ) {
			$user = $this->_User->toArray();
		} else {
			$user = false;
		}
		return $user;
	}
	
	/**
     * Check if the Permission exists for the current User
     * @return bool
	 */
	public function hasPermission ( $permission ) {
		return $this->getApp()->hasPermission($permission);
	}
}
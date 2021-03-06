<?php
require_once 'Zend/Controller/Action.php';
class Balcms_FrontController extends Zend_Controller_Action {

	# ========================
	# CONSTRUCTORS
	

	public function init ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->prepareLog();
		
		# Ajaxy
		$this->getHelper('Ajaxy'); // initialise
		
		# Layout
		$App->setArea('front');
		
		# Login
		$App->setOption('logged_in_forward', array('index', 'back'));
		
		# Authenticate
		$App->authenticate(false, false);
		
		# Navigation
		$this->applyNavigation();
		
		# Done
		return true;
	}

	public function applyNavigation ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$App->applyNavigation();
		
		# --------------------------
		
		# Criteria
		$criteria = array(
			'fetch' => 'simplelist',
			'where' => array(
				'status' => 'published'
			),
			'Root' => true,
			'hydrationMode' => Doctrine::HYDRATE_ARRAY
		);
		
		# Fetch
		$ContentListArray = $App->fetchRecords('Content',$criteria);
		
		# Convert
		foreach ( $ContentListArray as &$Content ) {
			$Content = Content::toNavItem($Content);
		}
		
		# Navigation
		$NavTree = array_tree_round($ContentListArray, 'id', 'parent_id', 'level', 'position', 'pages', array('id', 'route', 'order', 'uri', 'label', 'title', 'children', 'map', 'params')); // turn into tree from a flat list
		$App->applyNavigationMenu('front.menu', new Zend_Navigation($NavTree));
		
		# --------------------------
		
		# Done
		return true;
	}
	
	public function activateNavigationContentItem ( $Content ) { 
		# Prepare
		$App = $this->getHelper('App');
		$result = false; 
		
		# --------------------------
		
		# Handle
		while ( $result === false ) { 
			$code = $Content->code; 
			$result = $App->activateNavigationItem('front.menu', 'content-'.$Content->code, false, false);
			if ( empty($Content->parent_id) ) 
				break; 
			$Content = $Content->Parent; 
		} 
		
		# --------------------------
		
		# Done
		return $result;
	} 
	
	# ========================
	# INDEX
	

	public function indexAction ( ) {
		# Home Page
		$ContentArray = Doctrine::getTable('Content')->createQuery()->where('status = ?', 'published')->orderBy('position ASC, id ASC')->setHydrationMode(Doctrine::HYDRATE_ARRAY)->fetchOne();
		$content = $ContentArray['id'];
		
		# Popular Tags (as we are the home page)
		//$tags = Doctrine::getTable('Content')->getPopularTagsArray();
		//$tags = array_keys($tags);
		//$keywords = implode(', ', $tags);
		

		# Forward
		return $this->_forward('content-page', null, null, array('id' => $content));
	}
	

	# ========================
	# CONTENT
	

	public function searchAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# --------------------------
		
		# Search
		$search = $App->fetchSearch();
		if ( $search['oldCode'] !== $search['code'] ) {
			return $this->getHelper('redirector')->gotoRoute(array('action'=>'search','code'=>$search['code']), 'front', true);
		}
		
		# Extract
		$searchQuery = delve($search,'query');
		
		# Check
		if ( !$searchQuery ) {
			return $this->_forward('index');
		}
		
		# Criteria
		$criteria = array(
			'fetch' => 'list',
			'status' => 'published',
			'hydrationMode' => Doctrine::HYDRATE_RECORD
		);
		
		# Criteria: Search
		if ( $searchQuery ) {
			$criteria['search'] = $searchQuery;
		}
		
		# Fetch
		$ContentList = $App->fetchRecords('Content',$criteria);
		
		# --------------------------
		
		# Apply
		$this->view->search = $search;
		$this->view->searchCode = $search['code'];
		$this->view->ContentList = $ContentList;
		$this->view->headTitle()->append('Search');
		$this->view->headTitle()->append($search['query']);
		
		# Render
		$this->getHelper('Ajaxy')->render(array(
			'template' => 'content/search',
			'controller' => 'page',
			'routes' => array(
				'page-search-:searchCode' => array(
					'template' => 'content/search',
					'controller' => 'page'
				)
			),
			'data' => 'search, searchCode'
		));
		
		# Return true
		return true;
	}

	public function unsubscribeAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		$Response = $this->getResponse();
		
		# --------------------------
		
		# Fetch
		$email = fetch_param('subscribe.email', $Request->getParam('email'));
		
		# Handle
		if ( empty($email) ) {
			# Apply
			$this->view->headTitle()->append('Unsubscribe');
			
			# Render
			return $this->render('subscription/unsubscribe');
		}
		
		# Subscribe
		$Subscriber = Doctrine::getTable('User')->findOneByEmail($email);
		if ( count($Subscriber) && $Subscriber->exists() ) {
			$Subscriber->removeAllTags();
			$Subscriber->save();
			// $Subscriber->delete(); // no longer delete the subscribers as they are now users
		}
		
		# --------------------------
		
		# Done
		return $this->_forward('index');
	}

	public function subscribeAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		$Response = $this->getResponse();
		$Log = Bal_App::getLog();
		
		# --------------------------
		
		# Fetch
		$email = fetch_param('subscribe.email', $Request->getParam('email'));
		
		# Subscribe
		try {
			$Subscriber = new User();
			$Subscriber->email = $email;
			$Subscriber->setTags('newsletter');
			$Subscriber->save();
			# Log
			$log_details = array(
				'Subscriber' => $Subscriber->toArray(),
			);
			$Log->log(array('log-subscriber-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			# Log the Event
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# --------------------------
		
		# Done
		return $this->_forward('index');
	}

	public function userAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		
		# --------------------------
		
		# Fetch
		$User = Bal_Doctrine_Core::fetchItem('User');
		if ( !delve($User,'id') ) {
			throw new Zend_Controller_Action_Exception('This user does not exist',404);
		}
		
		# Meta
		$meta = preg_replace('/\s\s+/', ' ', strip_tags($User->description));
		
		# --------------------------
		
		# Apply
		$this->view->User = $User;
		$this->view->headTitle()->append('User');
		$this->view->headTitle()->append($User->displayname);
		$this->view->headMeta()->appendName('description', $meta);
		
		# Render
		$this->view->user = $User->id;
		$this->getHelper('Ajaxy')->render(array(
			'template' => 'user/user',
			'controller' => 'page',
			'routes' => array(
				'page-user-:user' => array(
					'template' => 'user/user',
					'controller' => 'page'
				)
			),
			'data' => 'user'
		));
		
		# Return true
		return true;
	}
	
	public function contentPageAction ( ) {
		# Prepare
		$App = $this->getHelper('App');
		$Request = $this->getRequest();
		$Response = $this->getResponse();
		
		# --------------------------
		
		# Fetch
		$content_id = $this->_getParam('id');
		$Content = Doctrine::getTable('Content')->find($content_id);
		if ( $Content->status !== 'published' ) {
			throw new Zend_Controller_Action_Exception('This content does not exist',404);
		}
		
		# Keywords
		$keywords = array($Content->tags);
		
		# Ancestors
		$Content_Ancestors = $this->view->content()->getAncestors($Content);
		foreach ( $Content_Ancestors as $Ancestor ) {
			$keywords[] = delve($Ancestor,'tags');
			$this->view->headTitle()->append(delve($Ancestor,'title'));
		}
		
		# Children
		$Content_Children = $this->view->content()->getChildren($Content);
		
		# Keywords
		$keywordstr = prepare_csv_str($keywords);
		
		# Meta
		$meta = preg_replace('/\s\s+/', ' ', strip_tags($Content->description_rendered));
		
		# --------------------------
		
		# Apply
		$this->view->Content = $Content;
		$this->view->Content_Ancestors = $Content_Ancestors;
		$this->view->Content_Children = $Content_Children;
		$this->view->headTitle()->append($Content->title);
		$this->view->headMeta()->appendName('description', $meta);
		$this->view->headMeta()->appendName('keywords', $keywordstr);
		$this->activateNavigationContentItem($Content);
		
		# Render
		$this->view->content = $content_id;
		$this->getHelper('Ajaxy')->render(array(
			'template' => 'content/content',
			'controller' => 'page',
			'routes' => array(
				'page-content-:content' => array(
					'template' => 'content/content',
					'controller' => 'page'
				)
			),
			'data' => 'content'
		));
		
		
		# Return true
		return true;
	}
	

}

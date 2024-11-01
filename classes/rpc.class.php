<?php
class WH_Integration_RPC
{

	var $config;
	var $options_obj;
	var $extend_obj;
	
	

	public function __construct()
	{
		global $wh_integration_config;
		$this->config = $wh_integration_config;
		$this->options_obj = WH_Integration::get_instance('WH_Integration_Options');
		$this->extend_obj = WH_Integration::get_instance('WH_Integration_Extend');
	}
	
	
	function add_hooks(){
		
		WH_Integration_Helper::log('add hooks loaded in xml rpc');

		add_filter( 'xmlrpc_methods', array( &$this, 'xmlrpc_methods'));
		WH_Integration_Helper::log('end hooks loaded in xml rpc');
	}
	//END ADD FILTERS
		

	function xmlrpc_methods( $methods ){
	
		//GET PLUGIN VERSION
		$methods['wh.version'] = array(&$this,'version');	
		
		//THEMES
		$methods['wh.getNetworkThemes'] = array(&$this,'get_network_themes');
		$methods['wh.updateNetworkThemes'] = array(&$this,'update_network_themes');
		
		//PRIVACY SETTINGS
		$methods['wh.getNetworkPrivacySettings'] = array(&$this,'get_network_privacy_defaults');
		$methods['wh.updateNetworkPrivacySettings'] =  array(&$this,'update_network_privacy_defaults');

		$methods['wh.getSitePrivacySettings'] = array(&$this,'get_site_privacy_settings'); 
		$methods['wh.updateSitePrivacySettings'] =  array(&$this,'update_site_privacy_settings'); 
		
		//USER METHODS
		$methods['wh.isValidUser'] = array(&$this,'is_super_admin');

		if( defined('SCHOOLPRESS_SP_INSTALLED') && SCHOOLPRESS_SP_INSTALLED == true ){
			$methods['wh.getSitesForUser'] = array(&$this,'get_sites_for_user_by_podium_id_with_sp');
		} else {
			$methods['wh.getSitesForUser'] = array(&$this,'get_sites_for_user_by_podium_id');
		}
		
		$methods['wh.addUpdateUsers'] =  array(&$this,'add_update_users');
		$methods['wh.getUsersForSite'] = array(&$this,'get_users_for_site');
		
		//Site Methods
		$methods['wh.isURLTaken'] = array(&$this,'check_site_slug');
		$methods['wh.getSite'] = array(&$this,'get_site_info');
		$methods['wh.createSite'] = array(&$this,'create_site');
		$methods['wh.updateSite'] = array(&$this,'update_site_info');


		$methods['wh.getAllSites'] = array(&$this,'get_all_sites_with_sp');
		
		return $methods;
	}
	//END RPC METHODS
	
	
	function version(){	
		return  $this->config['plugin_version'];
	}
	//END VERSION
	
	function get_all_sites_with_sp( $args ){
		$this->escape( $args );
		global $wpdb;
		
		$data = array();

		$role ="";
		$privacy = get_blog_option(1,'blog_public');
		$details = get_blog_details( 1 );
		$data[]  =  array(
				   			'isAdmin' => $isNetworkAdmin,
				   			'blogname' => $details->blogname,
							'domain' => $details->domain,
							'path' => $details->path,
							'siteid' => intval($details->site_id),
							'siteurl' => $details->siteurl,
							'blogid'=> intval($details->blog_id),
							'lastmod'=> $details->last_updated,
							'postcount' => (int) $details->post_count,
							'role' => $role,
							'privacy' => $privacy,
							'deleted' => $details->deleted
							
		    			);

		$lastid = $wpdb->get_var( "SELECT blog_id FROM $wpdb->blogs order by blog_id DESC LIMIT 1 " );
		$listid++;
		$blogs = $wpdb->get_results("CALL blogs(0,". $lastid .",'". str_replace("_", "", $wpdb->prefix) ."');",ARRAY_A);
		//set_transient( '_schoolpress_bloglist', $blogs , 60*60 );
		//
		




		foreach ($blogs as $user_blog) {
			$data[]  =  array(
				'isAdmin' => false,
				'blogname' => $user_blog['blog_name'],
				'domain' => $user_blog['domain'],
				'path' => $user_blog['path'],
				'siteid' => intval($user_blog['site_id']),
				'siteurl' =>  $user_blog['site_url'],
				'blogid'=> intval($user_blog['blog_id']),
				'lastmod'=> $user_blog['last_updated'],
				'postcount' => (int) $user_blog['post_count'],
				'role' => $role,
				'privacy' => $user_blog['public'],
				'deleted' => intval($user_blog['deleted'])
				
		    );
		}
		return $data;
	}


	
	/**
	*
	*	USER METHODS
	*
	*/
	
	function is_super_admin( $args ){
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		$e = $args[2];
		if ( !$user = $this->wh_login($username, $password) ){
			$valid = false;			
		}else{
			$valid = true;
		}
		return $valid;
	}
	
	/**
	*
	*	GET SITES BY PODIUM ID
	*
	*/
	


	function get_sites_for_user_by_podium_id( $args ){

		//WH_Integration_Helper::log('test for the logging function...inside get sites rpc method');

		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
				
		$podium_id = $args[2];
		$sortdata = $args[3];
		$is_manager = $args[4];

		$data = array();
		if( empty( $sortdata ) )
			$sortdata = array();
	
		$q_defaults =  array(
			'orderby' => 'blogname',
			'order' => 'ASC',
			'limit' => 10000
		);
				
		// Merge old and new fields with new fields overwriting old ones.
		$sortdata = array_merge($q_defaults, $sortdata);
		
		$user_id = $this->extend_obj->get_user_by_podium_id( $podium_id );
		
		$user = new WP_User($user_id);
		
		
		if( WH_Integration_Helper::bool_val( $is_manager ) ){
		 	$this->extend_obj->update_wh_super_admin($user_id, true);
		 	$isNetworkAdmin = true; //wh_super_admin( $user_id );
		} else {
		 	$this->extend_obj->update_wh_super_admin($user_id, false);
		 	//$isNetworkAdmin = false;
		 	
		 	$isNetworkAdmin = $this->extend_obj->wh_super_admin( $user_id );
		}
			
		$blogs = $this->extend_obj->get_blog_list(0,'all');
	//	return $blogs;
		$limit = 0;	
		foreach ($blogs as $user_blog) {
			
			//return $user_blog->blog_id;
			$details = get_blog_details( $user_blog['blog_id'] );
			
			/***details
			{
			    archived = 0;
			    "blog_id" = 45;
			    blogname = "Dave&#039;s blog";
			    deleted = 0;
			    domain = "testschool.whipplehillcloud.com";
			    "lang_id" = 0;
			    "last_updated" = "2010-09-10 15:48:03";
			    mature = 0;
			    path = "/DBtest/";
			    "post_count" = "";
			    public = "-1";
			    registered = "2010-09-10 11:48:03";
			    "site_id" = 1;
			    siteurl = "http://testschool.whipplehillcloud.com/DBtest";
			    spam = 0;
			}
			*
			*/
			
				if ($details->deleted == 0){
					//return $details;
					$role = $this->extend_obj->get_user_role_for_blog( $user_id , $user_blog['blog_id'] );
					$isAdmin = false;
					if($isNetworkAdmin){
						$role = 'administrator';
					}
					$privacy = get_blog_option($user_blog['blog_id'],'blog_public');
					if($role !="" || $privacy > -2 ) 
					{
						if($privacy == -3){
							if($role == 'administrator'){
								$limit++;
					   			$data[]  =  array(
						   			'isAdmin' => $isNetworkAdmin,
						   			'blogname' => $details->blogname,
									'domain' => $details->domain,
									'path' => $details->path,
									'siteid' => intval($user_blog['site_id']),
									'siteurl' => $details->siteurl,
									'blogid'=> intval($user_blog['blog_id']),
									'lastmod'=> $details->last_updated,
									'postcount' => (int) $details->post_count,
									'role' => $role,
									'privacy' => $privacy,
									'deleted' => (int) $details->deleted
				    			);

							}
						} else {
							$limit++;
				   			$data[]  =  array(
						   			'isAdmin' => $isNetworkAdmin,
						   			'blogname' => $details->blogname,
									'domain' => $details->domain,
									'path' => $details->path,
									'siteid' => intval($user_blog['site_id']),
									'siteurl' => $details->siteurl,
									'blogid'=> intval($user_blog['blog_id']),
									'lastmod'=> $details->last_updated,
									'postcount' => (int) $details->post_count,
									'role' => $role,
									'privacy' => $privacy,
									'deleted' => (int) $details->deleted
				    			);
		    			}
					}
					if($limit == $sortdata['limit']){ break; }
				}
			}
		/* ----- TESTING! ----- */

		// for ($testCount = 0; $testCount < 300 ; $testCount++) { 
		// 	$data[]  =  array(
	 //   			'isAdmin' => 1,
	 //   			'blogname' => "test blog ".$testCount,
		// 		'domain' => "demoschool.wordpress.whipplehill.net",
		// 		'path' => "/test".$testCount."/",
		// 		'siteid' => intval($testCount),
		// 		'siteurl' => "http://demoschool.wordpress.whipplehill.net/test".$testCount,
		// 		'blogid'=> intval($testCount),
		// 		'lastmod'=> "2012-01-24 22:55:45",
		// 		'postcount' => 24,
		// 		'role' => "administrator",
		// 		'privacy' => "-3"
		// 	);
		// }
		
		/* ----- TESTING! ----- */
			
			if (!empty($data)) {
				foreach ($data as $key => $row) {
	    			$blogname[$key]  = strtolower($row['blogname']);
	   		 		$bid[$key] = $row['blogid'];
	   		 		$lastmod[$key] = $row['lastmod'];
	   		 		$postcount[$key] = $row['postcount'];
	   		 		$path[$key] = strtolower($row['path']);
	   		 		$role[$key] = strtolower($row['role']);
	   			}
	   			
	   			if($sortdata['order'] == 'DESC'){
	   				$order = SORT_DESC;
	   			} else {
	   				$order = SORT_ASC;
	   			}
	   			
	   			
	   			switch( $sortdata['orderby'] ):
	   			case 'blogname':
	   				array_multisort($blogname, $order, $data);
	   			break;
	   			case 'blogid':
	   				array_multisort($bid, $order, $data);
	   			break;
	   			case 'lastmod':
	   				array_multisort($lastmod, $order, $data);
	   			break;
	   			case 'postcount':
	   				array_multisort($postcount, $order, $data);
	   			break;
	   			case 'path':
	   				array_multisort($path, $order, $data);
	   			break;
	   			case 'role':
	   				array_multisort($role, $order, $data);
	   			break;
				endswitch;
			} else {
				
			}
			// WH_Integration_Helper::log('user site data');
			// WH_Integration_Helper::log($data);
			// WH_Integration_Helper::log('End user site data');
			return $data;	
			
		/*
		}else{
			return new IXR_Error(403, __('User not found.'));
		}
		*/	
		
	}



	/**
	*
	*	GET SITES BY PODIUM ID
	*
	*/
	
	function get_sites_for_user_by_podium_id_with_sp( $args ){

		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		

		$podium_id = $args[2];
		$sortdata = $args[3];
		$is_manager = $args[4];
	
				
		if ( false === ( $blogs = get_transient( 'schoolpress_bloglist' )  )  ) {
					//WH_Integration_Helper::log('test for the logging function...inside get sites rpc method');
				include_once( ABSPATH . WPINC . '/class-IXR.php' );
				include_once( ABSPATH . WPINC . '/class-wp-http-ixr-client.php' );

				$url = get_site_url();
				$client = new WP_HTTP_IXR_CLIENT( $url . "/xmlrpc.php" );
				$result = $client->query( 'wh.getAllSites', array() );
			    $blogs = 	$client->getResponse();
			    set_transient( 'schoolpress_bloglist', $blogs , 60*15 );

		}
		



		
		$data = array();
		if( empty( $sortdata ) )
			$sortdata = array();
	
		$q_defaults =  array(
			'orderby' => 'blogname',
			'order' => 'ASC',
			'limit' => 10000
		);
				
		// Merge old and new fields with new fields overwriting old ones.
		$sortdata = array_merge($q_defaults, $sortdata);
		
		$user_id = $this->extend_obj->get_user_by_podium_id( $podium_id );
		
		$user = new WP_User($user_id);
		
		
		if( WH_Integration_Helper::bool_val( $is_manager ) ){
		 	$this->extend_obj->update_wh_super_admin($user_id, true);
		 	$isNetworkAdmin = true; //wh_super_admin( $user_id );
		} else {
		 	$this->extend_obj->update_wh_super_admin($user_id, false);
		 	//$isNetworkAdmin = false;
		 	
		 	$isNetworkAdmin = $this->extend_obj->wh_super_admin( $user_id );
		}
			
		//return $blogs;
		$limit = 0;	
		foreach ($blogs as $user_blog) {
			//return $details;
			if ($user_blog['deleted'] !== 1){
				$role = $this->extend_obj->get_user_role_for_blog( $user_id , intval($user_blog['blogid']) );
					$isAdmin = false;
					if($isNetworkAdmin){
						$role = 'administrator';
					}
					
					if( $user_blog['privacy'] == -3){
						if($role == 'administrator'){
							$limit++;
							$user_blog['isAdmin'] = $isNetworkAdmin;
							$user_blog['role'] =$role;
					   		$data[]  =  $user_blog;
						}
					} else {
						if($role != "" || $user_blog['privacy'] > -2){
							$limit++;
				   			$user_blog['isAdmin'] = $isNetworkAdmin;
							$user_blog['role'] = $role;
					   		$data[]  =  $user_blog;
					   	}
		    		}
		    	}
					if($limit == $sortdata['limit']){ break; }
				}



		//return $blogs;

		

			if (!empty($data)) {
				foreach ($data as $key => $row) {
	    			$blogname[$key]  = strtolower($row['blogname']);
	   		 		$bid[$key] = $row['blogid'];
	   		 		$lastmod[$key] = $row['lastmod'];
	   		 		$postcount[$key] = $row['postcount'];
	   		 		$path[$key] = strtolower($row['path']);
	   		 		$role[$key] = strtolower($row['role']);
	   			}
	   			
	   			if($sortdata['order'] == 'DESC'){
	   				$order = SORT_DESC;
	   			} else {
	   				$order = SORT_ASC;
	   			}
	   			
	   			
	   			switch( $sortdata['orderby'] ):
	   			case 'blogname':
	   				array_multisort($blogname, $order, $data);
	   			break;
	   			case 'blogid':
	   				array_multisort($bid, $order, $data);
	   			break;
	   			case 'lastmod':
	   				array_multisort($lastmod, $order, $data);
	   			break;
	   			case 'postcount':
	   				array_multisort($postcount, $order, $data);
	   			break;
	   			case 'path':
	   				array_multisort($path, $order, $data);
	   			break;
	   			case 'role':
	   				array_multisort($role, $order, $data);
	   			break;
				endswitch;
			} else {
				
			}
		
		return $data;
		/*
		error_log( $url );
		$objXMLRPClientWordPress = new XMLRPClientWH( $url . "/xmlrpc.php" , "" , "");
		$x = $objXMLRPClientWordPress->get_all_sites();
error_log( $x );
		return $x;


		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		/*
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
	
		/*
		}else{
			return new IXR_Error(403, __('User not found.'));
		}
		*/	
		
	}
	
	
	/**
	*
	*
	*	UPDATE USERS OF A SPECIFIC BLOG	
	*
	*
	**/	
	function add_update_users($args){
		
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		$blogid = $args[2];
		$uarray = $args[3];
		
	
		foreach($uarray as $whuser){
			
			$admincheck = 	 $this->extend_obj->get_user_by_podium_id( $whuser["userid"] );
			$isNetworkAdmin = $this->extend_obj->wh_super_admin( $admincheck );
			
			if( empty( $whuser["userid"] ) && !empty( $whuser['wpuserid'] ) ){
				$user_id = (int) $whuser['wpuserid'];
				
			} else {
				$user_id = $this->extend_obj->wh_wp_user( $whuser["userid"], $whuser["username"], $whuser["email"], $whuser["first"], $whuser["last"], $isNetworkAdmin);
			}
				
			$whuser['wpuserid'] = (int) $whuser['wpuserid'];	
							
			if(false == $user_id || is_wp_error($user_id)){
				//add to return array becasue its bad...
				if(is_wp_error($user_id)){
					//We got a ERROR back so lets us it
					$error_array= $user_id->get_error_messages('empty_user_data');
					$whuser['userid'] = (int) $whuser['userid'];
					$whuser['remove'] = WH_Integration_Helper::bool_val($whuser['remove']);
					$whuser['networkadmin'] =  WH_Integration_Helper::bool_val($whuser['networkadmin']);
					$whuser['error'] = $error_array[0];
				} else {
					//WE dont know what happened but we couldn't make a user					
					$whuser['error'] = 'User could not be create or updated.';
				}
				$struct[] = $whuser;
			}else{
				//continue
				if(!$whuser["remove"]){
					$user = new WP_User( $user_id );
					$user->for_blog( $blogid );
					$user->set_role( $whuser["role"] );	
				}else{
					remove_user_from_blog($user_id,$blogid);
				}			
			}
		}
		
		if ( empty($struct) ){
			//WE MADE IT WITH NO ERRORS RETURN AN EMPTY ARRAY
			return array();
		}
			
		return $struct;
	}	
	
	/**
	*
	*
	*	GET ALL USERS ATTACHED TO A SITE
	*
	*
	**/
	
	function get_users_for_site($args){
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		$blog_id = $args[2];
		
		// 3.3.7 - changed to get_users instead of deprecated get_users_of_blog
		$site_users = get_users_of_blog( $blog_id );
		WH_Integration_Helper::log('----SITE USERS BLOG ID: '.$blog_id.'----');
		foreach ($site_users as $user) {
			if ( isset( $user->meta_value ) && ! $user->meta_value )
				continue;
			$t = @unserialize( $user->meta_value );
			if ( is_array( $t ) ) {
			    reset( $t );
			    $existing_role = key( $t );
			}
			$whwpuser = get_userdata($user->ID);
			$puserid = intval( $this->extend_obj->get_podium_id_for_user($user->ID) );
			$struct[] = array(
				'userid' => $puserid,
				'username' => $user->user_login,
				'role' => $existing_role,
				'email' => $user->user_email,
				'wpuserid' => intval($user->ID),
				'first' => $whwpuser->first_name,
				'last' => $whwpuser->last_name
			);
			WH_Integration_Helper::log('----User ID: '.$puserid.', E-Mail: '.$user->user_email.', Role: '.$existing_role.'----');
		}
		WH_Integration_Helper::log('----SITE USERS BLOG ID: '.$blog_id.'----');
		return $struct;
	}
	
	
	function check_site_slug( $args ){
		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		$slug =		$args[2];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		return $this->extend_obj->check_site_slug($slug);
	}
	
	function get_site_info($args){
		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		$blogid = $args[2];
		
		$blog = get_blog_details( $blogid , true );
		$struct = array(
			'blogid' => intval($blogid),
			'blogname' => $blog->blogname,
			'domain' => $blog->domain,
			'path' => $blog->path,
			'siteid' => intval($blog->site_id),
			'siteurl' => $blog->siteurl
			);
	
		return $struct;
	}
	
	
	
	
	/**
	*
	*
	*	CREATE A NEW SITE ON NETWORK
	*	
	*	@param array $args Method parameters.
	*	@return wh_site_info
	*
	*
	**/

	function create_site( $args ){
		global $wpdb;
		global $current_site;
		global $base;
		global $wp_version;

		WH_Integration_Helper::log('Site Creation in Progress!');
		WH_Integration_Helper::log($args);

		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		
		$title = 		$args[2];
		$slug = 		$args[3];
		$userdata = $args[4];
		
		$ad_email = 	$userdata['email'];
		$ad_username = 	$userdata['username'];
		$pd_id = 		$userdata['userid'];
		
		$first_name = 	$userdata['first'];
		$last_name = 	$userdata['last'];
		
		$network_admin =$userdata['networkadmin'];
		
		$privacy = 		$args[5];
		
		
		//CHECK FOR NEEDED DATA
		if( !is_email($ad_email) || empty($title) || empty($slug) ||  empty($ad_username) || empty($pd_id) )
			return new IXR_Error(403,__('Missing required data site will not be created.'));
		
		$user_id = $this->extend_obj->wh_wp_user($pd_id, $ad_username, $ad_email, $first_name, $last_name, $network_admin);
		
		if(false == $user_id){
			return new IXR_Error(403, __('Could not create site, user could not be verified.'));
		}
		
		//$newdomain = 	$current_site->domain;
		//$path = 		$base . $slug . '/';
		if ( is_subdomain_install() ) {
			$newdomain = $slug . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path = $base;
		} else {

			$newdomain = $current_site->domain;

			if(version_compare($wp_version, 3.5) === -1){
				$path = $base . $slug . '/';
			}else{
				$path = $current_site->path . $slug . '/';
			}

			WH_Integration_Helper::log($newdomain);
			WH_Integration_Helper::log('Base: '.$base);
			WH_Integration_Helper::log($path);

		}
		
		
		/*
		if( wh_check_site_slug($slug) ){
			return new IXR_Error(403, __('The site domain is already taken.')); 
		}
		*/
		$wpdb->hide_errors();
		$id = wpmu_create_blog( $newdomain, $path, $title, $user_id , array( 'public' => $privacy ), $current_site->id );
		$wpdb->show_errors();
		//return ' asdfa';
		if ( !is_wp_error( $id ) ) {
			//do_action('wh_update_site_details', $id );
			$arg = array($username, $password, $id);
			
			return $this->get_site_info( $arg );
		}else{
			return $id; 
		}
		
	}
	
	function update_site_info($args){
		$this->escape( $args );
		
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		$id = $args[2];
		$title = $args[3];

		$title = stripslashes($title);
		
		if(false !== get_blog_details( $id ) ){
			update_blog_option($id,'blogname',$title);
			do_action('wh_update_site_details', $id );
			$arg = array($username, $password, $id);	
			return $this->get_site_info( $arg );
		} else {
			return new IXR_Error(403, __('There is no site with that id.')); 
		}
		
	}
	
	
	/**
	*
	*	NETWORK THEME FUNCTIONS --------------------------------------------------------->
	*
	*/
	
	
	function get_network_themes( $args ) {
		WH_Integration_Helper::log('called get_network_themes');
		WH_Integration_Helper::log($args);
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		if ( !$user = $this->wh_login($username, $password) ){
			WH_Integration_Helper::log('---Login Error---');
			WH_Integration_Helper::log($this->error);
			WH_Integration_Helper::log('---END Login Error---');
			return $this->error;
		}
			
		$themes = get_themes();

		//WH_Integration_Helper::log($themes);

		$allowed_themes = get_site_allowed_themes();
		$total_theme_count = $activated_themes_count = 0;
		
		foreach ( (array) $themes as $key => $theme ) {
				
				$theme_key = esc_html( $theme['Stylesheet'] );
				$enabled = $disabled = false;

				if ( isset( $allowed_themes[$theme_key] ) == true ) {
					$enabled = true;
				}
				
				$struct[] = array(
					'name' => $key,
					'key' => $theme_key,
					'enabled' => $enabled,
					'version' => $theme['Version'],
					'description' => $theme['Description']
				);
				
		} 

		WH_Integration_Helper::log($struct);
		
		return $struct;
	
	}
	//END GET NETWORK THEMES
	
	
	function update_network_themes( $args ) 
	{
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		$update_theme = $args[2];

		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
			
		if ( ! current_user_can( 'manage_network_themes' ) )
			return new IXR_Error(403, __('You do not have permission to access this call.'));
		
		foreach ( $update_theme as $theme ) {
			$allowed_themes[ $theme  ] = true;
		}
		
		update_site_option( 'allowedthemes', $allowed_themes );	
		return $this->get_network_themes( $args );
	}
	//END UPDATE NETWORK THEMES
	
	
	/**
	*
	*	<------------------------ NETWORK THEME FUNCTIONS
	*
	*/
	
	/**
	*
	*	NETWORK PRIVACY OPTIONS --------------------------------->
	*
	*/
	
	
	function update_network_privacy_defaults( $args ){
			global $wpdb;
			$this->escape( $args );
	
			$username = $args[0];
			$password = $args[1];
			$settings = explode("&",$args[2]);
			
			foreach($settings as $setting){
				$sets[] = explode("=",$setting);
			}
			
			
			
			$privacyDefault = $sets[0][1];
			$privacyOverride =$sets[1][1];
			$upadateAll = $sets[2][1];
	
			if ( !$user = $this->wh_login($username, $password) )
				return $this->error;
			
			$options = $this->options_obj->get();
			
			$options['privacy_default'] = $privacyDefault;
			$options['privacy_override'] = $privacyOverride;
			
			$this->options_obj->save( $options );
			
			if( WH_Integration_Helper::bool_val($upadateAll) === true ){
				$wpdb->query("UPDATE $wpdb->blogs SET public = '". $privacyDefault ."' WHERE blog_id != '1'");
				$query = "SELECT blog_id FROM $wpdb->blogs WHERE blog_id != '1'";
				$blogs = $wpdb->get_results( $query, ARRAY_A );
				if ( count( $blogs ) > 0 ) {
					foreach ( $blogs as $blog ){;
						update_blog_option($blog['blog_id'], "blog_public", $privacyDefault);
					}
				}
			}
			
			
			return $this->get_network_privacy_defaults( $args );
	
	}
	
	/**
	*
	*
	*	Gets the New Privacy Options provided by the additional privacy plugin
	*
	*
	**/
	
	function get_network_privacy_defaults( $args )
	{
		$this->escape( $args );
		$username = $args[0];
		$password = $args[1];
		
		if ( !$user = $this->wh_login($username, $password) )
			return $this->error;
		
		$options = $this->options_obj->get();
		
		$privacy_default = strval( $options['privacy_default'] );
		$privacy_override = strval( $options['privacy_override'] );
		
		$struct = $this->network_privacy_options( $privacy_default );
		$struct = array_merge($struct, $this->network_privacy_override_options( $privacy_override ));
		$struct[] = array(
		'text' => '',
		'description' => 'Updates all blogs with the default privacy setting. The main blog is not updated. Please be patient as this can take a few minutes.',
		'value' => 'update',
		'label' => 'Update All Blogs',
		'type' => 'checkbox',
		'selected'=> '',
		'key'=>'update'	
		
		);			
		
		
		return $struct;
	}
	
	
	/**
	*
	*	THIS NEEDS TO BE MOVED TO PRIVACY CLASS --------------------------------------------------->>>>>>>
	*
	*/
	
	function network_privacy_override_options( $selected )
	{
		$struct[] = array(
			'text' => 'Yes',
			'description' => '',
			'value' => 'yes',
			'label' => 'Allow Override',
			'type' => 'radio',
			'selected'=> (string)$selected,
			'key'=>'override'	
		);
		
		$struct[] = array(
			'text' => 'No',
			'description' => 'Allow Blog Administrators to modify the privacy setting for their blog(s). Note that Site Admins will always be able to edit blog privacy options.',
			'value' => 'no',
			'label' => 'Allow Override',
			'type' => 'radio',
			'selected'=> (string)$selected,
			'key'=>'override'	
		);
		return $struct;
	
	
	}
	
	/**
	*
	*
	*	Holds the default privacy options
	*
	*
	**/
	
	function network_privacy_options( $selected )
	{
	
		$struct[] = array(
					'text' => 'Allow all visitors to all blogs.',
					'description' => 'This makes all blogs visible to everyone, including search engines (like Google, Sphere, Technorati), archivers and all public listings around your site.',
					'value' => '1',
					'label' => 'Default Settings',
					'type' => 'radio',
					'selected' => (string)$selected,
					'key'=>'settings'
					
				);
	
		$struct[] = array(
					'text' => 'Block search engines from all blogs, but allow normal visitors to see all blogs.',
					'description' => '',
					'value' => '0',
					'label' => 'Default Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'key'=>'settings'
					
				);
	
		$struct[] = array(
					'text' => 'Only allow logged in users to see all blogs.',
					'description' => '',
					'value' => '-1',
					'label' => 'Default Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'key'=>'settings'
				);
		
		$struct[] = array(
					'text' => 'Only allow a registered user to see a blog for which they are registered to.',
					'description' => 'Even if a user is logged in, they must be a user of the individual blog in order to see it.',
					'value' => '-2',
					'label' => 'Default Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'key'=>'settings'
					
				);
		
		$struct[] = array(
					'text' => 'Only allow administrators of a blog to view the blog for which they are an admin.',
					'description' => 'A Site Admin can always view any blog, regardless of any privacy setting. (Note: "Site Admin", not an individual blog admin.)',
					'value' => '-3',
					'label' => 'Default Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'key'=>'settings'
				);
				
		return $struct; 
	}
	
	
	
	
	/**
	*
	*
	*	Get the Privacy Settings for a site
	*
	*
	**/
	
	function get_site_privacy_settings( $args ){
			$this->escape( $args );
			
			$username = $args[0];
			$password = $args[1];
			$blog_id = $args[2];
				
			if ( !$user = $this->wh_login($username, $password) )
				return $this->error;
			
			$options = $this->options_obj->get();
		
			$privacy_default = strval( $options['privacy_default'] );
			$privacy_override = strval( $options['privacy_override'] );
					
			if( $blog_id == 0 || !is_numeric($blog_id) ){
				$blog_public = $privacy_default;
			}else{
				$blog_public = get_blog_option($blog_id,'blog_public');
			}
			
			$struct = $this->site_privacy_options( $blog_public, $privacy_override );
						
			return $struct;
	}
	
	/**
	*
	*
	*	Update Privacy for a single site
	*
	*
	**/
	
	function update_site_privacy_settings( $args ){
			$this->escape( $args );
		
			WH_Integration_Helper::log('Updating Privacy...');
			WH_Integration_Helper::log($args);

			$username = $args[0];
			$password = $args[1];
			$blog_id = $args[2];			
			$privacy_setting = $args[3];
			
			$options = $this->options_obj->get();
		
			$privacy_default = strval( $options['privacy_default'] );
			$privacy_override = strval( $options['privacy_override'] );
	
			if ( !$user = $this->wh_login($username, $password) )
				return $this->error;
			
			if($privacy_override == 'yes'){
				update_blog_option($blog_id, 'blog_public' , $privacy_setting);
			}
			
			return $this->get_site_privacy_settings( $args );
			
		
	}
	
	/**
	*
	*
	*	Holds the Privacy Options for individual sites
	*
	*
	**/
	
	function site_privacy_options( $selected, $overrideAllowed ){
	
		$struct[] = array(
					'text' => 'I would like my site to be visible to everyone, including search engines (like Google, Bing, Technorati) and archivers',
					'description' => '',
					'value' => '1',
					'label' => 'Default Blog Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'override'=> $overrideAllowed,
					'key'=>'settings'
				);
	
		$struct[] = array(
					'text' => 'I would like to block search engines, but allow normal visitors',
					'description' => '',
					'value' => '0',
					'label' => 'Default Blog Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'override'=> $overrideAllowed,
					'key'=>'settings'
				);
	
		$struct[] = array(
					'text' => 'I would like only logged in users to see my blog.',
					'description' => '',
					'value' => '-1',
					'label' => 'Default Blog Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'override'=> $overrideAllowed,
					'key'=>'settings'
				);
		
		$struct[] = array(
					'text' => 'I would like only logged in users who are registered subscribers to see my blog.',
					'description' => '',
					'value' => '-2',
					'label' => 'Default Blog Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'override'=> $overrideAllowed,
					'key'=>'settings'
				);
		
		$struct[] = array(
					'text' => 'I would like only administrators of this blog, and network to see my blog.',
					'description' => '',
					'value' => '-3',
					'label' => 'Default Blog Settings',
					'type' => 'radio',
					'selected'=> (string)$selected,
					'override'=> $overrideAllowed,
					'key'=>'settings'
				);
				
		return $struct; 
	}
	
	
	
	
	/**
	*
	*	<<<<<<-----------------------------------------THIS NEEDS TO BE MOVED TO PRIVACY CLASS
	*
	*/
	
	
	
	/** 
	*
	*	The following functions have been taken slightly modified from the original
	* 	wordpress xmlrpc.php.
	*
	* 	Sanitize string or array of strings for database.
	*
	* 	@since 1.5.2
	*
	* 	@param string|array $array Sanitize single string or array of strings.
	* 	@return string|array Type matches $array and sanitized for the database.
	*
	*/
	
	function escape(&$array) 
	{
		global $wpdb;
	
		if(!is_array($array)) {
			return($wpdb->escape($array));
		}
		else {
			foreach ( (array) $array as $k => $v ) {
				if (is_array($v)) {
					$this->escape($array[$k]);
				} else if (is_object($v)) {
					//skip
				} else {
					$array[$k] = $wpdb->escape($v);
				}
			}
		}
	}
	//END ESCAPE
	
	/**
	*
	* 	Log user in and check for Super Admin.
	*
	* 	@param string $username User's username.
	* 	@param string $password User's password.
	* 	@return mixed WP_User object if authentication passed, false otherwise
	*
	*/
	
	function wh_login($username, $password) 
	{
		if ( !$user = $this->login($username, $password) )
			return false;
		if( !is_super_admin() ) {
			$this->error = new IXR_Error(403, __('User is not allowed to access this call.'));
			return false;
		} 
		
		return $user;
	}
	//END WH LOGIN
	
	
	/**
	* 	Log user in.
	*
	*	@since 2.8
	*
	* 	@param string $username User's username.
	* 	@param string $password User's password.
	* 	@return mixed WP_User object if authentication passed, false otherwise
	*
	*/
	
	function login($username, $password) 
	{
		if ( !get_option( 'enable_xmlrpc' ) ) {
			$this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
			return false;
		}	
		$user = wp_authenticate($username, $password);
		if (is_wp_error($user)) {
			$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
			return false;
		}
		set_current_user( $user->ID );
		return $user;
	}
	//END LOGIN

}
//END RPC CLASS
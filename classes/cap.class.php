<?php
class WH_Integration_Capabilities
{

	var $config;
	var $options_obj;
	var $extend_obj;
	
	

	public function __construct()
	{
		global $wh_integration_config;
		$this->config = $wh_integration_config;
		//Future - remove the need for options class to be loaded here.
		$this->options_obj = WH_Integration::get_instance('WH_Integration_Options');
		$this->extend_obj = WH_Integration::get_instance('WH_Integration_Extend');
		
	}
	
	function add_hooks(){
		add_filter('map_meta_cap',array( &$this, 'map_meta_cap_filter' ),100,3);
	}
	
	function remove_hooks(){
		remove_filter('map_meta_cap',array( &$this, 'map_meta_cap_filter' ),100,3);
	}
	
	/*
	
	
	FILTER SUPER ADMIN CAPABILITIES
	
	
	*/
	function map_meta_cap_filter($caps_old, $cap, $user_id ){
		$options = $this->options_obj->get();
		$args = array_slice( func_get_args(), 2 );
		$caps = array();
		global $current_user;
		global $current_screen;
		$wh_super_admin = $this->extend_obj->wh_super_admin();
		if( false !== $wh_super_admin && false == $options['wpm_as_su'] ){
			switch ( $cap ) {
				case 'manage_network':
					//$caps[] = 'do_not_allow';
				break;
				
				case 'manage_network_options':
					/*
					if( $current_screen->id == 'user-edit'){
						$caps[] = 'do_not_allow';
					}
					*/
				break;
				
				case 'manage_network_themes':
					$caps[] = 'do_not_allow';
				break;
				
				
				case 'install_plugins':
					if(! defined('WH_ALLOW_PLUGINS') ){
						$caps[] = 'do_not_allow';
					}
				
				break;
				
				case 'edit_plugins':
					$caps[] = 'do_not_allow';
				break;
				
				case 'edit_themes':
					$caps[] = 'do_not_allow';
				break;
				case 'create_users':
					$caps[] = 'do_not_allow';
				break;
				case 'update_core':
					$caps[] = 'do_not_allow';
				break;
				/*
				case 'install_themes':
					$caps[] = 'do_not_allow';
				break;
				*/
				case 'edit_users':
					$caps[] = 'do_now_allow';
				break;
				case 'domainmapping':
					$caps[] = 'do_now_allow';
				break;
				
				case 'manage_database':
					$caps[] = 'do_not_allow';
				break;
				
				case 'exclude_plugins':						
					$caps[] = 'do_not_allow';
				break;
				case 'edit_user':
					if( ! IS_PROFILE_PAGE )
						$caps[] = 'do_now_allow';
				break;
				default:
				$caps[] = $cap;
				
			}
			return $caps;
		} else {
			return $caps_old;
		}
		
	}

}
<?php
class WH_integration
{
	var $rpc_obj;
	var $privacy_obj;
	var $options_obj;
	var $extend_obj;
	var $sso_obj;
	var $cap_obj;
	var $config;
	

	public function __construct()
	{

		if( defined('XMLRPC_REQUEST') && !defined('DISABLE_WP_CRON') ){
			define('DISABLE_WP_CRON', 'true');
		}
		WH_Integration_Helper::log('Start Plugin');
		global $wh_integration_config;
		$this->config = $wh_integration_config;
		$this->options_obj = WH_Integration::get_instance('WH_Integration_Options');
		$this->update_configs();
		$options = $this->options_obj->get();
		
$this->extend_obj = WH_Integration::get_instance('WH_Integration_Extend');
		$this->sso_obj = WH_Integration::get_instance('WH_Integration_SSO');
		$this->sso_obj->add_hooks();
		if(!isset( $_GET[ $options['token_key'] ] )){
			$this->rpc_obj = WH_Integration::get_instance('WH_Integration_RPC');
			$this->privacy_obj = WH_Integration::get_instance('WH_Integration_Privacy');
			$this->cap_obj = WH_Integration::get_instance('WH_Integration_Capabilities');
			$this->add_filters_and_actions();
		}

		
						
		
	
		
	}
	
	//Get Class if we haven't already loaded it.
	function get_instance($class)
	{
		static $instances = array();
        if (!array_key_exists($class, $instances))
        {
            $instances[$class] =& new $class;
        }

        $instance =& $instances[$class];
        return $instance;
	}
	

	function create_sp(){
		//You are not running schoolpress
		if(!defined('SCHOOLPRESS_ENVIROMENT') || SCHOOLPRESS_ENVIROMENT == false ){
			define('SCHOOLPRESS_SP_INSTALLED',false);
			return false;
		}

		global $wpdb;
		//Check for SP
		$da = $wpdb->get_var("show create procedure blogs");
		if( $da == "blogs"){
			define('SCHOOLPRESS_SP_INSTALLED',true);
			return true;
		} else {
			error_log('Installing SP');
			$did_install = SP_DB_INSTALL::do_it();
			if($did_install !== false){
				define('SCHOOLPRESS_SP_INSTALLED',true);
				return true;
			}
		}
		
		define('SCHOOLPRESS_SP_INSTALLED',false);
		return false;
	}



	function update_configs(){
		
		$save_option = false;
		$options = $this->options_obj->get();
		$new_options= array();
		$stat= $this->create_sp();



		/**
		*
		*	CHECK FOR PLUGIN UPGRADE
		*
		*/
		
		
		
		if( $options['version'] < $this->config['plugin_version'] ){
			
			//IF IT IS AN OLD VERSION UPGRADE OPTIONS
			$options = $this->options_obj->upgrade();
		}
		
		/**
		*
		*	CHECK FOR OLD OPTIONS
		*
		*/
		
		// if old options exist, update to new system
		foreach( $options as $key => $value ) {
			if( $existing = get_site_option( 'wh_' . $key ) ) {
				$new_options[$key] = $existing;
				delete_option( 'wh_' . $key );
				$save_option = true;
			}
		}
		
		/**
		*
		*	CHECK FOR DEFINED SETTINGS
		*
		*/
		
		if( defined('WH_LOGOUT_URL') ){
			$new_options['logout_url'] = WH_LOGOUT_URL;
			$save_option = true;
		}
		
		if( defined('WH_SSO_SHARED_KEY') ){
			$new_options['podium_key'] = WH_SSO_SHARED_KEY;
			$save_option = true;
		}
	
		if( defined('WH_SSO_URL') ){
			$new_options['podium_url'] = WH_SSO_URL;
			$save_option = true;
		}
		
		if( defined('WH_WPM_AS_SUPER_ADMIN') ){
			$new_options['wpm_as_su'] = WH_WPM_AS_SUPER_ADMIN;
			$save_option = true;
		} else {
			if( false !==  WH_Integration_Helper::bool_val( $options['wpm_as_su' ]) ){
				$new_options['wpm_as_su'] = false;
				$save_option = true;
			}
		}
			
		
		if(false !== $save_option){
			$this->options_obj->save( $new_options );
		}
				
	}
	
	
	//Check for Known Conflicts
	function compatibility_check() 
	{
		$error = false;
		if( !is_multisite() ){
			add_action('admin_notices' , array( &$this , 'wrong_multisite_setup' ) );
			$error = true;
		}
		
		if( function_exists('additional_privacy') ){	
			add_action('admin_notices' , array( &$this , 'disable_additional_privacy' ) );
			$error = true;
		}
		if( !function_exists('mcrypt_encrypt') ){
			add_action('admin_notices', array( &$this, 'encrypt_needed' ) );
			$error = true;
		}
		if ( class_exists("absolutePrivacy") ) {
			add_action('admin_notices' , array( &$this , 'disable_absolute_privacy' ) );
			$error = true;
		}
		
		if(true == $error){
			$this->remove_filters_and_actions();
		}
	}
	
	//ADMIN NOTICES
	function wrong_multisite_setup()
	{
		echo "<div id='wh-ms-warning' class='updated fade'><p>You network is not a Multisite install. Your current configuration is unsupported by the WhippleHill Integration Plugin.</p></div>";	
	}
	
	function disable_absolute_privacy()
	{
		echo "<div id='wh-ap-warning' class='updated fade'><p>You are running the Absolute Privacy Plugin. It needs to be deactivated for the WhippleHill Integration Plugin to operate correctly.</p></div>";	
	}

	
	function disable_additional_privacy()
	{
		echo "<div id='wh-sso-warning' class='updated fade'><p>You are running the Additional Privacy Plugin. It needs to be deactivated for the WhippleHill Integration Plugin to operate correctly.</p></div>";	
	}
	
	function encrypt_needed(){
		echo "<div id='wh-sso-warning' class='updated fade'><p>WhippleHill SSO has been deactivated please install <a href='http://php.net/manual/en/book.mcrypt.php' target='_blank'>PHP Mcrypt</a>.</p></div>";
	}
	
	//End ADMIN NOTICE
	

	function add_filters_and_actions(){
		//Add check for compatibility issues.
		add_action('admin_init' , array( &$this , 'compatibility_check' ) );
		$options = $this->options_obj->get();
		
		//If the SSO is not active don't change anything in wordpress.
		if( false !== $options['sso_active'] ) {
			$this->privacy_obj->add_hooks();
			$this->extend_obj->add_hooks();
			//$this->sso_obj->add_hooks();
			$this->cap_obj->add_hooks();
		}
		
		//Attach ONLY the options for the plugin and the required rpc calls so plugin can be linked before its made active.
		$this->rpc_obj->add_hooks();
		$this->options_obj->add_hooks();
	}	
	
	function remove_filters_and_actions(){
	
		$new_options['sso_active'] = false;
		$this->options_obj->save( $new_options );
		
		$this->privacy_obj->remove_hooks();
		$this->extend_obj->remove_hooks();
		$this->sso_obj->remove_hooks();
		$this->cap_obj->remove_hooks();
	}
}
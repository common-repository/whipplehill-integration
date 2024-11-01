<?php
class WH_Integration_SSO
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
	
	function add_hooks()
	{
		add_filter( 'authenticate', array( &$this, 'sso_login_from_podium' ),10, 3);
		add_action('init', array( &$this, 'check_for_login' ));
		add_action('wp_logout', array( &$this, 'wp_logout' ));
	}
	
	function remove_hooks()
	{
		remove_filter( 'authenticate', array( &$this, 'sso_login_from_podium' ),10, 3);
		remove_action('init', array( &$this, 'check_for_login' ));
		remove_action('wp_logout', array( &$this, 'wp_logout' ));
	}
	
	function wp_logout(){
		$options = $this->options_obj->get();
		wp_redirect( $options['logout_url'] );
		exit;
	}
	
	function check_for_login()
	{
		
		$sso_get = isset( $_GET['sso'] ) ? $_GET['sso'] : 0;  
				
		if ( !is_user_logged_in() && WH_Integration_Helper::bool_val($sso_get) ) {
			wp_redirect(get_option('home') . "/wp-login.php?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
		}
			$no_sso = isset($_GET['wplogin']) ? $_GET['wplogin']: 0;
			$no_sso_user = isset($_GET['user']) ? $_GET['user'] : '';
			
			if( WH_Integration_Helper::bool_val( $no_sso ) ){
				$user_via_username = get_user_by('login', $no_sso_user );
				//wp_die("suadmin: ".$user_via_username->ID);
				if( is_super_admin($user_via_username->ID) && !$this->extend_obj->wh_super_admin($user_via_username->ID)   ){
					
					remove_filter( 'authenticate', array( &$this, 'sso_login_from_podium' ),10, 3);
				} 
			}
	}
	
	
	function sso_login_from_podium( $user , $username, $password){
		global $wh_sso_url;
		$options = $this->options_obj->get();

		//wp_die('Error, please check back later.');
				
		if( !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )){
	
			$login_token = 	$_GET[ $options['token_key'] ];



			WH_Integration_Helper::log('Start Token');
			WH_Integration_Helper::log($login_token);
			WH_Integration_Helper::log('End Token');

			$ret_url = 		$this->generate_return_url();
			$redir_url = 	$this->get_sso_redirect_url($ret_url, true);

			if( empty($login_token) ){
				WH_Integration_Helper::log($redir_url);
				wp_redirect($redir_url);
				exit;
			}
			
			$token_parts = 	$this->validate_token($login_token);

			WH_Integration_Helper::log('Start Token Parts');
			WH_Integration_Helper::log($token_parts);
			WH_Integration_Helper::log('End Token Parts');

			$podium_id = 	$token_parts[0];
			$time_stamp = 	$token_parts[1];
			$user_name = 	sanitize_user($token_parts[2]);
			$email = 		$token_parts[3];
			$admin = 		WH_Integration_Helper::bool_val( $token_parts[4] );
			$first_name =	$token_parts[5];
			$last_name = 	$token_parts[6];
			
			$whuser = false;
			if( count($token_parts) > 6 ){
				$whuser = WH_Integration_Helper::bool_val( $token_parts[7] );
			}

			if($whuser){
				global $wh_suadmin_accounts;

				if(!is_array($wh_suadmin_accounts)){
					$wh_suadmin_accounts = array();
				}

				$account = 'suadmin';
				if( defined('WH_USER_ACCOUNT') && !array_search($user_name, $wh_suadmin_accounts) ){
					$account = WH_USER_ACCOUNT;
				}

				if( substr($user_name, 0, 4) === 'pdwh' ){
					return get_user_by('login', $account );
				}
			}
			
			
			// WH_Integration_Helper::log('User Name = '.$user_name);
			// WH_Integration_Helper::log('WH User = '.$whuser);
					
			if ( WH_Integration_Helper::bool_val( $_GET['loggedout'] )) {
				return $user;
			} 	
			if ( $user == null && $podium_id == null) {
				WH_Integration_Helper::log($redir_url);
				wp_redirect($redir_url);
				exit;
			} else{ 
				
				$user_id = $this->extend_obj->wh_wp_user($podium_id, $user_name, $email, $first_name, $last_name, $admin);
				
				if($user_id === false && $GLOBALS['WH_BACKUP_SERVER']){
					//default to username because we are on the backup server
					$user_via_username = get_user_by('login', $user_name );
					if( $user_via_username !== false ){
						$user_id = $user_via_username->ID;
						//WH_Integration_Helper::log('User ID on legolas = '.$user_id);
					}else{
						//one last try, with FEELING...ok with e-mail address but still :/
						$user_via_email = get_user_by('email', $email );
						if( $user_via_email !== false ){
							$user_id = $user_via_email->ID;
							//echo 'User ID on legolas via email = '.$user_id;
						}
					}
				}

				if(is_wp_error($user_id)){
					//We got a ERROR back so lets use it
					$error_array= $user_id->get_error_messages('empty_user_data');
					wp_die( $error_array[0] ."<br>Please fix this information on your profile to use WordPress.<br>Click to return to <a href='".$wh_sso_url."/podium/'>".$wh_sso_url."/podium/</a>." );
			 		
			 	}		
			 	//WH_Integration_Helper::log('User Loading...'.$user_id); 		
			 	if( $user_id !== false){
					$user = new WP_User($user_id);
			 	} else {
			 		wp_die( __( 'You do not have WordPress account for this network.') );
			 	}
			}
			
		} else {
		
			$user_via_username = get_user_by('login', $username );
			if( !is_super_admin($user_via_username->ID) || $this->extend_obj->wh_super_admin($user_via_username->ID) ){
				remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
			} 
			if( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ){
				
			
				$user_via_username = get_user_by('login', $username );
				$wh_appid = get_user_meta(  $user_via_username->ID , 'wh_app_id');
				
				
				
				if($wh_appid[0] == WH_Integration_Helper::encrypt($password, AUTH_KEY) && !empty($wh_appid)){
					$user = new WP_User( $user_via_username->ID );
				} else{
					$error = new WP_Error();
					$error->add('incorrect_password', __('<strong>ERROR</strong>: Sorry user is not authorized to login this way.'));
					return $error;
				}
			}
		}
		return $user;
	}
	
	
	
	
	
	
	
	function generate_return_url() {
		if (is_ssl())
			$proto = 'https://';
		else
			$proto = 'http://';
	
		/* these variables can be passed in for testing */
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		$url = remove_query_arg('reauth');
		return $proto . $host. $url;
	}
	
	function get_sso_redirect_url( $returnURL, $encryptReturnUrl = false, $salt = "") {
		$options = $this->options_obj->get();
		$wh_sso_shared_key = $options['sso_shared_key'];

		WH_Integration_Helper::log('sso_shared_key');
		WH_Integration_Helper::log($wh_sso_shared_key);
		WH_Integration_Helper::log('sso_shared_key');

		WH_Integration_Helper::log('Return URL');
		WH_Integration_Helper::log($returnURL);
		WH_Integration_Helper::log('Return URL');
		
		$salt = $salt ? $salt : WH_Integration_Helper::generate_random_string(32, "abcdefghijklmnopqrstuvwxyz0123456789");
	
		if( $encryptReturnUrl ) {
			$rt_url =  $this->encrypt($returnURL, $wh_sso_shared_key, $salt, 8);
		}
		
		$rt_url = urlencode($rt_url);
		$salt = urlencode($salt);
		$vendor = urlencode( $options['vendor'] );
		$sep = (false === strrpos($rt_url, "?")) ? "&" : "?";

		$task = $options['pd_task']."&";
		if($options['eap']){
			global $wh_integration_config;
			$task = $wh_integration_config['default_options']['epa_sso']."?";
		}
		
	    $login_url = $options['sso_url'] . $task . $options['salt_key'] . "=$salt&" . $options['return_key'] . "=$rt_url&" . $options['vendor_key'] . "=$vendor";
	
		return $login_url;
	}
	
	
	
	function validate_token($token, $salt = '') {
		
		$options = $this->options_obj->get();
		$wh_sso_shared_key = $options['sso_shared_key'];
		if(!$token) return 0;
	
		$salt = $salt ? $salt : $_GET[ $options['salt_key'] ];
		$decryptedToken = $this->decrypt($token, $wh_sso_shared_key, $salt, 8);	
	
		//echo "salt: '$salt'<br>";
		//echo "decryptedToken: '$decryptedToken'<br>";

		WH_Integration_Helper::log('decrypted Token');
		WH_Integration_Helper::log($decryptedToken);
		WH_Integration_Helper::log('decrypted Token');
		
		$tokenParts = explode("||", $decryptedToken);
		$currentTime = time();
		
		WH_Integration_Helper::log('Time');
		WH_Integration_Helper::log($currentTime);
		WH_Integration_Helper::log('End Time');
		
		$tokenTime = $tokenParts[1] * 1;
		
		WH_Integration_Helper::log('Token Time');
		WH_Integration_Helper::log($tokenTime);
		WH_Integration_Helper::log('End Token Time');
		
		if( defined('WH_TIMEOUT_OVERRIDE') ){
			$options['timeout'] = WH_TIMEOUT_OVERRIDE;
		}

		WH_Integration_Helper::log('Timeout');
		WH_Integration_Helper::log($options['timeout']);
		WH_Integration_Helper::log('End Timeout');

		if( $currentTime - $tokenTime <= $options['timeout'] ) {
			//echo "valid<br>";
			return $tokenParts;
		} else {
			//echo "invalid<br>";

			wp_die('There was a problem logging into the site.  If you want to try again please <a href="'.$this->get_sso_redirect_url($this->generate_return_url(), true).'">click here</a> or return to <a href="'.$options['logout_url'].'">'.$options['logout_url'].'</a>.');
			
			return array(null,null,null,null);
		}
	}

	
	function decrypt($string_to_decrypt, $key, $iv, $keepingSigsSame) {
	    $string_to_decrypt = base64_decode($string_to_decrypt);
	
	    $rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);
	    $rtn = rtrim($rtn, "\0\4");
	
	    return($rtn);
	}

	function encrypt($string_to_encrypt, $key, $iv, $keepingSigsSame) {
		
	    $rtn = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);
	
	    $rtn = base64_encode($rtn);
	
	    return($rtn);
	}  
	
	
	

}
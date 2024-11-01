<?php
class WH_Integration_Extend
{

	var $config;
	var $options_obj;
	
	
	public function __construct()
	{
		global $wh_integration_config;
		$this->config = $wh_integration_config;
		//Future - remove the need for options class to be loaded here.
		$this->options_obj = WH_Integration::get_instance('WH_Integration_Options');
	}
	
	function add_hooks(){
		add_action('show_user_profile', array( &$this , 'profile_personal_options' ) );
		add_action('edit_user_profile', array( &$this , 'profile_personal_options' ) );
		add_action('edit_user_profile_update',  array( &$this ,'personal_options_update' ) );
		add_action('personal_options_update', array( &$this ,'personal_options_update' ) );
		
		
		
		add_action('wpmu_new_blog',  array( &$this , 'wpmu_new_blog' ), 100, 1 );
		
		add_filter('wpmu_users_columns',array( &$this , 'edit_users_columns' ));
		add_filter('manage_users_custom_column', array( &$this , 'custom_columns' ),0, 2);
		
		//Remove Items that should only for Full Super Admins 
		$options = $this->options_obj->get();
		if ( !is_super_admin() || ( $this->wh_super_admin() && false ==  $options['wpm_as_su'] )  ){
			
			add_action('admin_notices',  array( &$this , 'admin_notices' ) );
			add_action('admin_head', array( &$this , 'super_admin_style' ));			
			add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );	
			remove_action( 'load-update-core.php', 'wp_update_plugins' );
			add_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );
			add_filter('show_password_fields',create_function( '$a', "return false;" ) );
			add_filter('show_adduser_fields',create_function( '$a', "return false;" ) );
		
		}
		
	}
	
	
	function remove_hooks(){
		remove_action('show_user_profile', array( &$this , 'profile_personal_options' ) );
		remove_action('edit_user_profile', array( &$this , 'profile_personal_options' ) );
		remove_action('edit_user_profile_update',  array( &$this ,'personal_options_update' ) );
		remove_action('personal_options_update', array( &$this ,'personal_options_update' ) );
		
		
		
		remove_action('wpmu_new_blog',  array( &$this , 'wpmu_new_blog' ), 100, 1 );
		
		remove_filter('wpmu_users_columns',array( &$this , 'edit_users_columns' ));
		remove_filter('manage_users_custom_column', array( &$this , 'custom_columns' ),5, 2);
		
		//Remove Items that should only for Full Super Admins 
		$options = $this->options_obj->get();
		if ( !is_super_admin() || ( $this->wh_super_admin() && false ==  $options['wpm_as_su'] )  ){
			
			remove_action('admin_notices',  array( &$this , 'admin_notices' ) );
			remove_action('admin_head', array( &$this , 'super_admin_style' ));			
			remove_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );	
			add_action( 'load-update-core.php', 'wp_update_plugins' );
			remove_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );
			remove_filter('show_password_fields',create_function( '$a', "return false;" ) );
			remove_filter('show_adduser_fields',create_function( '$a', "return false;" ) );
		
		}
		
	}
	
	
	
	/*

	Remove Add Site From Manage Sites Page
	
	*/
	function super_admin_style(){
		global $submenu;
		global $current_screen;
		
		echo "<style>#form-add-site{display:none} }</style>";
		
		if($current_screen->id == 'ms-sites' || $current_screen->id == 'ms-users'){
			 echo "<style>.add-new-h2{display:none} }</style>";
		}
			
		echo "<style>
			#icon-ms-sites {
			background: transparent url(../wp-admin/images/icons32.png) no-repeat -659px -5px;
			}
			</style>";
		
		unset($submenu['options-general.php'][40]);
		
	}

	
	
	function wpmu_new_blog( $blog_id ){
		update_blog_option($blog_id,"comments_notify", 1);
		update_blog_option($blog_id,"comment_moderation", 1);
		update_blog_option($blog_id,"comment_whitelist", 0);
		$timezone = get_blog_option(1, 'gmt_offset');
		update_blog_option($blog_id,"gmt_offset", $timezone);
		$timezone_string =get_blog_option(1, 'timezone_string');
		update_blog_option($blog_id,"timezone_string", $timezone_string);
		refresh_blog_details( $blog_id );
	}
	
	
	
	function edit_users_columns($columns){
		$users_columns = array(
			//'id'           => __( 'ID' ),
			'username'      => __( 'Username' ),
			'name'       => __( 'Name' ),
			'email'      => __( 'E-mail' ),
			'registered' => _x( 'Registered', 'user' ),
			'pid'		=> __( 'WH ID'),
			'whwpadmin'	=> __( 'WPM' ),	
			'blogs'      => __( 'Sites' )
		);
		return $users_columns;
	}

	
	function custom_columns( $column , $id){
		switch($column):
		case 'pid':
			echo $this->get_podium_id_for_user($id).'234';	
		break;
		case 'whwpadmin':
			if( $this->wh_super_admin( $id ) ){
				echo '<input type="checkbox" name="whwpadmin" id="whwpadmin" checked disabled />';	
			} else {
				echo '<input type="checkbox" name="whwpadmin" id="whwpadmin" disabled />';	
			}
		break;
		endswitch;
		
	
	}
	function get_blog_list( $start = 0, $num = 10 ) {
		global $wpdb;
		$blogs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE site_id = %d AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );
	
		if ( false == is_array( $blogs ) )
			return array();
	
		if ( $num == 'all' )
			return array_slice( $blogs, $start, count( $blogs ) );
		else
			return array_slice( $blogs, $start, $num );
	}

	function update_wh_super_admin($user_id, $network_admin){
		if( $network_admin == true) {
			$this->grant_wh_super_admin( $user_id );
			return true;
		}
		$this->revoke_wh_super_admin( $user_id );
		return false;
	}
	
	function admin_notices(){
		global $user_id;
		global $current_screen;
		?>
		<?php if ($current_screen->id == 'user-edit' && !IS_PROFILE_PAGE && $this->wh_super_admin( $user_id ) ) { ?>
			<div class="updated"><p><strong><?php _e('Important:'); ?></strong> <?php _e('This user has WhippleHill WordPress manager privileges.'); ?> </p></div>
		<?php } ?>
		<?php
	}
	
	
	function grant_wh_super_admin( $user_id ) {
		global $wh_super_admins;
	
		// If global super_admins override is defined, there is nothing to do here.
		if ( isset($wh_super_admins) )
			return false;
	
		do_action( 'grant_wh_super_admin', $user_id );
	
		// Directly fetch site_admins instead of using get_super_admins()
		
		$options = $this->options_obj->get(); 
		$wh_super_admins =  $options['wh_super_admins'];
		
		$user = new WP_User( $user_id );
		
		//updated for wordpress 3.1 - allows super admin access to the network sites.
		$user->for_blog( 1 );
		$user->add_role( "subscriber" );	
					
		grant_super_admin( $user_id );
		if ( ! in_array( $user->user_login, $wh_super_admins ) ) {
			$wh_super_admins[] = $user->user_login;
			$options['wh_super_admins'] = $wh_super_admins;
			$this->options_obj->save( $options );
			do_action( 'granted_wh_super_admin', $user_id );
			return true;
		}
		return false;
	}


	function revoke_wh_super_admin( $user_id ) {
		global $wh_super_admins;
	
		// If global super_admins override is defined, there is nothing to do here.
		if ( isset($wh_super_admins) )
			return false;
	
		do_action( 'revoke_wh_super_admin', $user_id );
	
		$options = $this->options_obj->get(); 
		$wh_super_admins =  $options['wh_super_admins'];
		
			
		$user = new WP_User( $user_id );
		revoke_super_admin( $user_id );
		if ( $user->user_email != get_site_option( 'admin_email' ) ) {
			if ( false !== ( $key = array_search( $user->user_login, $wh_super_admins ) ) ) {
				unset( $wh_super_admins[$key] );
				$options['wh_super_admins'] = $wh_super_admins;
				
				$this->options_obj->save( $options );
				do_action( 'revoked_wh_super_admin', $user_id );
				
				return true;
			}
		}
		return false;
	}
	
	
	function get_wh_super_admins() {
		global $wh_super_admins;
		if ( isset($$wh_super_admins) ){
			return $wh_super_admins;
		} 	
		
		$options = $this->options_obj->get(); 
		return $options['wh_super_admins'];
	}

	
	function wh_super_admin( $user_id = false ) {
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id = ! empty($current_user) ? $current_user->id : 0;
		}
	
		if ( ! $user_id )
			return false;
	
		$user = new WP_User($user_id);
	
		if ( is_multisite() ) {
			$wh_super_admins = $this->get_wh_super_admins();
			if ( is_array( $wh_super_admins ) && in_array( $user->user_login, $wh_super_admins ) )
				return true;
		} else {
			if ( $user->has_cap('delete_users') )
				return true;
		}
	
		return false;
	}

	/*
	*
	* CHECK FOR WORDPRESS USER WITH PDID
	* IF NO USER THEN CHECK EMAIL AND LINK ACCOUNTS OR 
	* CREATE A NEW USER IF NEEDED
	*
	* RETURNS - wordpress user_id
	*/
	function wh_wp_user($podium_id, $username , $email, $firstname , $lastname, $network_admin = false){
		
		if( empty($podium_id) || empty($email) || empty( $username ) || empty($firstname) || empty($lastname) ){
			$message = "User is missing the following info: ";
			if(empty($podium_id)){
				$err .= "Podium ID";
			}
			if(empty($username) ){
				if(!empty($err)){ $err .= ", "; }
				$err .= "Username";
			}
			if(empty($email) ){
				if(!empty($err)){ $err .= ", "; }
				$err .= "Email";
			}
			if(empty($firstname)){
				if(!empty($err)){ $err .= ", "; }
				$err .= "First Name";
			}
			if(empty($lastname)){
				if(!empty($err)){ $err .= ", "; }
				$err .= "Last Name";
			}
			$err .= ".";
		
		
			return new WP_Error('empty_user_data', __($message . $err) );
			
		}
		
		$user_id_via_podium = $this->get_user_by_podium_id( $podium_id );
		$user_via_username = get_user_by('login', $username );
		$user_id_via_email = email_exists( $email );
		
		
		
		if( false !== $user_id_via_podium ) {
			//We matched a user by a podium id so update there data and return their user info
			$userinfo = $this->update_wp_user($user_id_via_podium, $username, $email, $firstname, $lastname);
			
			if( !is_wp_error($userinfo) ){
				$this->update_wh_super_admin( $userinfo, $network_admin);
			}
			return $userinfo;
		}
		
		$options = $this->options_obj->get();

		if($options['enable_user_overwrite']){

			if( false !== $user_via_username ){
				//We found a match for usernames so link podium id and update user record
				$userinfo = $this->update_wp_user( $user_via_username->ID, $username, $email, $firstname, $lastname );
				if( !is_wp_error($userinfo) ){
					$this->link_user_by_podium_id( $user_via_username->ID, $podium_id );
					$this->update_wh_super_admin($userinfo ,$network_admin);
				}
				return $userinfo;		
			}
			
			if( false !== $user_id_via_email){
				//We found a matching email so link podium id and update user record
				$userinfo = $this->update_wp_user($user_id_via_email, $username, $email, $firstname, $lastname);
				if( !is_wp_error($userinfo) ){
					$this->link_user_by_podium_id( $user_id_via_email, $podium_id );
					$this->update_wh_super_admin($userinfo,$network_admin);
				}
				return $userinfo;
			}

		}
		

		//before creating a new user, we need to check that the username and email don't already exist
		if( false !== $user_via_username || false !== $user_id_via_email ){
			return new WP_Error('empty_user_data', __("E-mail or user name already exist in WordPress, please make sure your user name and e-mail address are unique.") );
		}else{
			//IF YOU MADE IT THIS FAR I HAVE NO IDEA WHO YOU ARE SO WE ARE GOING TO MAKE A NEW USER FOR YOU!!
		
			$password = wp_generate_password();
			$user_id = wpmu_create_user( $username, $password, $email );

			if( $user_id ){
				//IF WE MADE A USER THEN UPDATE THEM AND RETURN
				$userinfo = $this->update_wp_user($user_id, $username, $email, $firstname, $lastname);
				if( !is_wp_error($userinfo) ){
					$this->link_user_by_podium_id( $user_id, $podium_id );
					$this->update_wh_super_admin($userinfo,$network_admin);
				}
				return $userinfo;
				
			}

		}
		
		//SOMETHING WENT HORRIBLY WRONG IF YOU GOT HERE BUT YOU CAN DEAL WITH THE ISSUE SO WE JUST SEND BACK FALSE
		return new WP_Error('empty_user_data', __("User was not created because of data issues.") );
	}
	
	function link_user_by_podium_id($userid,$pid)
	{
		update_user_meta( $userid, 'podium_id', $pid );
		return get_user_meta($userid, 'podium_id');
	}
	
	function get_podium_id_for_user( $user_id = 0 ){
		if( $user_id === 0 ){
			global $current_user;
			$user_id = $current_user->ID;
		}
	
		$meta = get_user_meta($user_id, 'podium_id');
		return $meta[0];
	}

		
	function update_wp_user($wpuserid, $user_name, $email, $first, $last){
		//Manually update the user's login 
		if(false === $this->update_user_login($user_name, $wpuserid))	
			return false;
		
		//If we could update the login continue with the rest of the data
		// $user = get_userdata( $wpuserid );
		// $user->user_email = $email;
		// $user->user_firstname = $first;
		// $user->user_lastname = $last;
		// return wp_update_user( get_object_vars( $user ) );

		return wp_update_user( array('ID' => $wpuserid, 'first_name' => $first, 'last_name' => $last, 'user_email' => $email) );

	}
	
	function update_user_login($user_name, $user_id)
	{
		global $wpdb;
		$q = sprintf( "UPDATE %s SET user_login='%s' WHERE ID=%d", $wpdb->users, $user_name, (int) $user_id );
		if (false !== $wpdb->query($q)){
			return $user_id;
		} else {
			return false;
		}
	}
	
	
	function check_site_slug($slug){
		//global $wpdb;
		global $current_site;
		global $base;
		
		$newdomain = $current_site->domain;
		$path = $base . $slug . '/';
		
		$domain = preg_replace( '/\s+/', '', sanitize_user( $newdomain, true ) );
		
		if(domain_exists($domain,$path,1)){
			return true;
		}
		$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
		if ( in_array( $slug, $subdirectory_reserved_names ) ){
			return true;
		}
			
		return false;
	}
	
	
	/*
	*
	* LOOKUP WORDPRESS USER FROM PODIUM ID
	*
	*/
	function get_user_by_podium_id( $pd_id ){
		global $wpdb;
		$metavalues = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'podium_id' AND meta_value = %s",$pd_id),ARRAY_N);
		if ( !empty($metavalues) ){
			return $metavalues[0][0];
		}
		return false;
	}
	
	/**
	*
	*
	*	Get User's Role for a blog
	*	
	*	Uses current blog if none is passed
	*	Uses current user if none is passed
	*
	**/
	
	function get_user_role_for_blog($user_id = 0, $blog_id = 0){
		global $current_user;
		global $current_blog;
		
		if( $blog_id === 0 ){
			$blog_id = (int) $current_blog->blog_id;
		}
		
		if( $user_id !== 0 ){
			$user =  new WP_User($user_id);
		} else {
			$user = $current_user;
		}
		
		$user->for_blog( $blog_id );
		$role= array_shift($user->roles);	
		return $role;
	}
	
	function personal_options_update( $user_id ){
		$wh_app_id_new = $_POST['wh_app_id_new'];
		if($wh_app_id_new){
			$wh_appid = WH_Integration_Helper::generate_random_string(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-)(*^%${}?');
			update_user_meta($user_id , 'wh_app_id' ,  WH_Integration_Helper::encrypt($wh_appid, AUTH_KEY));
		}
	
		$pdid = $_POST['user_pd'];
		if( is_numeric($pdid) ){
			$this->link_user_by_podium_id( $user_id, $pdid );
		}


	}



	
	
	
	function profile_personal_options($profileuser)
	{
		$options = $this->options_obj->get();
		?>
		<?php if( $this->wh_super_admin() && false ==  $options['wpm_as_su'] ) { ?>
		<script>  
		jQuery(document).ready(function(){
			jQuery('#email').attr('readonly', true).after(' <span class="description">Must be updated in Podium</span>');
			jQuery('#first_name').attr('readonly', true).after(' <span class="description">Must be updated in Podium</span>');
			jQuery('#last_name').attr('readonly', true).after(' <span class="description">Must be updated in Podium</span>');
			jQuery('#super_admin').attr('disabled', true)
			
		});
		</script>
		<?php } ?>
		<br>
		<h3><?php _e('Application Access') ?></h3>
		<?php  $wh_appid = get_user_meta( $profileuser->ID , 'wh_app_id'); ?>
		<?php
		if(empty($wh_appid)){
			$wh_appid = WH_Integration_Helper::generate_random_string(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-)(*^%${}?');
			update_user_meta($profileuser->ID , 'wh_app_id' , WH_Integration_Helper::encrypt($wh_appid, AUTH_KEY));
			$wh_appid = get_user_meta( $profileuser->ID , 'wh_app_id');
		} 
		?>
		<?php //echo $wh_appid[0]; ?>
		<?php //echo wh2_decrypt($wh_appid[0], AUTH_KEY) ?>
		<table class="form-table">
		<tr>
			<th><label for="wh_app_id"><?php _e('Password'); ?></label></th>
			<td><input type="text" name="wh_app_id" id="wh_app_id" value="<?php echo WH_Integration_Helper::decrypt($wh_appid[0], AUTH_KEY) ?>" readonly class="regular-text" /> <span class="description"><?php _e('Use this password to login from applications like MarsEdit, Windows Live Writer and the WordPress iPhone App.'); ?></span></td>
			
		</tr>
		</table>
		
		
		
		<table class="form-table">
		<tr>
		<th><label for="wh_app_id_new"><?php _e(' '); ?></label></th>
		<td align="left"><input type="checkbox" name="wh_app_id_new" id="wh_app_id_new" /> <span class="description"><?php _e('Generate a new Application Password.'); ?></span></td></tr>
		</table>
		
		<br>
		<h3><?php _e('Podium User Info') ?></h3>
		<?php  $pdid = get_user_meta( $profileuser->ID , 'podium_id'); ?>
		<table class="form-table">
		<tr>
			<th><label for="user_pd"><?php _e('ID'); ?></label></th>
			<td><input type="text" name="user_pd" id="user_pd" value="<?php echo $pdid[0] ?>" <?php if (!is_super_admin()){?>readonly<?php } ?> class="regular-text" /> <span class="description"><?php _e('Needs to be changed by a WordPress Manager'); ?></span></td>
		</tr>
		</table>
		
		<?php
	}

	
	
	
}
<?php
class WH_Integration_Options
{
	var $config;
	var $optionscache;
	
	

	public function __construct()
	{
		global $wh_integration_config;
		$this->config = $wh_integration_config;
	}
	
	function add_hooks(){
		WH_Integration_Helper::log('add hooks loaded in options');
		add_action('wpmu_options', array( &$this , 'site_admin_options' ) );
		add_action('update_wpmu_options', array( &$this , 'site_admin_options_update' ) );
	}
	
	function site_admin_options(){
		$options = $this->get(); 
		
		?>
			<h3><?php _e('WhippleHill Integration Settings') ?></h3> 
			<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="site_name">Use SSO:</label></th>
				<td>
					<input type="checkbox" name="whi[sso_active]" value="true" <?php if( $options['sso_active'] == true ){?>checked<?php } ?> <?php echo $options['sso_active_checkbox_enable']; ?>>
					
					Once activated you will have to use your Podium Login.
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="site_name">Enable XML-RPC:</label></th>
				<td>
					<input name="enable_xmlrpc" type="checkbox" id="enable_xmlrpc" value="1" <?php checked('1', get_blog_option(1,'enable_xmlrpc')); ?> />
					
					<?php _e('This only activates XML-RPC for the main site. You can also edit this option here: <a href="/wp-admin/options-writing.php">Writing Options</a>'); ?>
				</td>
			</tr>
			</table>
			<table class="form-table">
			
			<tr valign="top">
				<th scope="row"><label for="site_name">API Key:</label></th>
				<td>
					<input name="whi[sso_shared_key]" type="text" id="whi[sso_shared_key]" class="regular-text" value="<?php echo $options['sso_shared_key']; ?>" <?php echo $options['sso_shared_key_readonly']; ?> /><br>
					<?php _e('This is the key given to you by WhippleHill.'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="site_name">SSO URL:</label></th>
				<td>
					<input name="whi[sso_url]" type="text" id="whi[sso_url]" class="regular-text" value="<?php echo $options['sso_url']; ?>" <?php echo $wh_sso_url_readonly; ?> /><br>
					<?php _e('The URL that WhippleHill\'s Software is installed on for your school.'); ?>
					<p>Ex: http://whipplehill.com  -  make sure your remove the / on the end of the url.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="site_name">Logout URL:</label></th>
				<td>
					<input name="whi[logout_url]" type="text" id="whi[logout_url]" class="regular-text" value="<?php echo $options['logout_url']; ?>" <?php echo $wh_logout_url_readonly; ?> /><br>
					<?php _e('Set the url for where a person is sent when they logout of WordPress. Default is your main blog.') ?>
				</td>
			</tr>
			<?php if( !defined('WH_TIMEOUT_OVERRIDE') && ((defined('WH_WPM_AS_SUPER_ADMIN') && WH_WPM_AS_SUPER_ADMIN) || WH_Integration_Helper::is_suadmin()) ){?>
			<tr valign="top">
				<th scope="row"><label for="site_name">SSO Timeout:</label></th>
				<td>
					<input name="whi[timeout]" type="text" id="whi[timeout]" class="regular-text" value="<?php echo $options['timeout']; ?>" <?php echo $options['timeout']; ?> /><br>
					<?php _e('Set the length of time a SSO Token from podium should be valid in seconds.') ?>
				</td>
			</tr>
			<?php } ?>
			</table>

			<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="site_name">App:</label></th>
				<td>
					<input type="checkbox" name="whi[eap]" value="false" <?php if( $options['eap'] == true ){?>checked<?php } ?> >
					Check here if your school is using <b>/app</b> to login
				</td>
			</tr>
			</table>

			<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="site_name">Enable Logging:</label></th>
				<td>
					<input type="checkbox" name="whi[enable_logging]" value="false" <?php if( $options['enable_logging'] == true ){?>checked<?php } ?> <?php echo $options['enable_logging']; ?>>
					Enables plugin logging, only enable this if the WhippleHill Integration isn't working correctly.
				</td>
			</tr>
			</table>
			
			<!-- <table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="site_name">Enable User Matching:</label></th>
				<td>
					<input type="checkbox" name="whi[enable_user_overwrite]" value="false" <?php if( $options['enable_user_overwrite'] == true ){?>checked<?php } ?> <?php echo $options['enable_user_overwrite']; ?>>
					Enables user overwriting in WordPress.  This will allow current WordPress users to be automatically matched up with podium users where the user name or e-mail match.  We recommend using this option only when adding Podium SSO to a WordPress network that's already established and has users.
				</td>
			</tr>
			</table> -->
		<?php
	
	}
	
	function site_admin_options_update() 
	{
		$new_options = $_POST['whi'];
		$bool_opts = array( 'sso_active', 'enable_logging', 'enable_user_overwrite', 'eap' );
		foreach($bool_opts as $key) {
			$new_options[$key] = $new_options[$key] ? true : false;
		}
		$whenable_xmlrpc = $_POST['enable_xmlrpc'];
		update_blog_option(1, 'enable_xmlrpc', $whenable_xmlrpc);
		
		$this->save( $new_options );
		
	}
	
	
	function get()
	{
        static $options;
        
        if (!isset($options) )
        {
		    $options = get_site_option($this->config['plugin_name']);
		    if (empty($options))
		    {
			    $options = $this->config['default_options'];
		    }
		    
        }
      
		if( isset($this->optionscache) ){
			return $this->optionscache;
		}
		return $options;
	}
	
	
	function upgrade(){
		
		//Get Current Options
		$options_old = $this->get();
		
		//Get New Defaults
		$default_options = $this->config['default_options'];
		
		//Add any new Defaults to old options array
		$options = $this->array_extend($options_old, $default_options);
		
		//Update Options Verions Number
		$options['version'] = $default_options['version'];
		
		//Save
		$this->save( $options );
		return $options;
	}
	
	function array_extend($a, $b) {
	    foreach($b as $k=>$v) {
	        if( is_array($v) ) {
	            if( !isset($a[$k]) ) {
	                $a[$k] = $v;
	            } else {
	                $a[$k] = $this->array_extend($a[$k], $v);
	            }
	        } else {
	        	if( !isset($a[$k] )){ 
	            	$a[$k] = $v;
	            }
	        }
	    }
	    return $a;
	}
	
	
	function reset()
	{
	
	}
	
	function save( $new_options )
	{
        $options = get_site_option($this->config['plugin_name']);
		if ( empty($options) )
		{
			$options = $this->config['default_options'];
		}
        
        // Merge old and new fields with new fields overwriting old ones.
		$options = array_merge($options, $new_options);

		//set the timeout value from the default, just for a quick bug fix
		//$options['timeout'] = $this->config['default_options']['timeout'];
               
        get_site_option($this->config['plugin_name']) === false ? 
            add_site_option($this->config['plugin_name'], $options) : 
            update_site_option($this->config['plugin_name'], $options);
            
        $this->optionscache = $options;
            
   }
}
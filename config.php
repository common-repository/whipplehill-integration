<?php
global $wpdb;
global $wh_integration_config;
$wh_integration_config = array(
	'plugin_name' => 'whipplehill-integration',
    'plugin_path' => '/' . PLUGINDIR . '/whipplehill-integration/',
	'plugin_version' => '3.4',
	'plugin_url' => 'http://wordpress.org/extend/plugins/whipplehill-integration/'
);


$wh_integration_config['default_options'] = array(
		'privacy_default' 	=> 1,
		'privacy_override' 	=> 'yes',
		'powered'   		=> true,
		'debug'     		=> false,
		'version'			=> $wh_integration_config['plugin_version'],
		'pd_task'			=> '/podium/Default.aspx?t=52841',
		'epa_sso'			=> '/app/sso/schoolpress',
		'vendor'			=> 'wordpress',
		'return_key'		=> 'rt_url',
		'token_key' 		=> 'wh_sso_login',
		'salt_key'			=> 'wh_s',
		'vendor_key'		=> 'vendorkey',
		'timeout'			=> 20,
		'logout_url'		=> 'http://www.google.com',
		'sso_active'		=> 0,
		'enable_logging'	=> 0,
		'sso_shared_key'	=> '',
		'sso_url'			=> '',
		'wpm_as_su'			=> false,
		'wh_super_admins'	=> array('whipplehill-integration')
	);

//define('WH_WPM_AS_SUPER_ADMIN', true);

<?php
/*
Plugin Name: WhippleHill Integration
Plugin URI: http://wordpress.org/extend/plugins/whipplehill-integration/
Description: Connects a MultiSite WordPress installation to a school's Podium software. Requires the Integraton to be activated in Podium by WhippleHill.
Version: 3.4.1
Author: WhippleHill Communications
Author URI: http://www.whipplehill.com
Network: true
*/

require_once(ABSPATH . WPINC . '/registration.php');
require_once(ABSPATH . WPINC . '/pluggable.php');
require_once(ABSPATH . WPINC . '/ms-functions.php');


//Required for changing Super Admin Status
if ( is_multisite() ) {
	require_once(ABSPATH . 'wp-admin/includes/ms.php');
	require_once(ABSPATH . 'wp-admin/includes/ms-deprecated.php');
}


require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/classes/class.xmlrpc.php');
require_once(dirname(__FILE__) . '/classes/class.schoolpress.php');
require_once(dirname(__FILE__) . '/classes/helper.class.php');
require_once(dirname(__FILE__) . '/classes/options.class.php');
require_once(dirname(__FILE__) . '/classes/extend.class.php');
require_once(dirname(__FILE__) . '/classes/privacy.class.php');
require_once(dirname(__FILE__) . '/classes/rpc.class.php');
require_once(dirname(__FILE__) . '/classes/sso.class.php');
require_once(dirname(__FILE__) . '/classes/cap.class.php');
require_once(dirname(__FILE__) . '/classes/wh-int.class.php');

//define('WH_WPM_AS_SUPER_ADMIN',true);
global $wh_integration;

/*
add_action( 'set_current_user', 'testfuncwh' );
function testfuncwh(){
	global $wh_integration;
	if(!isset($wh_integration)){
		$wh_integration = new WH_integration();
	}
}
*/
add_filter('init','testfunctionrpc');

function testfunctionrpc($stuff){
	global $wh_integration;
	if(!isset($wh_integration)){
		$wh_integration = new WH_integration();
	}
	return $stuff;
}
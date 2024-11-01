=== WhippleHill Integration ===
Contributors: WhippleHill Communications
Tags: whipplehill 
Requires at least: 3.1
Tested up to: 3.1.3
Stable tag: 3.4.1

Creates a fully integrated solution between a WordPress MultiSite installation and WhippleHill's Podium Platform.

== Description ==

The WhippleHill Integration plugin for WordPress allows a school to manage a WordPress blog network right from their Podium platform. Taking advantage of [WhippleHill's Ecosystem](http://whipplehill.com/ecosystem/ecosystem/) this plugin helps bring the industry leading blogging platform WordPress right into the classroom. 

= Key features: =
*   "SSO" - [Single Sign On](http://whipplehill.com/products/product_detail/authentication.aspx "WhippleHill SSO") allows for user's to log in using their Podium credentials.
*   Provision new blogs from Podium.
*   Easy user management from within Podium. Quickly add or remove WordPress Admins, Editors, Authors, Contributors and Subscribers all from one central location.
*   Advanced privacy controls for WordPress.
*   List blogs on your Podium portals.
*   Manage the available themes on your network.
*	Added Security - User's have an Application Access Key created for them on their profile page. This key is for services and programs like MarsEdit, Windows Live Writer, Posterous and More.

= Extra Requirements =
*  Multisite enabled
*  The Mcrypt PHP library

We can't test every possible environment but if you can run WordPress and have the above installed the plugin should run without any trouble.

= Plugin Conflicts =
*  [Additional Privacy](http://premium.wpmudev.org/project/sitewide-privacy-options-for-wordpress-mu "Plugin Homepage") from WPMU DEV
*  [Absolute Privacy](http://wordpress.org/extend/plugins/absolute-privacy/ "Plugin Directory") 


= Who we are: =

WhippleHill Communications provides targeted communications solutions for independent schools seeking next-generation Web services. Our core Podium platform combines powerful content management and student data management software in a modular system design to allow schools to add functionality at their own pace.

== Installation ==

You can use the built in installer or you can install the plugin manually. This plugin does require that you are running WhippleHill's Podium platform and have the WordPress integration activated.

1.   You can either use the automatic plugin installer or your FTP program to upload it to your wp-content/plugins directory. Make sure you upload the entire `whipplehill-integration` folder. Don't just upload all the php files and put them in `/wp-content/plugins/`.

1.   Create two `Super Admin` accounts in WordPress. One will be for you to manage your install with once the integration is active and the other will be for Podium to use to control WordPress.

1.   Activate the plugin through the `Plugins` menu in WordPress. Make sure you don't get any warnings from the plugin. If you do please correct them before proceeding. 

1.   Don't activate the `SSO` under `WhippleHill Integration Settings` until you have complete all the steps below.

1.   Contact WhippleHill to get your `API key` and have the integration in Podium activated. You will need to provide WhippleHill with a `Super Admin` account and have RPC enabled on your main site. This is what Podium uses to authenticate with your WordPress install.

1.   Enter your `API key` in the `WhippleHill Integration Settings` on the `Options` page in WordPress.

1.   Now you can activate the `SSO`.

= Self-Hosted Settings = 

If you would like your WordPress Managers from WhippleHill to be full Super Admin's you will need to add this setting to your wp-config.php.

define('WH_WPM_AS_SUPER_ADMIN', true);


== Frequently Asked Questions ==

= Will WhippleHill help me setup this plugin? =

Of course we will. Feel free to contact either your WhippleHill Account Manager or Project Lead about receiving assistance when setting up the plugin. 


= Can I use this with more then one WordPress installation? =

Currently we only support a connection between one WordPress installation and Podium.

= Why do I need to create two extra 'Super Admin' accounts? =

This is due to how the integration works. When you install the plugin a new Role is created in WordPress called `WhippleHill WordPress Manager`. This Role is a clone of the `Super Admin` Role but has reduced capabilities. One of the removed capabilities is `Plugin` installation and upgrading, so you will need a true `Super Admin` account that is not managed by Podium to do upgrades and installs with.



== Screenshots ==

1. WhippleHill Blog Privacy Settings
2. WhippleHill Integration Settings
3. Create Blog Step One - Podium
4. Create Blog Step Two - Podium
5. Blog Creation Final Step - Podium
6. Edit Blog Settings - Podium
 

== Changelog ==

= 3.4.1 =
Fixed bug with deleted blogs, fixed bug with privacy settings

= 3.4 =
New RPC call to improve WH connections to WordPress

= 3.3.7 =
Fixed a bug with privacy settings in WordPress 3.5

= 2.0 =
Added support for WordPress 3.1+ (This version of the plugin WILL NOT WORK for WordPress 3.0, please continue to use version 1.2.)

= 1.2 = 
Added support for Sub-domain installs.

Added checks for conflicting Plugins.

Fixed install issue for new installs, Podium url was not being saved.


= 1.1.9 = 
Fixed issue with some users having access to more areas then their role normally allowed for

= 1.1.8 =
Fixed Issue SSO url

= 1.1.7 =
Added Screenshots

= 1.1.6 =
Updated addUpdateUsers to account for users not yet linked.

= 1.1.4 =
Fix to rpc.class.php so path is returned correctly

= 1.1.3 =
Options upgrade error fixed

= 1.1.2 =
Update to plugin header file

= 1.1.1 =
Removed Log function calls

= 1.1 = 
Fixed WhippleHill Super Admin error on first login from podium
Allowed true Super Admin's to create another Super Admin

= 1.0.9 =
Fixed typo in wh-int.class.php

= 1.0.8 =
Added option updating when new version is installed.

= 1.0 =
The first release.

== Upgrade Notice ==

= 1.2 =
Update for Sub-domain installs and SSO Links.

= 1.1.9 = 
Security patch for user access controls

= 1.1.8 = 
Fix to SSO login issue please update

= 1.1.6 =
Update to fix issues with adding and deleting users in Podium

= 1.1.4 =
Update to fix issue with Podium Portal links

= 1.1.3 =
Update to plugin Options

= 1.1.2 =
Version number fix 

= 1.1.1 = 
Please update to stop the plugin from creating log files.

= 1.1 = 
Update to WordPress Manager functionality

= 1.0.9 =
If you have 1.0.8 the plugin will not load. Please updated to 1.0.9. Thanks.

= 1.0.8 =
This is the new and is the first to come from WordPress.org.


= 1.0 =

You have to start somewhere.

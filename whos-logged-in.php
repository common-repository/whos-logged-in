<?php

/*
  Plugin Name: Who's Logged In
  Description: Adds a metabox on the Dashboard Home page that displays a list of all users that are currently logged in for Administrators
  Version: 1.3
  Author: Ben HartLenn
  Author URI: bhartlenn@gmail.com
  Text Domain: whos-logged-in
  License: GPL3
  License URI: https://www.gnu.org/licenses/gpl-3.0.html

  The Who's Logged In plugin shows Administrators a list of currently logged in users on the dashboard home page, and it updates the list every 15 seconds!
  Copyright (C) 2020  Ben HartLenn

  This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

  The Who's Logged In plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with The Who's Logged In plugin. If not, see <http://www.gnu.org/licenses/>.

  Please contact the plugin author via email at ~ bhartlenn at gmail dot com ~ for any inquiries or suggestions, thanks!
 */

// Plugin helper function(s)
// Function to get and display a list of currently logged in users
function wli_output_users() {
	// NOTE: version 1.3 update is no longer using option to display logged in users. 
	// now making one query to db for all users with an active session token, which are logged in users, and displaying the results of that
	
	// get list of users from wordpress database that have an active session token
	$wli_logged_in_user_objects = get_users([
		'meta_key' => 'session_tokens',
		'meta_compare' => 'EXISTS',
		'fields' => ['id', 'user_login']
	]);
		// create array of logged in user info from array of WordPress user objects
	$wli_logged_in_users_array = wp_list_pluck($wli_logged_in_user_objects, 'user_login', 'id');
	
	// initialize output variable
	$wli_logged_in_users_output = "";
	
	// if users array is not empty...
  if ( !empty( $wli_logged_in_users_array ) ) {
		// ... start unordered list
    $wli_logged_in_users_output .= "<ul id='wli-logged-in-users'>";
		// // add an li and a link for each user in the array
    foreach ( $wli_logged_in_users_array as $wli_user_id => $wli_user_login ) {
        $wli_logged_in_users_output .= "<li><a href='" . get_edit_user_link( $wli_user_id ) . "'>" . __( $wli_user_login, 'whos-logged-in' ) . "</a></li>";
    }
		// ... end unordered list
    $wli_logged_in_users_output .= "</ul>";
  }
	else {
		$wli_logged_in_users_output .= "No users to display.";
	}
	// return the output, do not echo
	return $wli_logged_in_users_output;
}
// ***** End plugin helper functions section ***** 

// ***** Main Plugin Functionality Section *****
// Setup a settings page for Who's Logged In available at Dashboard >> Settings >> Whos Logged In
add_action( 'admin_menu', 'wli_add_settings_page' );
function wli_add_settings_page() {
	// add_options_page adds our sub menu under Dashboard >> Settings
  add_options_page( 
		'Whos Logged In', 
		'Whos Logged In', 
		'administrator', 
		'whos-logged-in', 
		'wli_render_plugin_settings_page' 
	);
}

// register settings to our plugins settings/options page
add_action( 'admin_init', 'wli_settings_init' );
function wli_settings_init() {
	// register plugin setting that will have an array of option-like key:value pairs stored in the one option
  register_setting( 
		'wli_settings', 
		'wli_settings_general',
		[
			'type' => 'array'
		] 
	);
	
	// add plugin settings section
  add_settings_section(
  	'wli_settings_page_general_section',
		__( 'General Settings', 'whos_logged_in' ), 
		'wli_settings_general_section_callback', 
		'whos-logged-in'
  );
	
	// add checkbox field for turning auto kick users functionality on and off
  add_settings_field(
    'wli_auto_kick_inactive_users', 
		__( 'Auto logout inactive users', 'whos_logged_in' ), 
		'wli_auto_kick_users_field_render', 
		'whos-logged-in', 
		'wli_settings_page_general_section'
  );		
}

// settings section intro text
function wli_settings_general_section_callback() {
	echo '<i>Turning on this option will make the plugin automatically logout <b>ANY</b> users that have left your site, and have not returned for 30 minutes.</i>';
}

// render checkbox field for kicking users automatically
function wli_auto_kick_users_field_render() {
  $general_settings = get_option( 'wli_settings_general' );
  $value = $general_settings['wli_auto_kick_inactive_users'];
  $checked = checked( $value, 1, false );
  ?>
  <input id="wli_auto_kick_inactive_users" type="checkbox" name="wli_settings_general[wli_auto_kick_inactive_users]" value="1" <?= $checked; ?> >   
  <?php
}
	
	
// render the settings page html/php
function wli_render_plugin_settings_page() {
  // check user has capability to manage options before rendering settings page
  if ( !current_user_can( 'manage_options' ) ) {
      return;
  }
	?>
	
  <div class='wli-settings-page wrap'>
	  <h1><?= esc_html( get_admin_page_title() ); ?></h1>
	  <form action='options.php' method='post'>

	  <?php
			//Output nonce, action, and option_page fields for a settings page.
	    settings_fields( 'wli_settings' );
			
			// output the settings sections and fields
	    do_settings_sections( 'whos-logged-in' );
			
			// form submit button
	    submit_button( 'Save Settings' );
	  ?>

	  </form>
  </div>
	
	<?php
}

// when plugin is activated...
register_activation_hook( __FILE__, 'wli_plugin_activation' );
function wli_plugin_activation() {
	// ...default to *NOT* automatically kicking users off the website when plugin is activated
	update_option( 'wli_settings_general', ['wli_auto_kick_inactive_users' => 0] );
}

// remove ongoing list of logged in users when plugin is deactivated for no trace uninstall of plugin
register_deactivation_hook( __FILE__, 'wli_plugin_deactivation' );
function wli_plugin_deactivation() {
	delete_option( 'wli_logged_in_users' ); // delete old unused option from version 1.2 and older
	delete_option( 'wli_settings_general' );
}

// if current user is an administrator add meta box during dashboard setup
add_action( 'wp_dashboard_setup', 'wli_add_metabox' );
function wli_add_metabox() {
	if ( current_user_can( 'administrator' ) ) {
	  add_meta_box( 'wli-metabox', __( 'Who\'s Logged In', 'whos-logged-in' ), 'wli_metabox_ouput', 'dashboard', 'side', 'high' );
	}
}
// output metabox content using helper function for getting and displaying logged in user list
function wli_metabox_ouput() {
	echo "<div id='wli-logged-in-users-wrapper'>";
		echo wli_output_users();
	echo "</div>";
}

// Who's Logged In javascript loading
add_action( 'wp_enqueue_scripts', 'wli_load_scripts' );
add_action( 'admin_enqueue_scripts', 'wli_load_scripts' );
function wli_load_scripts( $hook ) {
	
	// only load script that updates the metabox list of users on the dashboard homepage where the Whos Logged In metabox is displayed
	if( $hook == 'index.php' ) {
		// add script that runs ajax call at 15 second intervals to keep list of logged in users updated in the metabox
	  wp_enqueue_script( 'wli-update-user-list-script', plugin_dir_url( __FILE__ ) . 'js/wli-update-user-list-script.js', [ 'jquery' ] );

	  // add js variables for our ajax call url, and a nonce variable for security
	  wp_localize_script( 'wli-update-user-list-script', 'wli_user_list_js_vars', [
      'ajax_url' => admin_url( 'admin-ajax.php' ), // make sure proper url is used for WordPress Ajax requests
      'secret_nonce' => wp_create_nonce( 'wli_secret_nonce_sauce' ), // Create nonce to secure ajax requests
    ]);
	}

	// only load script for autologout of inactive users if the setting is turned on and saved
	// get autologout users setting
  $general_settings = get_option( 'wli_settings_general' );
  $autologout_setting = $general_settings['wli_auto_kick_inactive_users'];
	// check if autologout setting is turned on, then proceed to...
	if($autologout_setting == 1) {
		// add script that handles autologout functionality
	  wp_enqueue_script( 'wli-logout-script', plugin_dir_url( __FILE__ ) . 'js/wli-logout-script.js', [ 'jquery' ] );

	  // add js variables for our ajax call url, and a nonce variable for security
	  wp_localize_script( 'wli-logout-script', 'wli_logout_js_vars', [
	    'ajax_url' => admin_url( 'admin-ajax.php' ), // make sure proper url is used for WordPress Ajax requests
	    'secret_nonce' => wp_create_nonce( 'wli_secret_nonce_sauce' ), // Create nonce to secure ajax requests
	  ]);
	}
}


// action called by ajax that updates list of logged in users shown on dashboard
add_action( 'wp_ajax_wli_ajax_update_users_list', 'wli_ajax_update_users_list' );
function wli_ajax_update_users_list() {
	// Verify nonce
	if ( !check_ajax_referer( 'wli_secret_nonce_sauce', 'security', false ) )
	    die( 'Permission denied.' );
		
	// jQuery puts output response into div#wli-logged-in-users-wrapper when ajax successful
	echo wli_output_users();

	wp_die();
}

// action called by ajax to automatically log out inactive users
add_action( 'wp_ajax_wli_ajax_logout_users', 'wli_ajax_logout_users' );
function wli_ajax_logout_users() {
	// check if autologout users setting is on or off
  $general_settings = get_option( 'wli_settings_general' );
  $autologout_setting = $general_settings['wli_auto_kick_inactive_users'];
	// if autologout is turned on, then proceed to...
	if($autologout_setting == 1) {	
		// ...get the current user that triggered this action
		$current_user = wp_get_current_user();

		// ...get all sessions for the current user by their ID
		$sessions = WP_Session_Tokens::get_instance($current_user->ID);

		// ...destroy all of their login sessions to log them out
		$sessions->destroy_all();
	}
	// or else send response to let javascript know not to refresh the page on ajax success
	else {
		echo "auto-kick-is-turned-off";
	}	
	// and then die
	wp_die();
}
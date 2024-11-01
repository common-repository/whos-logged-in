jQuery(document).ready(function($){
	
	// function that makes ajax call to log users out after the current browser tab is not in focus for some amount of time
	function wliLogoutUsers() {
    $.post(
      wli_logout_js_vars.ajax_url,
      {
      	action: 'wli_ajax_logout_users',
      	security: wli_logout_js_vars.secret_nonce
      },
      function(response) {
				// if setting to autologout users is turned on, then reload page on ajax success
				if(response != "auto-kick-is-turned-off") {
					window.location.reload();
				}	
      }
    );
	}
	
	// When user leaves current tab/window, then start a 15 minute timer that calls the "wliLogoutUsers" function above to logout users when it's done
	wliTimeout = ""; // fix for console warning: initialize variable in global scope so it is available in both of the below functions locally
	function wliStartLogoutTimer() {
		wliTimeout = window.setTimeout(wliLogoutUsers, 1800000); // 1,800,000ms = 30 minutes
	}
	// When a user comes back to the current tab/window, then clear the 15 minute timer if user so they don't get logged out
	function wliClearLogoutTimer() {
		if(wliTimeout != "") {
			window.clearTimeout(wliTimeout);
		}
	}
	
	// NOTE: using the javascript "blur" event to recognize when a user has left the current tab/window is not incredibly reliable across browsers and devices, but it is the most reliable approach I could find to make the requested feature functional
	window.addEventListener( 'blur', wliStartLogoutTimer );
	// clear logout timer when inactive user focuses back on website
	window.addEventListener( 'focus', wliClearLogoutTimer );
	
});
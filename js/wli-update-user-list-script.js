jQuery(document).ready(function($){
  // every XXXX seconds(1000 == 1 second) make ajax call to update user list
  setInterval(function(){
    $.post(
      wli_user_list_js_vars.ajax_url,
      {
        action: 'wli_ajax_update_users_list',
        security: wli_user_list_js_vars.secret_nonce
      },
      function( response ) {
      	$('#wli-logged-in-users-wrapper').html(response);
      }
    );
  }, 15000); // 15000ms = 15 seconds
});
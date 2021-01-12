<?php

  /**
   * Update user's email if it was changed in PMO.
   */
  add_action('openid-connect-generic-update-user-using-current-claim', function($user, $user_claim) {
    if ($user_claim['email'] != $user->user_email) {
      $args = array(
        'ID'         => $user->ID,
        'user_email' => esc_attr( $user_claim['email'] )
      );
    
      wp_update_user( $args );
    }
  }, 10, 2);

?>
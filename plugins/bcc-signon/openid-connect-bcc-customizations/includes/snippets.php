<?php
  /**
   * Redirect user with only read access to home_url when accessing the admin dashboard.
   */
  add_action('admin_init', function() {
    if ( !is_user_logged_in() ) {
      return null;
    }

    $user_meta = get_userdata( get_current_user_id() );
    $only_read_access = true;

    foreach ($user_meta->allcaps as $cap => $value) {
      if ( $cap != 'read' && ! in_array($cap, $user_meta->roles) ) {
        $only_read_access = false;
      }
    }

    if ( $only_read_access && !defined('DOING_AJAX') ) {
      wp_redirect(home_url());
      exit;
    }
  });

  /**
   * Update user's email if it was changed in PMO.
   */
  add_action('openid-connect-generic-update-user-using-current-claim', function($user, $user_claim) {
    if ( $user_claim['email'] != $user->user_email
        && ! empty( $user_claim['email'] ) ) {
      wp_update_user( array(
        'ID'         => $user->ID,
        'user_email' => esc_attr( $user_claim['email'] )
      ) );
    }
  }, 10, 2);

  /**
   * Add the personId as a claim in the database when an administrator registers a new user,
   * in order to find that WP user based on it's usermeta when that user logs in.
   */
  add_action('user_register', function($user_id) {
    $user_info = get_userdata($user_id);
    add_user_meta(
      $user_id,
      'openid-connect-generic-last-user-claim', 
      array("https://login.bcc.no/claims/personId" => intval($user_info->user_login))
    );
  });

  /**
   * Require the 'church' scope by default.
   * This is needed in order to identify if an user is a local member for the common login.
   */
  add_filter('openid-connect-generic-auth-url', function($url) {
    $parts = parse_url($url);

    if ( isset($parts['query']) ) {
      parse_str($parts['query'], $params); 

      if ( strpos($params['scope'], 'church') === false ) {
        $params['scope'] .= ' church';
      }

      $pieces = array();
      foreach ( $params as $key => $value ) {
        $pieces[] = $key . '=' . rawurlencode($value);
      }

      $parts['query'] = implode('&', $pieces);
    }

    return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . 
      ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . 
      (isset($parts['user']) ? "{$parts['user']}" : '') . 
      (isset($parts['pass']) ? ":{$parts['pass']}" : '') . 
      (isset($parts['user']) ? '@' : '') . 
      (isset($parts['host']) ? "{$parts['host']}" : '') . 
      (isset($parts['port']) ? ":{$parts['port']}" : '') . 
      (isset($parts['path']) ? "{$parts['path']}" : '') . 
      (isset($parts['query']) ? "?{$parts['query']}" : '') . 
      (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
  });
?>
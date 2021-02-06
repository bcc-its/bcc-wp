<?php
    /**
     * Allow logout without confirmation
     */
    add_action('check_admin_referer', 'logout_without_confirm', 10, 2);
    function logout_without_confirm($action, $result) {
        if ( $action == "log-out" && !isset($_GET['_wpnonce']) && isset($_GET['signoutiframe']) ) {
            $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';
            $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));

            header("Location: $location");
            die;
        }
	}

    /* Add script tag for single signout */
    add_action('wp_footer', 'add_signout_script');
    function add_signout_script() {
        $signout_path = wp_make_link_relative(plugins_url('/bcc-signout.php', dirname(__FILE__)));
        $redirect_path = urlencode(home_url('/wp-login.php?action=logout&signoutiframe=1', 'relative'));
        
        echo ('<script type="text/javascript" src="' . get_option('bcc_auth_domain') . 'signout/js" signout-path="' . $signout_path . '?redirectpath=' .   $redirect_path . '"></script>');
    };
?>
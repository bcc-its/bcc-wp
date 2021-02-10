<?php

require_once  "../../../../wp-load.php";

// Log user out and redirect to home page (which would typically result in user being logged in again)
wp_logout();
wp_redirect(get_home_url());

?>
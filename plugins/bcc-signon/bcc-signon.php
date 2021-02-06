<?php

/*
Plugin Name: BCC Signon
Description: Integration to BCC's Login System.
Version: $_PluginVersion_$
Author: BCC IT
*/

require  dirname( __FILE__) . '/openid-connect-bcc-customizations/bcc-signon.php';
require  dirname( __FILE__) . '/openid-connect-generic-client/openid-connect-generic.php';
require dirname( __FILE__)  . '/plugin-update-checker/plugin-update-checker.php';

function plugin_settings_link($links) {
	$oidc_url = get_admin_url() . 'options-general.php?page=openid-connect-generic-settings';
	$oidc_settings_link = '<a href="'.$oidc_url.'">' . 'OIDC Settings' . '</a>';
	$bcc_url = get_admin_url() . 'options-general.php?page=bcc_signon_settings_page';
	$bcc_settings_link = '<a href="'.$bcc_url.'">' . 'BCC Settings' . '</a>';
	array_unshift( $links, $oidc_settings_link );
	array_unshift( $links, $bcc_settings_link );
	return $links;
}
 
function plugin_after_setup_theme() {
     add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_settings_link');
}
add_action ('after_setup_theme', 'plugin_after_setup_theme');

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://developer.bcc.no/api/updates/bcc-signon.json',
     __FILE__,
    'bcc-signon');
?>
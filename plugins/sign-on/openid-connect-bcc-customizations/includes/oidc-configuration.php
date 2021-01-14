<?php

add_filter('openid-connect-generic-default-settings', 'oidc_default_settings');
function oidc_default_settings($settings) {
	$default_settings = array_merge(bcc_settings(), custom_settings());
	$settings = new OpenID_Connect_Generic_Option_Settings('openid_connect_generic_settings', $default_settings);

	return $settings;
}

function bcc_settings() {
	return array(
		// oauth client settings
		'endpoint_login'    => 'https://login.bcc.no/authorize',
		'endpoint_userinfo' => 'https://login.bcc.no/userinfo',
		'endpoint_token'    => 'https://login.bcc.no/oauth/token',
		'endpoint_end_session' => 'https://login.bcc.no/v2/logout',
		// Those following values are used to force the update of endpoints
		'ep_login'    => 'https://login.bcc.no/authorize',
		'ep_userinfo' => 'https://login.bcc.no/userinfo',
		'ep_token'    => 'https://login.bcc.no/oauth/token',
		'ep_end_session' => 'https://login.bcc.no/v2/logout',

		// non-standard settings
		'no_sslverify'    => 0,
		'http_request_timeout' => 5,
		'identity_key'    => 'https://login.bcc.no/claims/personId',
		'nickname_key'    => 'preferred_username',
		'email_format'       => '{email}',
		'displayname_format' => '{given_name} {family_name}',
		'identify_with_username' => true,
		'state_time_limit' => 180,

		// plugin settings
		'link_existing_users' => 1,
		'create_if_does_not_exist' => 1,
		'redirect_user_back' => 1,
		'redirect_on_logout' => 1,
	);
}

function custom_settings() {
	return array(
		// oauth client settings
		'login_type'        => 'auto',
		'client_id'         => '',
		'client_secret'     => '',
		'scope'             => 'email openid profile church',

		// plugin settings
		'enforce_privacy' => 1,
		'protected_urls' => '',
		'unprotected_urls' => '',
		'alternate_redirect_uri' => 0,
		'enable_logging'  => 0,
		'log_limit'       => 100,
	);
}

/**
 * Handle openid connect errors better by redirecting to the 'bccAuthDomain'
 */
add_action( 'wp_authenticate', 'handle_openid_error' );
function handle_openid_error() {
	$error = isset($_GET['login-error']) ? $_GET["login-error"] : '';
	switch ($error){
		case "unknown-error":
			wp_redirect(get_option('bcc_auth_domain') . '?message=consentrejected');
			exit;    
			break;
		case "access-token-expired":
		case "missing-state":
			wp_redirect(home_url());
			exit;    
			break;
		default:
			break;
	}
}

?>
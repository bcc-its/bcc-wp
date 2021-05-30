<?php

class BCC_Login_Settings {

    public $authority;
    public $jwks_uri;
    public $token_endpoint;
    public $userinfo_endpoint;
    public $authorization_endpoint;
    public $end_session_endpoint;
    public $client_id;
    public $client_secret;
    public $scope;
    public $redirect_uri;
    public $create_missing_users;
    public $local_organization_name;
    public $local_organization_claim_type;
}

/**
 * Provides settings
 */
class BCC_Login_Settings_Provider {

    private BCC_Login_Settings $_settings;

        /**
     * List of settings that can be defined by environment variables.
     *
     * @var array<string,string>
     */
    private $environment_variables = array(
        'client_id'                 => 'OIDC_CLIENT_ID',
        'client_secret'             => 'OIDC_CLIENT_SECRET',
        'authorization_endpoint'    => 'OIDC_ENDPOINT_LOGIN_URL',
        'userinfo_endpoint'         => 'OIDC_ENDPOINT_USERINFO_URL',
        'token_endpoint'            => 'OIDC_ENDPOINT_TOKEN_URL',
        'end_session_endpoint'      => 'OIDC_ENDPOINT_LOGOUT_URL',
        'authority'                 => 'OIDC_AUTHORITY',
        'scope'                     => 'OIDC_SCOPE',
        'create_missing_users'      => 'OIDC_CREATE_USERS',
        'local_organization_name'   => 'BCC_WP_LOCAL_ORGANIZATION_NAME'
    );

    function __construct () {
        // Set default settings
        $settings = new BCC_Login_Settings();
        $settings->authority = 'https://login.bcc.no';
        $settings->token_endpoint = 'https://login.bcc.no/oauth/token';
        $settings->authorization_endpoint = 'https://login.bcc.no/authorize';
        $settings->userinfo_endpoint = 'https://login.bcc.no/userinfo';
        $settings->jwks_uri = 'https://login.bcc.no/.well-known/jwks.json';
        $settings->scope = 'email openid profile church';
        $settings->redirect_uri = 'oidc-authorize';
        $settings->create_missing_users = false;
        $settings->local_organization_claim_type = 'https://login.bcc.no/claims/churchName';

        // Set settings from environment variables
        foreach ( $this->environment_variables as $key => $constant ) {
            if ( defined( $constant ) && constant( $constant ) != '' ) {
                $settings->$key = constant( $constant );
            } else {
                $env = getenv( $constant );
                if ( isset($env) && !is_null($env) && $env != '') {
                    $settings->$key = $env;
                }
            }
        }

        // Backwards compatibility with old plugin configuration
        if ( ! isset( $settings->client_id ) ) {
            $old_settings = (array) get_option( 'openid_connect_generic_settings', array () );
            if ( isset( $old_settings['client_id'] ) ) {
                $settings->client_id = $old_settings['client_id'];
            }
            if ( isset( $old_settings['client_secret'] ) ) {
                $settings->client_secret = $old_settings['client_secret'];
            }
        }
        $this->_settings = $settings;
    }

    /**
     * Get signon settings
     *
     * @return Sign-on settings
     */
    public function get_settings() : BCC_Login_Settings {
        return $this->_settings;
    }
}

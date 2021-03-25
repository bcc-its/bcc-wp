<?php

/*
Plugin Name: BCC Wordpress Proxy
Description: Add support for serving pages via the BCC Wordpress Proxy
Version: 1.0
Author: BCC IT
License: GPL2
*/


class BCC_WP_Proxy_Plugin {

    /**
     * Initialize plugin
     */
    static function init_plugin(){
        $plugin = new self();
        register_activation_hook( __FILE__, array( 'BCC_WP_Proxy_Plugin', 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( 'BCC_WP_Proxy_Plugin', 'deactivate_plugin' ) );
    }

    /**
     * Activate plugin hook
     * Called when plugin is activated
     */
    static function activate_plugin() {

    }

    /**
     * Deactivate plugin hook
     * Called when plugin is deactivated
     */
    static function deactivate_plugin() {

    }   

    function __construct() {
        // remove_filter('template_redirect','redirect_canonical');
        // Add init handler
        add_action( 'init', array( $this, 'on_init' ) );

        add_action( 'rest_api_init', function () {
            register_rest_route( 'bcc-wp-proxy/v1', '/users', array(
              'methods' => 'GET',
              'callback' => array($this, 'get_user_info'),
              'permission_callback' => function( WP_REST_Request $request ) {
                    return true;
                },
            ) );
          } );
    }

    function on_init() {
        $userId = $this->authorize_proxy_request();
        if ( $userId > 0 ) {
            if ( is_user_logged_in() ) {
                if ( get_current_user_id() == $userId ) {
                    return;
                } else {
                    wp_clear_auth_cookie();
                }                
            }
            wp_set_current_user( $userId );
            wp_set_auth_cookie( $userId );
            // update_user_caches($user); // unsure if necessary
        }
    }


     /**
     * Returns a summary of users
     */
    function get_user_info( $data ) {

        $this->authorize_api_request();        

        return array_map(function( $user) {
           return array (
             "ID" => $user->ID,
             "Login" => $user->user_login,
             "Email" => $user->user_email,
             "Status" => $user->user_status,
             "Roles" => $user->roles
           );
        }, get_users());
    }
    

    /**
     * Returns configuration value from contants or environment variables
     */
    private function get_config_value( $key ) {
        if ( defined( $key ) && constant( $key ) != '' ) {
            return constant( $key );
        } else {
            $env = getenv($key);                
            if ( isset($env) && !is_null($env) && $env != '') {
                return $env;
            }                
        }
    }

    /**
     * Returns key defined for plugin
     */
    private function get_auth_key() {
        return $this->get_config_value('BCC_WP_PROXY_KEY');
    }


    /**
     * Checks if auth key has been provided in authorization header and returns the userID set by the proxy (if specified)
     */
    function authorize_proxy_request() {
        $header = apache_request_headers();
        if ( isset( $header['X-Wp-Proxy-Key'] )) {
            if ( $header['X-Wp-Proxy-Key'] == $this->get_auth_key() ) {
                if ( isset( $header['X-Wp-Proxy-User-Id'] )) {
                    $userId = $header['X-Wp-Proxy-User-Id'];
                    if ($userId != '' && $userId != '0'){
                        return (int)$userId;
                    }
                    return 0;
                }
                else
                {
                    return 0;
                }
            };
         }
         return 0;
    }

    /**
     * Checks if API key has been provided in authorization header and returns 403 response if not found
     */
    function authorize_api_request() {
        $header = apache_request_headers();
         if ( isset( $header['X-Wp-Proxy-Key'] )) {
            if ( $header['X-Wp-Proxy-Key'] == $this->get_auth_key() ) {
                return true;
            }
         }
         header('HTTP/1.0 403 Forbidden');
        die('You are not allowed to access this file.');
    }

   
}

// Initialize plugin
BCC_WP_Proxy_Plugin::init_plugin();
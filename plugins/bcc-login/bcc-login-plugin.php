<?php

/*
Plugin Name: BCC Login Plugin
Description: Integration to BCC's Login System.
Version: $_PluginVersion_$
Author: BCC IT
License: GPL2
*/

require_once('includes/class-auth-settings.php');
require_once('includes/class-auth-client.php');

class BCC_Login_Plugin {

    /**
     * Initialize plugin
     */
    static function init_plugin(){
        $plugin = new self();
        register_activation_hook( __FILE__, array( 'BCC_Login_Plugin', 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( 'BCC_Login_Plugin', 'deactivate_plugin' ) );
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

    private Auth_Settings $_settings;
    private Auth_Client $_client;

    function __construct() {
        $settings_provider = new Auth_Settings_Provider();
        $this->_settings = $settings_provider->get_settings();
        $this->_client = new Auth_Client($this->_settings);

        // Add init handler
        add_action( 'init', array( $this, 'on_init' ) );

		// Add privacy handlers
		add_action( 'template_redirect', array( $this, 'on_template_redirect' ), 0 );
		add_filter( 'the_content_feed', array( $this, 'filter_the_content_feed' ), 999 );
		add_filter( 'the_excerpt_rss', array( $this, 'filter_the_excerpt_rss' ), 999 );
        add_filter( 'comment_text_rss', array( $this, 'filter_comment_text_rss' ), 999 );

    }

    function on_init(){

    }

    function on_template_redirect(){
        $this->_client->ensure_authenticated();
    }

    function filter_the_content_feed( $content ){
        return $content;
    }

    function filter_the_excerpt_rss( $content ) {
        return $content;
    }

    function filter_comment_text_rss( $content ) {
        return $content;
    }
}

// Initialize plugin
BCC_Login_Plugin::init_plugin();
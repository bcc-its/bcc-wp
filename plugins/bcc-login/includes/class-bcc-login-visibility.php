<?php

class BCC_Login_Visibility {

    private BCC_Login_Plugin $plugin;

    private $default_level = 0;

    private $levels = array(
        'bcc-login-member' => 2,
        'subscriber'       => 1,
    );

    function __construct( BCC_Login_Plugin $plugin) {
        $this->plugin = $plugin;

        add_action( 'init', array( $this, 'on_init' ) );
        add_action( 'added_post_meta', array( $this, 'on_meta_saved' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'on_meta_saved' ), 10, 4 );
        add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
        add_filter( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );
        add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_menu_items' ), 20 );
        add_filter( 'render_block', array( $this, 'on_render_block' ), 10, 2 );
    }

    /**
     * Registers the `bcc_login_visibility` meta for posts and pages.
     */
    function on_init() {
        foreach ( array( 'post', 'page' ) as $post_type ) {
            register_post_meta( $post_type, 'bcc_login_visibility', array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'number',
                'default'      => 0,
            ) );
        }
    }

    /**
     * Prevents the default level from beeing stored in the database.
     *
     * @param int    $mid
     * @param int    $post_id
     * @param string $key
     * @param int    $value
     * @return void
     */
    function on_meta_saved( $mid, $post_id, $key, $value ) {
        if ( $key === 'bcc_login_visibility' && $value == $this->default_level ) {
            delete_post_meta( $post_id, $key );
        }
    }

    function on_admin_enqueue_scripts() {
        $script_path = BCC_LOGIN_PATH . 'build/visibility.asset.php';
        $script_url  = BCC_LOGIN_URL . 'build/visibility.js';

        if ( ! file_exists( $script_path ) ) {
            return;
        }

        $script_asset = require $script_path;

        wp_enqueue_script(
            'bcc-login-visibility',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_add_inline_script(
            'bcc-login-visibility',
            'var bccLoginPostVisibility = ' . json_encode( array(
                'defaultLevel' => $this->default_level,
                'levels'       => $this->levels,
            ) ),
            'before'
        );
    }

    /**
     * @param WP_Query $query
     * @return WP_Query
     */
    function filter_pre_get_posts( $query ) {
        if ( is_admin() || is_super_admin() ) {
            return $query;
        }

        $query->set(
            'meta_query',
            array(
                'relation' => 'OR',
                array(
                    'key'     => 'bcc_login_visibility',
                    'compare' => '<=',
                    'value'   => $this->get_current_user_level(),
                ),
                array(
                    'key'     => 'bcc_login_visibility',
                    'compare' => 'NOT EXISTS',
                ),
            )
        );

        return $query;
    }

	/**
	 * @param WP_Post[] $items
     * @return WP_Post[]
	 */
	function filter_menu_items( $items ) {
        if ( is_admin() || is_super_admin() ) {
            return $items;
        }

        $level   = $this->get_current_user_level();
        $removed = array();

		foreach ( $items as $key => $item ) {
            // Don't render children of removed menu items.
            if ( in_array( $item->menu_item_parent, $removed, true ) ) {
                $removed[] = $item->ID;
                unset( $items[ $key ] );
                continue;
            }

            if ( in_array( $item->object, array( 'page', 'post' ), true ) ) {
                $visibility = (int) get_post_meta( $item->object_id, 'bcc_login_visibility', true );

                if ( $visibility && $visibility > $level ) {
                    $removed[] = $item->ID;
                    unset( $items[ $key ] );
                }
            }
        }

		return $items;
	}

    /**
     * @return int
     */
    private function get_current_user_level() {
        $user  = wp_get_current_user();

        foreach ( $this->levels as $role => $level ) {
            if ( user_can( $user, $role ) ) {
                return $level;
            }
        }

        return 0;
    }

    /**
     * @param string $block_content
     * @param array $block
     * @return string
     */
    function on_render_block( $block_content, $block ) {
        if ( is_admin() || is_super_admin() ) {
            return $block_content;
        }

        if ( isset( $block['attrs']['bccLoginVisibility'] ) ) {
            $visibility = (int) $block['attrs']['bccLoginVisibility'];
            $level      = $this->get_current_user_level();

            if ( $visibility && $visibility > $level ) {
                return '';
            }
        }

        return $block_content;
    }
}

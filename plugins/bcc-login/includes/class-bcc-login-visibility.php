<?php

class BCC_Login_Visibility {

    private Auth_Settings $_settings;

    private $default_level = 0;

    // A mapping of role -> level.
    private $levels = array(
        'bcc-login-member' => 2,
        'subscriber'       => 1,
    );

    private $post_types = array( 'post', 'page' );

    function __construct( Auth_Settings $settings ) {
        $this->_settings = $settings;

        add_action( 'init', array( $this, 'on_init' ) );
        add_action( 'added_post_meta', array( $this, 'on_meta_saved' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'on_meta_saved' ), 10, 4 );
        add_action( 'enqueue_block_editor_assets', array( $this, 'on_block_editor_assets' ) );
        add_filter( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );
        add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_menu_items' ), 20 );
        add_filter( 'render_block', array( $this, 'on_render_block' ), 10, 2 );
    }

    /**
     * Registers the `bcc_login_visibility` meta for posts and pages.
     */
    function on_init() {
        foreach ( $this->post_types as $post_type ) {
            register_post_meta( $post_type, 'bcc_login_visibility', array(
                'show_in_rest' => current_user_can( 'edit_posts' ),
                'single'       => true,
                'type'         => 'number',
                'default'      => $this->default_level,
            ) );
        }
    }

    /**
     * Removes the default level from the database.
     *
     * @param int    $mid
     * @param int    $post_id
     * @param string $key
     * @param int    $value
     * @return void
     */
    function on_meta_saved( $mid, $post_id, $key, $value ) {
        if ( $key == 'bcc_login_visibility' && (int) $value == $this->default_level ) {
            delete_post_meta( $post_id, $key );
        }
    }

    /**
     * Loads the `src/visibility.js` script in Gutenberg.
     */
    function on_block_editor_assets() {
        $script_path    = BCC_LOGIN_PATH . 'build/visibility.asset.php';
        $script_url     = BCC_LOGIN_URL . 'build/visibility.js';
        $scrcipt_handle = 'bcc-login-visibility';

        if ( ! file_exists( $script_path ) ) {
            return;
        }

        $script_asset = require $script_path;

        wp_enqueue_script(
            $scrcipt_handle,
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_add_inline_script(
            $scrcipt_handle,
            'var bccLoginPostVisibility = ' . json_encode( array(
                'defaultLevel' => $this->default_level,
                'levels'       => $this->levels,
            ) ),
            'before'
        );
    }

    /**
     * Filters out posts that the current user shouldn't see. This filter
     * applies to single posts, category lists and REST API results.
     *
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
     * Filters out menu items that the current users shouldn't see.
     *
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

            if ( in_array( $item->object, $this->post_types, true ) ) {
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
     * Checks the `bccLoginVisibility` attribute and hides the block if
     * the current users shouldn't be allowed to see it.
     *
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

    /**
     * Delets all `bcc_login_visibility` values from the database.
     */
    static function on_uninstall() {
        delete_metadata( 'post', 0, 'bcc_login_visibility', '', true );
    }
}

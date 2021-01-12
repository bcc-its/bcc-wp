<?php

class BCC_Signon {
	
	protected $bcc_auth_domain;
	protected $private_newsfeed_link;
	protected $private_newsfeeds;
	protected $bcc_topbar;
	protected $option_name = "bcc-signon-plugin-settings-group";
	protected $options_page_name = "bcc_signon_settings_page";

	public function __construct() {
		$this->bcc_auth_domain = esc_attr( get_option('bcc_auth_domain') );
		if ($this->bcc_auth_domain == "") {
			$this->bcc_auth_domain = "https://auth.bcc.no/";
		 	update_option('bcc_auth_domain', $this->bcc_auth_domain);
		}

		$this->private_newsfeed_link = esc_attr( get_option('private_newsfeed_link') );
		if ($this->private_newsfeed_link == "") {
			$this->private_newsfeed_link = strtolower(str_replace("-","",trim($this->createGUID(), '{}')));
			update_option('private_newsfeed_link', $this->private_newsfeed_link);
		}

		$this->bcc_topbar = get_option('bcc_topbar');

		$this->private_newsfeeds = get_option('private_newsfeeds');

		$this->load_dependencies();
		add_action('admin_menu', array ($this, 'bcc_signon_plugin_create_menu'));
	}

	/**
	 * Helper to create the GUID
	 */
	private function createGUID()
	{
		if (function_exists('com_create_guid'))
		{
			return com_create_guid();
		}
		else
		{
			mt_srand((double)microtime()*10000);
			//optional for php 4.2.0 and up.
			$set_charid = strtoupper(md5(uniqid(rand(), true)));
			$set_hyphen = chr(45);
			// "-"
			$set_uuid = chr(123)
				.substr($set_charid, 0, 8).$set_hyphen
				.substr($set_charid, 8, 4).$set_hyphen
				.substr($set_charid,12, 4).$set_hyphen
				.substr($set_charid,16, 4).$set_hyphen
				.substr($set_charid,20,12)
				.chr(125);
				// "}"
			return $set_uuid;
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/single-signout.php';
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/newsfeed.php';
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/oidc-configuration.php';
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/privacy-settings.php';
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/widgets.php';
		include_once plugin_dir_path( dirname( __FILE__) ) . 'openid-connect-bcc-customizations/includes/signin.php';
	}

	/**
	 * Create the menu item
	 */
	public function bcc_signon_plugin_create_menu() {
		register_setting( $this->option_name, 'bcc_auth_domain' );
		register_setting( $this->option_name, 'private_newsfeeds' );
		register_setting( $this->option_name, 'bcc_topbar' );
		add_options_page('BCC Signon', 'BCC Signon', 'manage_options', $this->options_page_name, array($this, $this->options_page_name));
		add_action( 'admin_init', function(){
			/* Sections */
			add_settings_section( 'oidc', 'OpenId Connect', function(){} , $this->options_page_name);
			add_settings_section( 'newsfeed', 'NewsFeed', function(){} , $this->options_page_name);
			add_settings_section( 'topbar', 'TopBar', function(){} , $this->options_page_name);

			/* Fields */
			add_settings_field('bcc_auth_domain', "BCC Signon URL", array ($this, 'do_text_field'), $this->options_page_name, 'oidc', 
				array('name' => 'bcc_auth_domain', 'value' => $this->bcc_auth_domain, 'readonly' => 1));
			add_settings_field('private_newsfeeds', "Enable Private Newsfeeds", array ($this, 'do_checkbox_field'), $this->options_page_name, 'newsfeed', 
				array('name' => 'private_newsfeeds', 'value' => $this->private_newsfeeds, 
				'description' => 'This makes the newsfeed of your site accessible only via the <code>Private newsfeed link</code> from below.'));
			add_settings_field('private_newsfeed_link', "Private newsfeed link", array ($this, 'do_text_field'), $this->options_page_name, 'newsfeed', 
				array('name' => 'private_newsfeed_link', 'value' => ($this->private_newsfeeds ? get_site_url() . get_private_link_feed() : ''), 'readonly' => 1,
				'description' => 'Please share this URL with BCC to integrate your news into the BCC Portal.'));
			add_settings_field('bcc_topbar', "Enable TopBar", array ($this, 'do_checkbox_field'), $this->options_page_name, 'topbar', 
				array('name' => 'bcc_topbar', 'value' => $this->bcc_topbar, 
				'description' => 'This shows BCCs topbar on your website: read more on developer.bcc.no.'));
		});
	}

	/**
	 * Creates the settings page
	 */
	public function bcc_signon_settings_page() {
		?>
		<div class="wrap">
		<h1>BCC Signon Settings</h1>

		<form method="post" action="options.php">

		<?php settings_fields( $this->option_name); ?>
		<?php do_settings_sections($this->options_page_name ); ?>
		<?php submit_button(); ?>

		</form>
		</div>
		<?php
	}

	/**
	 * Generates a text field in settings page
	 */
	public function do_text_field($args){
		?>
		<input type="text"
			id="<?php echo $args['name']; ?>"
			name="<?php echo $args['name']; ?>"
			class="large-text"
			value="<?php echo $args['value']; ?>" 
			size="65"
			<?php if (isset($args['readonly']) && $args['readonly']) : echo "readonly"; endif; ?>>
		<?php
		$this->do_field_description($args);
	}

	/**
	 * Generates a checkbox field in settings page
	 */
	public function do_checkbox_field($args){
		?>
		<input type="checkbox"
			id="<?php echo $args['name']; ?>"
			name="<?php echo $args['name']; ?>"
			<?php checked($args['value']); ?>
			value="1"
			<?php if (isset($args['readonly']) && $args['readonly']) : echo "readonly onclick='return false;'"; endif; ?>>
		<?php
		$this->do_field_description($args);
	}

	/**
	 * Generate the description for a field
	 */
	public function do_field_description($args) {
		if (isset( $args['description'])):
		?>
		<p class="description">
			<?php print $args['description']; ?>
			<?php if ( isset( $args['example'] ) ) : ?>
				<br/><strong><?php _e( 'Example' ); ?>: </strong>
				<code><?php print $args['example']; ?></code>
			<?php endif; ?>
		</p>
		<?php
		endif;
	}

	/**
	 * Get access_token of logged in user.
	 */
	public static function get_access_token(){
	$user_id = get_current_user_id();
	if ( empty( $user_id ) ){
		return '';
	}
	$tokens = get_user_meta($user_id, 'openid-connect-generic-last-token-response', true);
	if (empty( $tokens )) {
		return '';
	}
	$access_token = $tokens['access_token'];

	return $access_token;
	}
}

$plugin = new BCC_Signon();
?>
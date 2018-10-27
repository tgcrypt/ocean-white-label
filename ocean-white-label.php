<?php
/**
 * Plugin Name:			Ocean White Label
 * Plugin URI:			https://oceanwp.org/extension/ocean-white-label/
 * Description:			A plugin which add a new box in Theme Panel to allow you to replace the OceanWP name by your own branding name.
 * Version:				1.0.5
 * Author:				OceanWP
 * Author URI:			https://oceanwp.org/
 * Requires at least:	4.5.0
 * Tested up to:		4.9.7
 *
 * Text Domain: ocean-white-label
 * Domain Path: /languages/
 *
 * @package Ocean_White_Label
 * @category Core
 * @author OceanWP
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Ocean_White_Label to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Ocean_White_Label
 */
function Ocean_White_Label() {
	return Ocean_White_Label::instance();
} // End Ocean_White_Label()

Ocean_White_Label();

/**
 * Main Ocean_White_Label Class
 *
 * @class Ocean_White_Label
 * @version	1.0.0
 * @since 1.0.0
 * @package	Ocean_White_Label
 */
final class Ocean_White_Label {
	/**
	 * Ocean_White_Label The single instance of Ocean_White_Label.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct( $widget_areas = array() ) {
		$this->token 			= 'ocean-white-label';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.5';

		register_activation_hook( __FILE__, array( $this, 'install' ) );
		register_activation_hook( __FILE__, array( $this, 'reset_setting' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'setup' ) );
		add_action( 'init', array( $this, 'hide_elements' ) );
		add_action( 'init', array( $this, 'updater' ), 1 );

		// Hide Themes section in the customizer as the theme name cannot be edited in it
		if ( true == get_option( 'oceanwp_hide_themes_customizer', false ) ) {
			add_action( 'customize_register', array( $this, 'remove_themes_section' ), 30 );
		}
	}

	/**
	 * Initialize License Updater.
	 * Load Updater initialize.
	 * @return void
	 */
	public function updater() {

		// Plugin Updater Code
		if( class_exists( 'OceanWP_Plugin_Updater' ) ) {
			$license	= new OceanWP_Plugin_Updater( __FILE__, 'White Label', $this->version, 'OceanWP' );
		}
	}

	/**
	 * Main Ocean_White_Label Instance
	 *
	 * Ensures only one instance of Ocean_White_Label is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Ocean_White_Label()
	 * @return Main Ocean_White_Label instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'ocean-white-label', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if OceanWP or a child theme using OceanWP as a parent is active and the extension specific filter returns true.
	 * @return void
	 */
	public function setup() {
		if ( self::show_white_label_box() ) {
			add_action( 'oe_theme_panel_after', array( $this, 'white_label_box' ) );
		}
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_filter( 'ocean_theme_branding', array( $this, 'get_theme_branding_settings' ) );
		add_filter( 'wp_prepare_themes_for_js', array( $this, 'get_theme_branding' ) );
		add_filter( 'update_right_now_text', array( $this, 'dashboard_right_now' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Allow to remove the theme switch in the customizer as the theme name cannot be edited
	 *
	 * @since 1.0.5
	 */
	public static function remove_themes_section( $wp_customize ) {
		$wp_customize->remove_panel( 'themes' );
	}

	/**
	 * Get white label settings.
	 *
	 * @since 1.0.0
	 */
	public static function get_white_label_settings() {

		$branding = array(
			'branding'        			=> get_option( 'oceanwp_theme_branding' ),
			'name'        				=> get_option( 'oceanwp_theme_name' ),
			'author'      				=> get_option( 'oceanwp_theme_author' ),
			'author_url'  				=> get_option( 'oceanwp_theme_author_url' ),
			'description' 				=> get_option( 'oceanwp_theme_description' ),
			'screenshot'  				=> get_option( 'oceanwp_theme_screenshot' ),
			'hide_oceanwp_news'  		=> get_option( 'oceanwp_hide_oceanwp_news', false ),
			'hide_theme_panel_sidebar'  => get_option( 'oceanwp_hide_theme_panel_sidebar', false ),
			'hide_themes_customizer'  	=> get_option( 'oceanwp_hide_themes_customizer', false ),
			'hide_box'  				=> get_option( 'oceanwp_hide_box', false ),
		);

		return apply_filters( 'ocean_white_label_settings', $branding );
	}

	/**
	 * Add the White Label box.
	 *
	 * @since 1.0.0
	 */
	public static function white_label_box() {

		// Only if manage_options attr
		if ( ! current_user_can( 'manage_options' ) ) {
	        return;
	    }

		// Get settings
		$settings = self::get_white_label_settings(); ?>

		<div class="divider clr"></div>

		<div class="oceanwp-bloc oceanwp-brand">
			<h3><?php esc_html_e( 'White Label', 'ocean-white-label' ); ?></h3>
			<div class="content-wrap">
				<form id="oceanwp-brand-form" method="post" action="options.php">
					<?php settings_fields( 'oceanwp_branding' ); ?>

					<div class="field-wrap left">
						<label for="oceanwp-branding"><?php esc_html_e( 'Theme Branding:', 'ocean-white-label' ); ?></label>
						<input type="text" name="oceanwp_branding[branding]" id="oceanwp-branding" value="<?php echo esc_attr( $settings['branding'] ); ?>">
						<p class="desc"><?php esc_html_e( 'This option replace OceanWP in the admin as the OceanWP Settings metabox.', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap right">
						<label for="oceanwp-name"><?php esc_html_e( 'Theme Name:', 'ocean-white-label' ); ?></label>
						<input type="text" name="oceanwp_branding[name]" id="oceanwp-name" value="<?php echo esc_attr( $settings['name'] ); ?>">
						<p class="desc"><?php esc_html_e( 'This option replace the theme name in Appearance > Themes.', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap left">
						<label for="oceanwp-author"><?php esc_html_e( 'Theme Author:', 'ocean-white-label' ); ?></label>
						<input type="text" name="oceanwp_branding[author]" id="oceanwp-author" value="<?php echo esc_attr( $settings['author'] ); ?>">
						<p class="desc"><?php esc_html_e( 'This option replace the theme author in Appearance > Themes.', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap right">
						<label for="oceanwp-author_url"><?php esc_html_e( 'Theme Author URL:', 'ocean-white-label' ); ?></label>
						<input type="text" name="oceanwp_branding[author_url]" id="oceanwp-author_url" value="<?php echo esc_url( $settings['author_url'] ); ?>">
						<p class="desc"><?php esc_html_e( 'This option replace the theme autohr url in Appearance > Themes.', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap clear">
						<label for="oceanwp-description"><?php esc_html_e( 'Theme Description:', 'ocean-white-label' ); ?></label>
						<textarea name="oceanwp_branding[description]" id="oceanwp-description" rows="3"><?php echo esc_attr( $settings['description'] ); ?></textarea>
						<p class="desc"><?php esc_html_e( 'This option replace the theme description in Appearance > Themes.', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap left">
						<label for="oceanwp-screenshot"><?php esc_html_e( 'Theme Screenshot URL:', 'ocean-white-label' ); ?></label>
						<div class="oceanwp-media-live-preview" style="display:none;">
							<?php
							$preview = $settings['screenshot'];
							if ( $preview ) { ?>
								<img src="<?php echo esc_url( $preview ); ?>" alt="<?php esc_html_e( 'Preview Image', 'ocean-white-label' ); ?>" />
							<?php } ?>
						</div>
						<input class="oceanwp-media-input" type="text" name="oceanwp_branding[screenshot]" value="<?php echo esc_url( $settings['screenshot'] ); ?>">
						<input class="oceanwp-media-upload-button button-secondary" type="button" value="<?php esc_html_e( 'Upload', 'ocean-white-label' ); ?>" />
						<a href="#" class="oceanwp-media-remove" style="display:none;"><?php esc_html_e( 'Remove Image', 'ocean-white-label' ); ?></a>
						<p class="desc"><?php esc_html_e( 'This option replace the theme screenshot in Appearance > Themes. Recommended size: 1200x900px', 'ocean-white-label' ); ?></p>
					</div>

					<div class="field-wrap clear">
						<label for="oceanwp-hide-oceanwp-news">
							<input type="checkbox" id="oceanwp-hide-oceanwp-news" name="oceanwp_branding[hide_oceanwp_news]" value="1" <?php checked( '1', $settings['hide_oceanwp_news'] ); ?>>
							<?php esc_html_e( 'Hide the OceanWP News & Updates in the Dashboard', 'ocean-white-label' ); ?>
						</label>
						<label for="oceanwp-hide-theme-panel-sidebar">
							<input type="checkbox" id="oceanwp-hide-theme-panel-sidebar" name="oceanwp_branding[hide_theme_panel_sidebar]" value="1" <?php checked( '1', $settings['hide_theme_panel_sidebar'] ); ?>>
							<?php esc_html_e( 'Hide The Theme Panel Sidebar', 'ocean-white-label' ); ?>
						</label>
						<label for="oceanwp-hide-themes-customizer">
							<input type="checkbox" id="oceanwp-hide-themes-customizer" name="oceanwp_branding[hide_themes_customizer]" value="1" <?php checked( '1', $settings['hide_themes_customizer'] ); ?>>
							<?php esc_html_e( 'Hide The Themes Section in the Customizer', 'ocean-white-label' ); ?>
						</label>
						<label for="oceanwp-hide-box">
							<input type="checkbox" id="oceanwp-hide-box" name="oceanwp_branding[hide_box]" value="1" <?php checked( '1', $settings['hide_box'] ); ?>>
							<?php esc_html_e( 'Hide This Box', 'ocean-white-label' ); ?>
						</label>
						<p class="desc"><?php esc_html_e( 'Check this option to hide this box. Re-activate Ocean White Label to display this box again.', 'ocean-white-label' ); ?></p>
					</div>

					<input type="submit" name="oceanwp_branding_save" id="submit" class="button owp-button" value="<?php esc_attr_e( 'Save Changes', 'ocean-white-label' ); ?>">

					<?php
					// Updated notice
					if ( isset( $_GET['settings-updated'] ) ) {
					    echo '<div class="oceanwp-settings-updated"><p>Settings updated successfully.</p></div>';
					} ?>

					<?php wp_nonce_field( 'oceanwp-white-label', 'oceanwp-white-label-nonce' ); ?>
				</form>
			</div>
			<i class="dashicons dashicons-admin-generic"></i>
		</div>

	<?php
	}

	/**
	 * Register setting.
	 *
	 * @since 1.0.0
	 */
	public function register_setting() {
		register_setting( 'oceanwp_branding', 'oceanwp_branding', array( $this, 'sanitize_white_label_settings' ) ); 
	}

	/**
	 * Sanitize checkbox.
	 *
	 * @since  1.0.0
	 */
	public static function oceanwp_sanitize_checkbox( $input ) {
		return isset( $input ) ? $input : null;
	}

	/**
     * Save setting.
	 *
	 * @since 1.0.0
     */
    public function sanitize_white_label_settings() {

    	if ( ! isset( $_POST['oceanwp-white-label-nonce'] )
    		&& ! wp_verify_nonce( $_POST['oceanwp-white-label-nonce'], 'oceanwp-white-label' ) ) {
    		return;
    	}

        if ( ! isset( $_POST['oceanwp_branding'] ) ) {
			return;
		}

		// Get settings
		$settings = self::get_white_label_settings();

		// Loop
		foreach( $settings as $key => $setting ) {

			if ( in_array( $key, array( 'description' ) ) ) {
				if ( isset( $_POST['oceanwp_branding']['description'] ) ) {
					update_option( 'oceanwp_theme_description', wp_filter_nohtml_kses( wp_unslash( $_POST['oceanwp_branding']['description'] ) ) );
				}
			} else if ( in_array( $key, array( 'hide_oceanwp_news' ) ) ) {
				if ( isset( $_POST['oceanwp_branding']['hide_oceanwp_news'] ) ) {
					update_option( 'oceanwp_hide_oceanwp_news', true );
				} else {
					update_option( 'oceanwp_hide_oceanwp_news', false );
				}
			} else if ( in_array( $key, array( 'hide_theme_panel_sidebar' ) ) ) {
				if ( isset( $_POST['oceanwp_branding']['hide_theme_panel_sidebar'] ) ) {
					update_option( 'oceanwp_hide_theme_panel_sidebar', true );
				} else {
					update_option( 'oceanwp_hide_theme_panel_sidebar', false );
				}
			} else if ( in_array( $key, array( 'hide_themes_customizer' ) ) ) {
				if ( isset( $_POST['oceanwp_branding']['hide_themes_customizer'] ) ) {
					update_option( 'oceanwp_hide_themes_customizer', true );
				} else {
					update_option( 'oceanwp_hide_themes_customizer', false );
				}
			} else if ( in_array( $key, array( 'hide_box' ) ) ) {
				if ( isset( $_POST['oceanwp_branding']['hide_box'] ) ) {
					update_option( 'oceanwp_hide_box', self::oceanwp_sanitize_checkbox( $_POST['oceanwp_branding']['hide_box'] ) );
				}
			} else {
				if ( isset( $_POST['oceanwp_branding'][$key] ) ) {
					update_option( 'oceanwp_theme_'. $key, sanitize_text_field( wp_unslash( $_POST['oceanwp_branding'][$key] ) ) );
				}
			}
		}
 
    }

    /**
     * Hide elements.
	 *
	 * @since 1.0.3
	 */
	public function hide_elements() {
		if ( true == get_option( 'oceanwp_hide_oceanwp_news', false ) ) {
			add_filter( 'oceanwp_news_enabled', '__return_true' );
		}
		if ( true == get_option( 'oceanwp_hide_theme_panel_sidebar', false ) ) {
			add_filter( 'oceanwp_theme_panel_sidebar_enabled', '__return_true' );
		}
	}

    /**
     * Reset the oceanwp_hide_box settng when the plugin is disabled.
	 *
	 * @since 1.0.0
	 */
	public function reset_setting() {
		$hide_box = get_option( 'oceanwp_hide_box', false );
		if ( isset( $hide_box ) && false != $hide_box ) {
			update_option( 'oceanwp_hide_box', false );
		}
	}

	/**
	 * Show white label box.
	 *
	 * @since 1.0.0
	 */
	public static function show_white_label_box() {

		// Default true
		$return = true;

		// If setting checked
		if ( true == get_option( 'oceanwp_hide_box', false ) ) {
			$return = false;
		}

		// Return
		return $return;
	}

	/**
     * Get theme branding settings.
	 *
	 * @since 1.0.0
     */
    public static function get_theme_branding_settings( $return ) {

		if ( $setting = get_option( 'oceanwp_theme_branding' ) ) {
			$return = $setting;
		}

		return $return;
 
    }

	/**
     * Get theme branding.
	 *
	 * @since 1.0.0
     */
    public static function get_theme_branding( $themes ) {

		$key = 'oceanwp';

		if ( isset( $themes[ $key ] ) ) {

			// Get settings
			$theme_data = self::get_white_label_settings();

			// Theme naem
			if ( ! empty( $theme_data['name'] ) ) {

				$themes[ $key ]['name'] = $theme_data['name'];

				foreach ( $themes as $parent_key => $theme ) {
					if ( isset( $theme['parent'] ) && 'OceanWP' == $theme['parent'] ) {
						$themes[ $parent_key ]['parent'] = $theme_data['name'];
					}
				}
			}

			// Theme description
			if ( ! empty( $theme_data['description'] ) ) {
				$themes[ $key ]['description'] = $theme_data['description'];
			}

			// Theme author and author url
			if ( ! empty( $theme_data['author'] ) ) {
				$author_url = empty( $theme_data['author_url'] ) ? '#' : $theme_data['author_url'];
				$themes[ $key ]['author'] = $theme_data['author'];
				$themes[ $key ]['authorAndUri'] = '<a href="' . esc_url( $author_url ) . '">' . $theme_data['author'] . '</a>';
			}

			// Theme screenshot
			if ( ! empty( $theme_data['screenshot'] ) ) {
				$themes[ $key ]['screenshot'] = array( $theme_data['screenshot'] );
			}
		}

		return $themes;
 
    }

	/**
	 * Add the theme name in the At a Glance metabox on the dashboard page
	 *
	 * @since 1.0.0
	 */
	public static function dashboard_right_now( $return ) {

		// Get setting
		$theme_data = self::get_white_label_settings();

		// Add the theme name
		if ( is_admin() && 'OceanWP' == wp_get_theme() && ! empty( $theme_data['name'] ) ) {
			return sprintf( $return, get_bloginfo( 'version', 'display' ), '<a href="themes.php">' . $theme_data['name'] . '</a>' );
		}

		// Return
		return $return;

	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook ) {

		// Only load scripts when needed
		if ( 'toplevel_page_oceanwp-panel' != $hook ) {
			return;
		}

		// CSS
		wp_enqueue_style( 'oceanwp-white-label', plugins_url( '/assets/css/style.min.css', __FILE__ ) );

		// JS
		wp_enqueue_media();
		wp_enqueue_script( 'oceanwp-white-label-uploader', plugins_url( '/assets/js/uploader.min.js', __FILE__ ), array( 'media-upload' ), false, true );

	}

} // End Class

<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Mail Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-mail-sync
Description: Create WordPress posts from CiviCRM Mailings for viewing email in browser.
Author: Christian Wach
Version: 0.2
Author URI: http://haystack.co.uk
Text Domain: civicrm-wp-mail-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set our version here.
define( 'CIVICRM_WP_MAIL_SYNC_VERSION', '0.2' );

// Store reference to this file.
define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE', __FILE__ );

// Store URL to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_URL' ) ) {
	define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_URL', plugin_dir_url( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) );
}

// Store path to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH' ) ) {
	define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH', plugin_dir_path( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) );
}

// Set our debug flag here.
define( 'CIVICRM_WP_MAIL_SYNC_DEBUG', false );



/**
 * CiviCRM WordPress Mail Sync Plugin Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Mail_Sync {

	/**
	 * Admin Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $civicrm The Admin Utilities object.
	 */
	public $admin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civi;

	/**
	 * WordPress Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $wp The WordPress Utilities object.
	 */
	public $wp;



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Init loading process
		$this->initialise();

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Use translation files.
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// Load our Admin utility class.
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-admin.php' );

		// Instantiate.
		$this->admin = new CiviCRM_WP_Mail_Sync_Admin();

		// Load our CiviCRM utility functions class.
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-civi.php' );

		// Initialise.
		$this->civi = new CiviCRM_WP_Mail_Sync_CiviCRM;

		// Load our WordPress utility functions class.
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-wp.php' );

		// Initialise.
		$this->wp = new CiviCRM_WP_Mail_Sync_WordPress;

		// Store references.
		$this->admin->set_references( $this->wp, $this->civi );
		$this->civi->set_references( $this->admin, $this->wp );
		$this->wp->set_references( $this->admin, $this->civi );

		// Fire action.
		do_action( 'civicrm_wp_mail_sync_initialised' );

	}



	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Admin stuff that needs to be done on activation.
		$this->admin->activate();

		// Register CPT.
		$this->wp->register_cpt();

		// Flush.
		flush_rewrite_rules();

	}



	/**
	 * Do stuff on plugin deactivation.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// Admin stuff that needs to be done on deactivation.
		$this->admin->deactivate();

		// Flush.
		flush_rewrite_rules();

	}



	//##########################################################################



	/**
	 * Enable translation.
	 *
	 * A good reference on how to implement translation in WordPress:
	 *
	 * @see http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Load translations.
		load_plugin_textdomain(
			'civicrm-wp-mail-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) ) . '/languages/' // Relative path to files.
		);

	}



	//##########################################################################



	/**
	 * Debugging.
	 *
	 * @since 0.1
	 *
	 * @param array $msg The message.
	 */
	private function _debug( $msg ) {

		// Add to internal array
		$this->messages[] = $msg;

		// Do we want output?
		if ( CIVICRM_WP_MAIL_SYNC_DEBUG ) {
			print_r( $msg );
		}

	}



} // Class ends.



/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.2
 *
 * @return CiviCRM_ACF_Integration $civicrm_wp_mail_sync The plugin reference.
 */
function civicrm_wp_mail_sync() {

	// Store instance in static variable.
	static $civicrm_wp_mail_sync = false;

	// Maybe return instance.
	if ( false === $civicrm_wp_mail_sync ) {
		$civicrm_wp_mail_sync = new CiviCRM_WP_Mail_Sync();
	}

	// --<
	return $civicrm_wp_mail_sync;

}



// Initialise plugin now.
civicrm_wp_mail_sync();

// Activation.
register_activation_hook( __FILE__, [ civicrm_wp_mail_sync(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ civicrm_wp_mail_sync(), 'deactivate' ] );

// Uninstall uses the 'uninstall.php' method.
// See: http://codex.wordpress.org/Function_Reference/register_uninstall_hook




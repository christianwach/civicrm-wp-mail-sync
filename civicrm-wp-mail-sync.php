<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Mail Sync
Description: Create WordPress posts from CiviCRM Mailings for viewing email in browser
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
--------------------------------------------------------------------------------
*/



// set our version here
define( 'CIVICRM_WP_MAIL_SYNC_VERSION', '0.1' );

// store reference to this file
define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE', __FILE__ );

// store URL to this plugin's directory
if ( ! defined( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_URL' ) ) {
	define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_URL', plugin_dir_url( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) );
}

// store PATH to this plugin's directory
if ( ! defined( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH' ) ) {
	define( 'CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH', plugin_dir_path( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) );
}

// set our debug flag here
define( 'CIVICRM_WP_MAIL_SYNC_DEBUG', false );



/*
--------------------------------------------------------------------------------
CiviCRM_WP_Mail_Sync Class
--------------------------------------------------------------------------------
*/

class CiviCRM_WP_Mail_Sync {

	/** 
	 * Properties
	 */
	
	// Admin utilities class
	public $admin;
	
	// CiviCRM utilities class
	public $civi;
	
	// WordPress utilities class
	public $wp;
	
	
	
	/** 
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {
	
		// init loading process
		$this->initialise();
		
		// --<
		return $this;

	}
	
	
	
	/**
	 * Do stuff on plugin init
	 * 
	 * @return void
	 */
	public function initialise() {
		
		// use translation files
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );
		
		// load our Admin utility class
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-admin.php' );
		
		// instantiate
		$this->admin = new CiviCRM_WP_Mail_Sync_Admin();
	
		// load our CiviCRM utility functions class
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-civi.php' );
		
		// initialise
		$this->civi = new CiviCRM_WP_Mail_Sync_CiviCRM;
	
		// load our WordPress utility functions class
		require( CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'civicrm-wp-mail-sync-wp.php' );
		
		// initialise
		$this->wp = new CiviCRM_WP_Mail_Sync_WordPress;
		
		// store references
		$this->admin->set_references( $this->wp, $this->civi );
		$this->civi->set_references( $this->admin, $this->wp );
		$this->wp->set_references( $this->admin, $this->civi );
		
		// fire action
		do_action( 'civicrm_wp_mail_sync_initialised' );
	
	}
	
	
	
	/**
	 * Do stuff on plugin activation
	 * 
	 * @return void
	 */
	public function activate() {
		
		// admin stuff that needs to be done on activation
		$this->admin->activate();
		
		// register CPT
		$this->wp->register_cpt();
		
		// flush
		flush_rewrite_rules();
		
	}
	
	
		
	/**
	 * Do stuff on plugin deactivation
	 * 
	 * @return void
	 */
	public function deactivate() {
		
		// admin stuff that needs to be done on deactivation
		$this->admin->deactivate();
		
		// flush
		flush_rewrite_rules();
		
	}
	
	
		
	//##########################################################################
	
	
	
	/** 
	 * Load translation files
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 * 
	 * @return void
	 */
	public function enable_translation() {
		
		// not used, as there are no translations as yet
		load_plugin_textdomain(
		
			// unique name
			'civicrm-wp-mail-sync', 
			
			// deprecated argument
			false,
			
			// relative path to directory containing translation files
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'

		);
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * @description: debugging
	 * @param array $msg
	 * @return string
	 */
	private function _debug( $msg ) {
		
		// add to internal array
		$this->messages[] = $msg;
		
		// do we want output?
		if ( CIVICRM_WP_MAIL_SYNC_DEBUG ) print_r( $msg );
		
	}
	
	
	
} // class ends






// declare as global
global $civicrm_wp_mail_sync;

// init plugin
$civicrm_wp_mail_sync = new CiviCRM_WP_Mail_Sync;

// activation
register_activation_hook( __FILE__, array( $civicrm_wp_mail_sync, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $civicrm_wp_mail_sync, 'deactivate' ) );






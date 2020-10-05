<?php
/**
 * Admin Class.
 *
 * Handles general plugin admin functionality.
 *
 * @package CiviCRM_WP_Mail_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM WordPress Mail Sync Admin Class
 *
 * A class that encapsulates Admin functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Mail_Sync_Admin {

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
	 * Admin pages reference.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $settings_page The Admin pages reference.
	 */
	public $settings_page;

	/**
	 * Settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings.
	 */
	public $settings = array();



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Init.
		$this->initialise();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.1
	 *
	 * @param object $wp_object Reference to this plugin's WP object.
	 * @param object $civi_object Reference to this plugin's CIviCRM object.
	 */
	public function set_references( $wp_object, $civi_object ) {

		// Store reference to WordPress reference.
		$this->wp = $wp_object;

		// Store reference to CiviCRM object.
		$this->civi = $civi_object;

	}



	/**
	 * Perform activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Kick out if we are re-activating.
		if ( civiwpmailsync_site_option_get( 'civicrm_wp_mail_sync_installed', 'false' ) == 'true' ) {
			return;
		}

		// Store default settings.
		civiwpmailsync_site_option_set( 'civicrm_wp_mail_sync_settings', $this->settings_get_defaults() );

		// Store version.
		civiwpmailsync_site_option_set( 'civicrm_wp_mail_sync_version', CIVICRM_WP_MAIL_SYNC_VERSION );

		// Store installed flag.
		civiwpmailsync_site_option_set( 'civicrm_wp_mail_sync_installed', 'true' );

	}



	/**
	 * Perform deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// We delete our options in uninstall.php

	}



	/**
	 * Initialise.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Load settings array.
		$this->settings = civiwpmailsync_site_option_get( 'civicrm_wp_mail_sync_settings', $this->settings );

		// Is this the back end?
		if ( is_admin() ) {

			// Multisite?
			if ( is_multisite() ) {

				// Add admin page to Network menu.
				add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 30 );

			} else {

				// Add admin page to menu.
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			}

		}

	}



	//##########################################################################



	/**
	 * Add an admin page for this plugin.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// We must be network admin in multisite.
		if ( is_multisite() AND !is_super_admin() ) {
			return false;
		}

		// Check user permissions.
		if ( !current_user_can('manage_options') ) {
			return false;
		}

		// Try and update settings.
		$saved = $this->update_settings();

		// Multisite?
		if ( is_multisite() ) {

			// Add the admin page to the Network Settings menu.
			$page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				'manage_options',
				'civiwpmailsync_admin_page',
				array( $this, 'admin_form' )
			);

		} else {

			// Add the admin page to the Settings menu.
			$page = add_options_page(
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				'manage_options',
				'civiwpmailsync_admin_page',
				array( $this, 'admin_form' )
			);

		}

		// Add styles only on our admin page.
		//add_action( 'admin_print_styles-' . $page, array( $this, 'add_admin_styles' ) );

	}



	/**
	 * Enqueue any styles and scripts needed by our admin page.
	 *
	 * @since 0.1
	 */
	public function add_admin_styles() {

		// Add admin CSS.
		wp_enqueue_style(
			'civiwpmailsync-admin-style',
			CIVICRM_WP_MAIL_SYNC_URL . 'assets/css/admin.css',
			null,
			CIVICRM_WP_MAIL_SYNC_VERSION,
			'all' // Media.
		);

	}



	/**
	 * Update settings supplied by our admin page.
	 *
	 * @since 0.1
	 */
	public function update_settings() {

	 	// Was the form submitted?
		if ( ! isset( $_POST['civiwpmailsync_submit'] ) ) {
			return;
		}

		// Check that we trust the source of the data
		check_admin_referer( 'civiwpmailsync_admin_action', 'civiwpmailsync_nonce' );

		// Debugging switch for admins and network admins - if set, triggers do_debug() below.
		if ( is_super_admin() AND isset( $_POST['civiwpmailsync_debug'] ) ) {
			$settings_debug = absint( $_POST['civiwpmailsync_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) {
				$this->do_debug();
			}
			return;
		}

		// Check for sync option.
		if ( isset( $_POST['civiwpmailsync_sync'] ) ) {
			$settings_sync = absint( $_POST['civiwpmailsync_sync'] );
			$sync = $settings_sync ? 1 : 0;
			if ( $sync ) {
				$this->build_sync();
			}
			return;
		}

	}



	/**
	 * Show our admin page.
	 *
	 * @since 0.1
	 */
	public function admin_form() {

		// We must be network admin in multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-mail-sync' ) );
		}

		// Show message.
		if ( isset( $_GET['updated'] ) AND isset( $_POST['civiwpmailsync_sync'] ) ) {
			echo '<div id="message" class="updated"><p>' .
				sprintf(
					__( 'CiviMail messages synced to WordPress posts. <a href="%s">View message archive</a>.', 'bpwpapers' ),
					get_post_type_archive_link( $this->wp->get_cpt_name() )
				) . '</p></div>';
		}

		// Get sanitised admin page url.
		$url = $this->admin_form_url_get();

		// Open admin page.
		echo '

		<div class="wrap" id="civiwpmailsync_admin_wrapper">

		<div class="icon32" id="icon-options-general"><br/></div>

		<h2>' . __( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ) . '</h2>

		<form method="post" action="' . htmlentities( $url . '&updated=true' ) . '">

		' . wp_nonce_field( 'civiwpmailsync_admin_action', 'civiwpmailsync_nonce', true, false ) . '
		' . wp_referer_field( false ) . '

		';

		// Open div.
		echo '<div id="civiwpmailsync_admin_options">

		';

		// Sync options.
		$this->admin_form_sync_option();

		// Dev options.
		$this->admin_form_dev_option();

		// Close div.
		echo '

		</div>';

		// Show submit button.
		echo '

		<hr>
		<p class="submit">
			<input type="submit" name="civiwpmailsync_submit" value="' . __( 'Submit', 'civicrm-wp-mail-sync' ) . '" class="button-primary" />
		</p>

		';

		// Close form.
		echo '

		</form>

		</div>
		' . "\n\n\n\n";

	}



	/**
	 * Get Sync option.
	 *
	 * @since 0.1
	 */
	public function admin_form_sync_option() {

		// Show sync.
		echo '
		<h3>' . __( 'Sync Existing Mailings to WordPress', 'civicrm-wp-mail-sync' ) . '</h3>

		<p>' . __( 'WARNING: this will probably only work when there are a reasonably small number of mailings. If you have lots of mailings, it would be better to write some kind of chunked update routine yourself. I will upgrade this plugin to do this at some point.', 'civicrm-wp-mail-sync' ) . '</p>

		<table class="form-table">

			<tr>
				<th scope="row">' . __( 'Sync to WordPress', 'civicrm-wp-mail-sync' ) . '</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civiwpmailsync_sync" id="civiwpmailsync_sync" value="1" />
					<label class="civi_wp_member_sync_settings_label" for="civiwpmailsync_sync">' . __( 'Check this to sync existing mailings to WordPress.', 'civiwpmailsync' ) . '</label>
				</td>
			</tr>

		</table>' . "\n\n";

	}




	/**
	 * Get Developer Testing option.
	 *
	 * @since 0.1
	 */
	public function admin_form_dev_option() {

		// Bail if debugging not set.
		if ( CIVICRM_WP_MAIL_SYNC_DEBUG !== true ) {
			return;
		}

		// Show debugger.
		echo '
		<h3>' . __( 'Developer Testing', 'civicrm-wp-mail-sync' ) . '</h3>

		<table class="form-table">

			<tr>
				<th scope="row">' . __( 'Debug', 'civicrm-wp-mail-sync' ) . '</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civiwpmailsync_debug" id="civiwpmailsync_debug" value="1" />
					<label class="civi_wp_member_sync_settings_label" for="civiwpmailsync_debug">' . __( 'Check this to trigger do_debug().', 'civiwpmailsync' ) . '</label>
				</td>
			</tr>

		</table>' . "\n\n";

	}




	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.1
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function admin_form_url_get() {

		// Sanitise admin page url.
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );
		if ( $url_array ) {
			$target_url = htmlentities( $url_array[0] . '&updated=true' );
		}

		// --<
		return $target_url;

	}




	/**
	 * Reset plugin back to starting state.
	 *
	 * @since 0.1
	 */
	public function clear_sync() {

		// Reset posts.
		$this->wp->delete_posts();

		// Clear linkage.
		$this->setting_set( 'linkage', array() );
		$this->settings_save();

	}



	/**
	 * Build plugin to current state.
	 *
	 * @since 0.1
	 */
	public function build_sync() {

		// Get mailings.
		$mailings = $this->civi->get_mailings();

		// Did we get any?
		if (
			$mailings['is_error'] == 0 AND
			isset( $mailings['values'] ) AND
			count( $mailings['values'] ) > 0
		) {

			// Loop through them.
			foreach( $mailings['values'] AS $mailing_id => $mailing ) {

				// Does it have a post?
				if ( $this->get_post_id_by_mailing_id( $mailing_id ) ) {
					continue;
				}

				// Create a post.
				$post_id = $this->wp->create_post_from_mailing( $mailing_id, $mailing );

			}

		}

	}



	/**
	 * General debugging utility.
	 *
	 * @since 0.1
	 */
	public function do_debug() {

		//print_r( array( 'linkage' => $this->setting_get( 'linkage' ) ) ); die();

		// Reset plugin.
		//$this->clear_sync();

		// Rebuild plugin.
		//$this->build_sync();

		// Disabled.
		return;

		/*
		// Get current user.
		$current_user = wp_get_current_user();

		// Get Civi contact ID.
		$contact_id = $this->civi->get_contact_id_by_user_id( $current_user->ID );

		// Get mailings.
		$mailings = $this->civi->get_mailings_by_contact_id( $contact_id );
		print_r( $mailings ); die();
		*/

		// Init $recipients.
		$recipients = array();

		// Get mailings.
		$mailings = $this->civi->get_mailings();

		// Loop through them.
		foreach( $mailings['values'] AS $mailing_id => $mailing ) {

			// Get contacts.
			$contacts = $this->civi->get_contacts_by_mailing_id( $mailing_id );

			// Loop through them.
			foreach( $contacts['values'] AS $mailing_id => $mailing_contact ) {

				// Add to recipients array.
				$recipients[] = $mailing_contact['contact_id'];

			}

			/*
			print_r( array(
				'mailing_id' => $mailing_id,
				'mailing' => $mailing,
				'contacts' => $contacts,
			));
			*/

		}

		// Init mailings per contact.
		$mailings_by_contact = array();

		// Make unique.
		$recipients = array_unique( $recipients );

		// Loop through them.
		foreach( $recipients AS $contact_id ) {

			// Get mailings.
			$mailings_by_contact[$contact_id] = $this->civi->get_mailings_by_contact_id( $contact_id );

		}

		/*
		print_r( array(
			//'mailings' => $mailings,
			'mailings_by_contact' => $mailings_by_contact,
			'recipients' => $recipients,
		)); die();
		*/

	}



	//##########################################################################



	/**
	 * Get default settings values for this plugin.
	 *
	 * @since 0.1
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = array();

		// Init linkage array.
		$settings['linkage'] = array();

		// --<
		return $settings;

	}



	/**
	 * Save array as site option.
	 *
	 * @since 0.1
	 *
	 * @return bool Success or failure
	 */
	public function settings_save() {

		// Save array as site option.
		return civiwpmailsync_site_option_set( 'civicrm_wp_mail_sync_settings', $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name = '' ) {

		// Test for null.
		if ( $setting_name == '' ) {
			die( __( 'You must supply an setting to setting_exists()', 'civicrm-wp-mail-sync' ) );
		}

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Test for null.
		if ( $setting_name == '' ) {
			die( __( 'You must supply an setting to setting_get()', 'civicrm-wp-mail-sync' ) );
		}

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[$setting_name] : $default;

	}



	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $value The value of the setting.
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Test for null.
		if ( $setting_name == '' ) {
			die( __( 'You must supply an setting to setting_set()', 'civicrm-wp-mail-sync' ) );
		}

		// Test for other than string.
		if ( ! is_string( $setting_name ) ) {
			die( __( 'You must supply the setting as a string to setting_set()', 'civicrm-wp-mail-sync' ) );
		}

		// Set setting.
		$this->settings[$setting_name] = $value;

	}



	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name = '' ) {

		// Test for null.
		if ( $setting_name == '' ) {
			die( __( 'You must supply an setting to setting_delete()', 'civicrm-wp-mail-sync' ) );
		}

		// Unset setting.
		unset( $this->settings[$setting_name] );

	}



	//##########################################################################



	/**
	 * Link a WordPress post to a CiviCRM mailing.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numerical ID of the WordPress post.
	 * @param int $mailing_id The numerical ID of the CiviCRM mailing.
	 */
	public function link_post_and_mailing( $post_id, $mailing_id ) {

		// Sanity check incoming values.
		$post_id = absint( $post_id );
		$mailing_id = absint( $mailing_id );

		// Get linkage array.
		$linkage = $this->setting_get( 'linkage' );

		// Add mailing ID to array keyed by post ID.
		$linkage[$post_id] = $mailing_id;

		// Overwrite setting.
		$this->setting_set( 'linkage', $linkage );

		// Save.
		$this->settings_save();

	}



	/**
	 * Unlink a WordPress post from a CiviCRM mailing.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numerical ID of the WordPress post.
	 */
	public function unlink_post_and_mailing( $post_id ) {

		// Sanity check incoming values.
		$post_id = absint( $post_id );

		// Get linkage array.
		$linkage = $this->setting_get( 'linkage' );

		// Remove entry keyed by post ID.
		unset( $linkage[$post_id] );

		// Overwrite setting.
		$this->setting_set( 'linkage', $linkage );

		// Save.
		$this->settings_save();

	}



	/**
	 * Get a WordPress post ID from a CiviCRM mailing ID.
	 *
	 * @since 0.1
	 *
	 * @param int $mailing_id The numerical ID of the CiviCRM mailing.
	 * @return int $post_id The numerical ID of the WordPress post.
	 */
	public function get_post_id_by_mailing_id( $mailing_id ) {

		// Sanity check incoming values.
		$mailing_id = absint( $mailing_id );

		// Get linkage array.
		$linkage = $this->setting_get( 'linkage' );

		// Flip the array.
		$flipped = array_flip( $linkage );

		// Return if it's there.
		if ( isset( $flipped[$mailing_id] ) ) {
			return $flipped[$mailing_id];
		}

		// Fallback.
		return false;

	}



	/**
	 * Get a CiviCRM mailing ID from a WordPress post ID.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numerical ID of the WordPress post.
	 * @return int $mailing_id The numerical ID of the CiviCRM mailing.
	 */
	public function get_mailing_id_by_post_id( $post_id ) {

		// Sanity check incoming values.
		$post_id = absint( $post_id );

		// Get linkage array.
		$linkage = $this->setting_get( 'linkage' );

		// Return if it's there.
		if ( isset( $linkage[$post_id] ) ) {
			return $linkage[$post_id];
		}

		// Fallback.
		return false;

	}



} // Class ends



/*
================================================================================
Globally-available utility functions.
================================================================================
The "site_option" functions below are useful because in multisite, they access
Network Options, while in single-site they access Blog Options.
--------------------------------------------------------------------------------
*/



/**
 * Test existence of a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @return bool $exists Whether or not the option exists.
 */
function civiwpmailsync_site_option_exists( $option_name = '' ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to civiwpmailsync_site_option_exists()', 'civicrm-wp-mail-sync' ) );
	}

	// Test by getting option with unlikely default.
	if ( civiwpmailsync_site_option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
		return false;
	} else {
		return true;
	}

}



/**
 * Return a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @param str $default The default value of the option if it has no value.
 * @return mixed $value the value of the option.
 */
function civiwpmailsync_site_option_get( $option_name = '', $default = false ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to civiwpmailsync_site_option_get()', 'civicrm-wp-mail-sync' ) );
	}

	// Get option.
	return get_site_option( $option_name, $default );

}



/**
 * Set a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @param mixed $value The value to set the option to.
 * @return bool $success If the value of the option was successfully saved.
 */
function civiwpmailsync_site_option_set( $option_name = '', $value = '' ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to civiwpmailsync_site_option_set()', 'civicrm-wp-mail-sync' ) );
	}

	// Set option.
	return update_site_option( $option_name, $value );

}




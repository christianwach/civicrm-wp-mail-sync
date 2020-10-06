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
	 * Plugin object.
	 *
	 * @since 0.2
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * WordPress Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $wp The WordPress Utilities object.
	 */
	public $wp;

	/**
	 * Settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings.
	 */
	public $settings = [];

	/**
	 * Settings page reference.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $settings_page The settings page reference.
	 */
	public $settings_page;



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise on "civicrm_wp_mail_sync_initialised".
		add_action( 'civicrm_wp_mail_sync_initialised', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Store references to other objects.
		$this->civicrm = $this->plugin->civicrm;
		$this->wp = $this->plugin->wp;

		// Load settings array.
		$this->settings = $this->site_option_get( 'civicrm_wp_mail_sync_settings', $this->settings );

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.2
		 */
		do_action( 'civicrm_wp_mail_sync_admin_initialised' );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.2
	 */
	public function register_hooks() {

		// Add admin page to menu.
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'admin_menu' ], 30 );
		} else {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		}

	}



	/**
	 * Perform activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Bail if we are re-activating.
		if ( $this->site_option_get( 'civicrm_wp_mail_sync_installed', 'false' ) == 'true' ) {
			return;
		}

		// Store default settings.
		$this->site_option_set( 'civicrm_wp_mail_sync_settings', $this->settings_get_defaults() );

		// Store version.
		$this->site_option_set( 'civicrm_wp_mail_sync_version', CIVICRM_WP_MAIL_SYNC_VERSION );

		// Store installed flag.
		$this->site_option_set( 'civicrm_wp_mail_sync_installed', 'true' );

	}



	/**
	 * Perform deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// We delete our options in uninstall.php

	}



	// -------------------------------------------------------------------------



	/**
	 * Add an admin page for this plugin.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// We must be network admin in multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Try and update settings.
		$saved = $this->settings_update_router();

		// Multisite?
		if ( is_multisite() ) {

			// Add the admin page to the "Network Settings" menu.
			$this->settings_page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				'manage_options',
				'civiwpmailsync_admin_page',
				[ $this, 'admin_form' ]
			);

		} else {

			// Add the admin page to the "Settings" menu.
			$this->settings_page = add_options_page(
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				__( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ),
				'manage_options',
				'civiwpmailsync_admin_page',
				[ $this, 'admin_form' ]
			);

		}

		/*
		 * Add styles and scripts only on our settings page.
		 *
		 * @see wp-admin/admin-header.php
		 */
		//add_action( 'admin_print_styles-' . $this->settings_page, [ $this, 'admin_styles' ] );
		//add_action( 'admin_print_scripts-' . $this->settings_page, [ $this, 'admin_scripts' ] );
		//add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_head' ], 50 );

	}



	/**
	 * Enqueue any styles needed by our admin page.
	 *
	 * @since 0.1
	 */
	public function admin_styles() {

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
	 * Show our admin page.
	 *
	 * @since 0.1
	 */
	public function admin_form() {

		// We must be network admin in multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-mail-sync' ) );
		}

		// Maybe show message.
		if ( isset( $_GET['updated'] ) AND isset( $_POST['civiwpmailsync_sync'] ) ) {
			$messages = '<div id="message" class="updated"><p>' . sprintf(
				__( 'CiviMail messages synced to WordPress posts. <a href="%s">View message archive</a>.', 'civicrm-wp-mail-sync' ),
				get_post_type_archive_link( $this->wp->get_cpt_name() )
			) . '</p></div>';
		}

		// Include template.
		include CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'assets/templates/wordpress/settings.php';

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




	// -------------------------------------------------------------------------



	/**
	 * Update settings as requested by our admin page.
	 *
	 * @since 0.1
	 */
	public function settings_update_router() {

	 	// Bail if the form was not submitted.
		if ( ! isset( $_POST['civiwpmailsync_settings_submit'] ) ) {
			return;
		}

		// Check that we trust the source of the data
		check_admin_referer( 'civiwpmailsync_settings_action', 'civiwpmailsync_settings_nonce' );

		// Check for sync option.
		if ( isset( $_POST['civiwpmailsync_sync'] ) ) {
			$settings_sync = absint( $_POST['civiwpmailsync_sync'] );
			$sync = $settings_sync ? 1 : 0;
			if ( $sync ) {
				$this->mailings_sync_to_wp();
			}
			return;
		}

		// Check for clear option.
		if ( isset( $_POST['civiwpmailsync_clear'] ) ) {
			$settings_clear = absint( $_POST['civiwpmailsync_clear'] );
			$clear = $settings_clear ? 1 : 0;
			if ( $clear ) {
				$this->mailings_delete_from_wp();
			}
			return;
		}

	}



	/**
	 * Sync CiviCRM Mailings to WordPress.
	 *
	 * @since 0.1
	 */
	public function mailings_sync_to_wp() {

		// Get mailings.
		$mailings = $this->civicrm->mailings_get_all();

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
	 * Delete synced CiviCRM Mailings from WordPress.
	 *
	 * This resets plugin back to its starting state.
	 *
	 * @since 0.1
	 */
	public function mailings_delete_from_wp() {

		// Reset posts.
		$this->wp->delete_posts();

		// Clear linkage.
		$this->setting_set( 'linkage', [] );
		$this->settings_save();

	}



	// -------------------------------------------------------------------------



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



	// -------------------------------------------------------------------------



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



	// -------------------------------------------------------------------------



	/**
	 * Get default settings values for this plugin.
	 *
	 * @since 0.1
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

		// Init linkage array.
		$settings['linkage'] = [];

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
		return $this->site_option_set( 'civicrm_wp_mail_sync_settings', $this->settings );

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

		// Unset setting.
		unset( $this->settings[$setting_name] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Test existence of a specified site option.
	 *
	 * @since 0.2
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function site_option_exists( $option_name = '' ) {

		// Define an unlikely string.
		$unlikely = 'fenfgehgefdfdjgrkj';

		// Test by getting option with unlikely default.
		if ( $this->site_option_get( $option_name, $unlikely ) == $unlikely ) {
			return false;
		} else {
			return true;
		}

	}



	/**
	 * Return a value for a specified site option.
	 *
	 * @since 0.2
	 *
	 * @param str $option_name The name of the option.
	 * @param str $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function site_option_get( $option_name = '', $default = false ) {

		// Get option.
		return get_site_option( $option_name, $default );

	}



	/**
	 * Set a value for a specified site option.
	 *
	 * @since 0.2
	 *
	 * @param str $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success If the value of the option was successfully saved.
	 */
	public function site_option_set( $option_name = '', $value = '' ) {

		// Set option.
		return update_site_option( $option_name, $value );

	}



} // Class ends.





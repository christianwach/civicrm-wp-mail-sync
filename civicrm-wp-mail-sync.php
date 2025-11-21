<?php
/**
 * CiviCRM WordPress Mail Sync
 *
 * Plugin Name:       CiviCRM WordPress Mail Sync
 * Description:       Create WordPress Posts from CiviCRM Mailings to allow your Users to view their emails in WordPress.
 * Plugin URI:        https://github.com/christianwach/civicrm-wp-mail-sync
 * GitHub Plugin URI: https://github.com/christianwach/civicrm-wp-mail-sync
 * Version:           0.2.1
 * Author:            Christian Wach
 * Author URI:        https://haystack.co.uk
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Text Domain:       civicrm-wp-mail-sync
 * Domain Path:       /languages
 *
 * @package CiviCRM_WP_Mail_Sync
 * @link    https://github.com/christianwach/civicrm-wp-profile-sync
 * @license GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'CIVICRM_WP_MAIL_SYNC_VERSION', '0.2.1' );

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
	 * @var CiviCRM_WP_Mail_Sync_Admin
	 */
	public $admin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var CiviCRM_WP_Mail_Sync_CiviCRM
	 */
	public $civicrm;

	/**
	 * WordPress Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var CiviCRM_WP_Mail_Sync_WordPress
	 */
	public $wp;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise on "plugins_loaded".
		add_action( 'plugins_loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bail if CiviCRM isn't found.
		if ( ! function_exists( 'civi_wp' ) ) {
			$done = true;
			return;
		}

		// Bootsrap plugin.
		$this->enable_translation();
		$this->include_files();
		$this->setup_objects();

		/**
		 * Broadcast that this plugin is now initialised.
		 *
		 * @since 0.1
		 */
		do_action( 'civicrm_wp_mail_sync_initialised' );

		// We're done.
		$done = true;

	}

	/**
	 * Include files.
	 *
	 * @since 0.2
	 */
	public function include_files() {

		// Load class files.
		include CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'includes/class-admin.php';
		include CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'includes/class-civicrm.php';
		include CIVICRM_WP_MAIL_SYNC_PLUGIN_PATH . 'includes/class-wordpress.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.2
	 */
	public function setup_objects() {

		// Instantiate objects.
		$this->admin   = new CiviCRM_WP_Mail_Sync_Admin( $this );
		$this->civicrm = new CiviCRM_WP_Mail_Sync_CiviCRM( $this );
		$this->wp      = new CiviCRM_WP_Mail_Sync_WordPress( $this );

	}

	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Maybe init.
		$this->initialise();

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

		// Maybe init.
		$this->initialise();

		// Admin stuff that needs to be done on deactivation.
		$this->admin->deactivate();

		// Flush.
		flush_rewrite_rules();

	}

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
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'civicrm-wp-mail-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( CIVICRM_WP_MAIL_SYNC_PLUGIN_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	/**
	 * Write to the error log.
	 *
	 * @since 0.2.1
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( WP_DEBUG === false ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

}

/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.2
 *
 * @return CiviCRM_WP_Mail_Sync $civicrm_wp_mail_sync The plugin reference.
 */
function civicrm_wp_mail_sync() {

	// Store instance in static variable.
	static $plugin = false;

	// Maybe return instance.
	if ( false === $plugin ) {
		$plugin = new CiviCRM_WP_Mail_Sync();
	}

	// --<
	return $plugin;

}

// Initialise plugin now.
civicrm_wp_mail_sync();

// Activation.
register_activation_hook( __FILE__, [ civicrm_wp_mail_sync(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ civicrm_wp_mail_sync(), 'deactivate' ] );

/*
 * Uninstall uses the 'uninstall.php' method.
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */

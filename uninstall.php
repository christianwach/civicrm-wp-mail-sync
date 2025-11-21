<?php
/**
 * CiviCRM WordPress Mail Sync Uninstaller
 *
 * @package CiviCRM_WP_Mail_Sync
 */

// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete site options. Falls back to options in single-site.

// Delete settings.
delete_site_option( 'civicrm_wp_mail_sync_settings' );

// Delete version.
delete_site_option( 'civicrm_wp_mail_sync_version' );

// Delete installed flag.
delete_site_option( 'civicrm_wp_mail_sync_installed' );

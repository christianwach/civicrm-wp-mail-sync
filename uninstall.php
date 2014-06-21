<?php /* 
--------------------------------------------------------------------------------
CiviCRM WordPress Mail Sync Uninstaller
--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



// delete site options (falls back to options in single-site)

// delete settings
delete_site_option( 'civicrm_wp_mail_sync_settings' );

// delete version
delete_site_option( 'civicrm_wp_mail_sync_version' );

// delete installed flag
delete_site_option( 'civicrm_wp_mail_sync_installed' );

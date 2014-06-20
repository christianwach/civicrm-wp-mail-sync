<?php
/*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Mail Sync
Description: Create WordPress posts from CiviCRM Mailings for viewing email in browser
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
--------------------------------------------------------------------------------
*/



// set our debug flag here
define( 'CIVICRM_WP_MAIL_SYNC_DEBUG', false );

// set our version here
define( 'CIVICRM_WP_MAIL_SYNC_VERSION', '0.1' );



/*
--------------------------------------------------------------------------------
CiviCRM_WP_Mail_Sync Class
--------------------------------------------------------------------------------
*/

class CiviCRM_WP_Mail_Sync {

	/** 
	 * properties
	 */
	
	// error messages
	public $messages = array();
	
	
	
	/** 
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
		// set directional flag because the primary email is updated before the contact
		add_action( 'civicrm_pre', array( $this, 'template_before_save' ), 10, 4 );
		
		// sync a WP user when a CiviCRM contact is updated
		add_action( 'civicrm_post', array( $this, 'template_after_save' ), 10, 4 );
		
		// --<
		return $this;

	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Create a WordPress post from an email template
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return nothing
	 */
	public function template_before_save( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Mailing' ) return;
		
	}
	
	
	
	/**
	 * Holding...
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return nothing
	 */
	public function template_after_save( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Mailing' ) return;
		
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






/**
 * @description: initialise our plugin after CiviCRM initialises
 */
function civicrm_wp_mail_sync_init() {

	// declare as global
	global $civicrm_wp_mail_sync;
	
	// init plugin
	$civicrm_wp_mail_sync = new CiviCRM_WP_Mail_Sync;
	
}

// add action for plugin init
add_action( 'civicrm_instance_loaded', 'civicrm_wp_mail_sync_init' );






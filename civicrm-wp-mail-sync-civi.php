<?php /*
--------------------------------------------------------------------------------
CiviCRM_WP_Mail_Sync_CiviCRM Class
--------------------------------------------------------------------------------

Notes:

$page = new CRM_Mailing_Page_View();
$value = $page->run($mailing->id, NULL, FALSE);

--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating CiviCRM functionality
 */
class CiviCRM_WP_Mail_Sync_CiviCRM {

	/** 
	 * Properties
	 */
	
	// Admin utilities
	public $admin;
	
	// WordPress utilities
	public $wp;
	
	
	
	/** 
	 * Initialises this object
	 * 
	 * @return object
	 */
	function __construct() {
	
		// add actions for plugin init on CiviCRM init
		add_action( 'civicrm_instance_loaded', array( $this, 'register_hooks' ) );
				
		// --<
		return $this;

	}
	
	
	
	/**
	 * Set references to other objects
	 * 
	 * @param object $admin_object Reference to this plugin's Admin object
	 * @param object $wp_object Reference to this plugin's WordPress object
	 * @return void
	 */
	public function set_references( &$admin_object, &$wp_object ) {
	
		// store reference to Admin object
		$this->admin = $admin_object;
		
		// store reference to WordPress object
		$this->wp = $wp_object;
		
	}
	
	
		
	/**
	 * Register hooks on plugin init
	 * 
	 * @return void
	 */
	public function register_hooks() {
		
		// intercept Mailing before save
		add_action( 'civicrm_pre', array( $this, 'template_before_save' ), 10, 4 );
		
		// intercept Mailing after save
		add_action( 'civicrm_post', array( $this, 'template_after_save' ), 10, 4 );
		
		// intercept Mailing email before send
		add_action( 'civicrm_alterMailParams', array( $this, 'message_before_send' ), 10, 2 );
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Create a WordPress post from an email template
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function template_before_save( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Mailing' ) return;
		
		///*
		print_r( array( 
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		)); //die();
		//*/
	
	}
	
	
	
	/**
	 * Holding...
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function template_after_save( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Mailing' ) return;
		
		///*
		print_r( array( 
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		)); //die();
		//*/
	
	}
	
	
	
	/**
	 * Holding...
	 *
	 * @param array $params The message parameters
	 * @param string $context The message context
	 * @return void
	 */
	public function message_before_send( $params, $context = null ) {
		
		// target our context
		if ( $context != 'civimail' ) return;
		
		///*
		print_r( array( 
			'params' => $params,
			'context' => $context,
		)); //die();
		//*/
	
	}
	
	
	
} // class ends







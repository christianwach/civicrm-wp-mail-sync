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
	
	
	
	/**
	 * Test if CiviCRM plugin is active
	 *
	 * @return bool
	 */
	public function is_active() {
		
		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return false;
		
		// try and init CiviCRM
		return civi_wp()->initialize();
		
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
		
		// do not sync on send
		if ( isset( $objectRef['now'] ) AND $objectRef['now'] == 1 ) return;
		
		// make sure we have a message template
		if ( ! isset( $objectRef['body_html'] ) AND ! isset( $objectRef['body_text'] ) ) return;
		
		/*
		print_r( array( 
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		)); //die();
		*/
		
		// create a post from this data
		$this->wp->create_post_from_mailing( $objectId, $objectRef );
		
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
		
		// disabled
		return;
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Mailing' ) return;
		
		// do not sync on send
		if ( isset( $objectRef['now'] ) AND $objectRef['now'] == 1 ) return;
		
		// make sure we have a message template
		if ( ! isset( $objectRef['body_html'] ) AND ! isset( $objectRef['body_text'] ) ) return;
		
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
	 * Intercept every email before it is sent
	 *
	 * @param array $params The message parameters
	 * @param string $context The message context
	 * @return void
	 */
	public function message_before_send( $params, $context = null ) {
		
		// target our context
		if ( $context != 'civimail' ) return;
		
		// disabled
		return;
		
		///*
		print_r( array( 
			'params' => $params,
			'context' => $context,
		)); //die();
		//*/
	
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Get all Civi Mailings
	 *
	 * @return array $mailings The Civi API array containg the mailings
	 */
	public function get_mailings() {
	
		// init CiviCRM or die
		if ( ! $this->is_active() ) return false;
		
		// construct array
		$params = array( 
			'version' => 3,
			'options' => array( 
				'limit' => '100000',
			),
		);
		
		// call API
		$mailings = civicrm_api( 'mailing', 'get', $params );
		
		// --<
		return $mailings;
		
	}
	
	
	
	/**
	 * Get all Civi Mailings for a Contact
	 *
	 * @param int $contact_id The numerical ID of the Civi contact
	 * @return array $mailings The Civi API array containg the mailings
	 */
	public function get_mailings_by_contact_id( $contact_id ) {
	
		// init CiviCRM or die
		if ( ! $this->is_active() ) return false;
		
		// construct array
		$params = array( 
			'version' => 3,
			'contact_id' => $contact_id,
			/*
			//'type' => 'Delivered',
			'options' => array( 
				'Delivered' => 'Delivered',
				'Bounced' => 'Bounced',
				//'limit' => '100000',
			),
			*/
		);
		
		// call API
		$mailings = civicrm_api( 'mailing_contact', 'get', $params );
		
		// --<
		return $mailings;
		
	}
	
	
	
	/**
	 * Get all Civi Contacts for a Mailing
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing
	 * @return array $contacts The Civi API array containg the contacts
	 */
	public function get_contacts_by_mailing_id( $mailing_id ) {
	
		// init CiviCRM or die
		if ( ! $this->is_active() ) return false;
		
		// construct array
		$params = array( 
			'version' => 3,
			'id' => $mailing_id,
			'options' => array( 
				'limit' => '100000',
			),
		);
		
		// call API
		$contacts = civicrm_api( 'mailing_recipients', 'get', $params );
		
		// --<
		return $contacts;
		
	}
	
	
	
	/**
	 * Get CiviCRM contact ID by WordPress user ID
	 * 
	 * @param int $user_id The numeric ID of the WordPress user
	 * @return int $contact_id The numeric ID of the CiviCRM Contact
	 */
	public function get_contact_id_by_user_id( $user_id ) {
		
		// init or die
		if ( ! $this->is_active() ) return;
		
		// make sure Civi file is included
		require_once 'CRM/Core/BAO/UFMatch.php';
			
		// do initial search
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $user_id );
		
		// return it if we get one
		if ( $contact_id ) return $contact_id;
		
		// fallback
		return false;
		
	}
	
	
		
} // class ends







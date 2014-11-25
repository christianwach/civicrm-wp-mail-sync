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
		//add_action( 'civicrm_post', array( $this, 'template_after_save' ), 10, 4 );

		// intercept Mailing email before send
		//add_action( 'civicrm_alterMailParams', array( $this, 'message_before_send' ), 10, 2 );

		// intercept token values
		//add_filter( 'civicrm_tokenValues', array( $this, 'token_values' ), 10, 4 );

		// intercept tokens
		//add_filter( 'civicrm_tokens', array( $this, 'tokens' ), 10, 1 );

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
	public function template_before_save( $op, $objectName, $objectId, &$objectRef ) {

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
		$post_id = $this->wp->create_post_from_mailing( $objectId, $objectRef );

		// make sure we created a post successfully
		if ( ! $post_id ) return;

		// get permalink
		$permalink = get_permalink( $post_id );

		// append to plain text, if present
		if ( isset( $objectRef['body_text'] ) ) {

			// get link for insertion
			$plain_text = $this->get_mail_url_plain( $post_id );

			// get possible position of an existing instance of a link
			$offset = strpos( $objectRef['body_text'], $plain_text );

			// do we already have an inserted link? (happens in re-used mailings)
			if ( false !== $offset ) {

				// strip everything from that point to the end
				$objectRef['body_text'] = substr_replace( $objectRef['body_text'], '', $offset );

			} else {

				// give new link some space
				$objectRef['body_text'] .= "\r\n\r\n";

			}

			// append to text and insert permalink
			$objectRef['body_text'] .= $plain_text . "\r\n" . $permalink . "\r\n";

		}

		// apply to html, if present
		if ( isset( $objectRef['body_html'] ) ) {

			// get link for insertion
			$html = $this->get_mail_url_html( $permalink, $post_id );

			// wrap this in a div
			$html = '<div class="civicrm_wp_mail_sync_url">' . $html . '</div>';

			// do we already have an inserted link (happens in re-used mailings)
			if ( false !== strpos( $objectRef['body_html'], '<!--civicrm-wp-mail-sync-url-->' ) ) {

				// yes, replace what's between the html comments
				$objectRef['body_html'] = preg_replace(
					'#<!--civicrm-wp-mail-sync-url-->(.*?)<!--civicrm-wp-mail-sync-url-->#s',
					$html, // replacement
					$objectRef['body_html'] // source
				);

			} else {

				// wrap this with two comments (so we can tell if this is a reused template above)
				$html = '<!--civicrm-wp-mail-sync-url-->' . $html . '<!--/civicrm-wp-mail-syncurl-->';

				// append to HTML
				$objectRef['body_html'] .= "\r\n\r\n" . $html;

			}

		}

	}



	/**
	 * Intercept template after it has been saved (unused)
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



	/**
	 * Intercept token values
	 *
	 * @param array $values The token values
	 * @param array $contact_id An array of numerical IDs of the Civi contacts
	 * @param int $job_id The job ID
	 * @param array $tokens The tokens whose values need replacing
	 * @return void
	 */
	public function token_values( &$values, $contact_ids, $job_id = null, $tokens = array() ) {

		// disabled
		return;

		///*
		print_r( array(
			'values' => $values,
			'contact_ids' => $contact_ids,
			'job_id' => $job_id,
			'tokens' => $tokens,
		)); die();
		//*/

		// target our token
		if ( ! isset( $tokens['mailing']['viewUrl'] ) ) return;

		// replace view url token?

	}



	/**
	 * Intercept tokens
	 *
	 * @param array $tokens The tokens
	 * @return void
	 */
	public function tokens( &$tokens ) {

		// disabled
		return;

		// unset view url token?

		///*
		print_r( array(
			'tokens' => $tokens,
		)); die();
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
	 * Check if a Civi Contact was a recipient of a Mailing
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing
	 * @param int $contact_id The numerical ID of the Civi contact
	 * @return bool True if contact was a recipient of this mailing, false otherwise
	 */
	public function is_recipient( $mailing_id, $contact_id ) {

		// init CiviCRM or die
		if ( ! $this->is_active() ) return false;

		// get mailings
		$mailings = $this->get_mailings_by_contact_id( $contact_id );

		/*
		print_r( array(
			'mailing_id' => $mailing_id,
			'contact_id' => $contact_id,
			'mailings' => $mailings,
		) );
		*/

		// did we get any?
		if ( count( $mailings['values'] ) > 0 ) {

			// get recipient IDs array
			$mailing_ids = array_keys( $mailings['values'] );

			// is our mailing in this array?
			if ( in_array( $mailing_id, $mailing_ids ) ) return true;

		}

		// fallback
		return false;

	}



	//##########################################################################



	/**
	 * Get a personalised message
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing
	 * @param int $contact_id The numerical ID of the Civi contact
	 * @param str $type Either 'html' or 'text' (default 'html')
	 * @return str $message The formatted message
	 */
	public function message_render( $mailing_id, $contact_id = null, $type = 'html' ) {

		// init CiviCRM or die
		if ( ! $this->is_active() ) return false;

		// if we don't have a passed contact, use logged in user
		if ( is_null( $contact_id ) AND is_user_logged_in() ) {

			// get current user
			$current_user = wp_get_current_user();

			// get Civi contact ID
			$contact_id = $this->get_contact_id_by_user_id( $current_user->ID );

		}

		/*
		// replace tokens (fails due to buggy permissions)
		$page = new CRM_Mailing_Page_View();
		$value = $page->run( $mailing_id, $contact_id, FALSE );
		*/

		// the following copied from CRM_Mailing_Page_Preview
		// @see CRM/Mailing/Page/Preview.php

		// init mailing
		$mailing = new CRM_Mailing_BAO_Mailing();

		// set ID
		$mailing->id = $mailing_id;

		// try and find it
		if ( ! $mailing->find( true ) ) {

			// say what?
			$text = __( '<p>Sorry, this email has not been found.</p>', 'civicrm-wp-mail-sync' );

			// allow overrides
			$text = apply_filters( 'civicrm_wp_mail_sync_email_render_not_found', $text, $mailing_id );

			// --<
			return $text;

		}

		// what's the status of this mailing?
		if ( ! $this->is_email_viewable( $mailing, $contact_id ) ) {

			// say what?
			$text = __( '<p>Sorry, but you are not allowed to view this email.</p>', 'civicrm-wp-mail-sync' );

			// allow overrides
			$text = apply_filters( 'civicrm_wp_mail_sync_email_render_not_allowed', $text, $mailing_id );

			// --<
			return $text;

		}

		// set empty header and footer
		$mailing->header_id = false;
		$mailing->footer_id = false;

		// replace tokens
		CRM_Mailing_BAO_Mailing::tokenReplace( $mailing );

		// get and format attachments
		$attachments = CRM_Core_BAO_File::getEntityFile(
			'civicrm_mailing',
			$mailing->id
		);

		// get details of contact with token value including Custom Field Token Values.CRM-3734
		$returnProperties = $mailing->getReturnProperties();
		$params = array( 'contact_id' => $contact_id );

		// get details
		$details = CRM_Utils_Token::getTokenDetails(
			$params,
			$returnProperties,
			TRUE, TRUE, NULL,
			$mailing->getFlattenedTokens(),
			'CRM_Mailing_Page_Preview'
		);

		// what?
		$mime = &$mailing->compose(
			NULL, NULL, NULL, $contact_id,
			$mailing->from_email, $mailing->from_email,
			TRUE, $details[0][$contact_id], $attachments
		);

		if ( $type == 'html' ) {
			$value = $mime->getHTMLBody();
		} else {
			$value = $mime->getTXTBody();
		}

		// --<
		return $value;

	}



	//##########################################################################



	/**
	 * Check if email is viewable
	 *
	 * @param object $mailing The CiviCRM mailing object
	 * @param int $contact_id The numerical ID of the Civi contact
	 * @return bool $is_viewable True if viewable, false otherwise
	 */
	public function is_email_viewable( $mailing, $contact_id = null ) {

		// allow if the email is public and user has permissions
		if (
			$mailing->visibility == 'Public Pages' AND
			CRM_Core_Permission::check('view public CiviMail content')
		) {
			return true;
		}

		// if user is an admin, always allow
		if (
			CRM_Core_Permission::check('administer CiviCRM') OR
			CRM_Core_Permission::check('access CiviMail')
		) {
			return true;
		}

		// if it's our post type archive page, allow...
		// because we can only ever see the mailings we've been sent
		if ( $this->wp->is_mailing_archive() ) return true;

		// at this point, we *must* have a logged in user
		if ( ! is_user_logged_in() ) return false;

		// check if current contact was a recipient
		if ( $this->is_recipient( $mailing->id, $contact_id ) ) return true;

		// --<
		return false;

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



	//##########################################################################



	/**
	 * Get text to prefix "View in Browser" link in a plain text message
	 *
	 * @param int The numeric ID of the WordPress post
	 * @return str Text and link to "View in browser"
	 */
	public function get_mail_url_plain( $post_id = null ) {

		// define text
		$plain_text = __( 'Unable to view this email? View it here:', 'civicrm-wp-mail-sync' );

		// allow overrides of the text intro
		return apply_filters( 'civicrm_wp_mail_sync_mail_plain_url', $plain_text, $post_id );

	}



	/**
	 * Get text and link to add "View in Browser" link to an HTML message
	 *
	 * @param str The permalink of the WordPress post
	 * @param int The numeric ID of the WordPress post
	 * @return str Text and link to "View in browser"
	 */
	public function get_mail_url_html( $permalink, $post_id = null ) {

		// define html and insert permalink
		$html = sprintf(
			__( 'Unable to view this email? <a href="%s">Click here to view it in your browser</a>.', 'civicrm-wp-mail-sync' ),
			$permalink
		);

		// allow overrides
		return apply_filters( 'civicrm_wp_mail_sync_mail_html_url', $html, $permalink, $post_id );

	}



} // class ends







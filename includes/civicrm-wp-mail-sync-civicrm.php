<?php
/**
 * CiviCRM Class.
 *
 * Handles general CiviCRM functionality.
 *
 * @package CiviCRM_WP_Mail_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/*
--------------------------------------------------------------------------------
Notes:
--------------------------------------------------------------------------------

$page = new CRM_Mailing_Page_View();
$value = $page->run($mailing->id, NULL, FALSE);

--------------------------------------------------------------------------------
*/



/**
 * CiviCRM WordPress Mail Sync CiviCRM Utilities Class
 *
 * A class that encapsulates CiviCRM functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Mail_Sync_CiviCRM {

	/**
	 * Plugin object.
	 *
	 * @since 0.2
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Admin Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $admin The Admin Utilities object.
	 */
	public $admin;

	/**
	 * WordPress Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $wp The WordPress Utilities object.
	 */
	public $wp;

	/**
	 * CiviCRM version.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $civicrm_version The CiviCRM version.
	 */
	public $civicrm_version;



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
	 * @since 0.2
	 */
	public function initialise() {

		// Store references to other objects.
		$this->admin = $this->plugin->admin;
		$this->wp = $this->plugin->wp;

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.2
		 */
		do_action( 'civicrm_wp_mail_sync_civicrm_initialised' );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Intercept Mailing before save.
		//add_action( 'civicrm_pre', [ $this, 'template_before_save' ], 10, 4 );

		// Intercept Mailing after save.
		//add_action( 'civicrm_post', [ $this, 'template_after_save' ], 10, 4 );

		// Intercept Mailing email before send.
		//add_action( 'civicrm_alterMailParams', [ $this, 'mailing_before_send' ], 10, 2 );

		// Intercept token values.
		//add_filter( 'civicrm_tokenValues', [ $this, 'mailing_token_values' ], 10, 4 );

		// Intercept tokens.
		//add_filter( 'civicrm_tokens', [ $this, 'mailing_tokens' ], 10, 1 );

	}



	/**
	 * Initialise CiviCRM if necessary.
	 *
	 * @since 0.2
	 *
	 * @return bool $initialised True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) OR ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept template before it has been saved.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function template_before_save( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Mailing' ) {
			return;
		}

		// Maybe cast as an object.
		if ( is_object( $objectRef ) ) {
			$mailing = $objectRef;
		} else {
			$mailing = (object) $objectRef;
		}

		// Make sure we have a message template.
		if ( empty( $mailing->body_html ) AND empty( $mailing->body_text ) ) {
			return;
		}

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	/**
	 * Intercept template after it has been saved.
	 *
	 * Create a WordPress post from an email template at the point at which the
	 * Mailing is scheduled, because prior to this, we do not know what the
	 * mailing_id is - CiviCRM now seems to increment the ID with every change!
	 *
	 * Also update the mailing template and append the permalink to the mailing
	 * plain text and HTML. The issue with doing this is that we cannot inject
	 * the link into a sensible place in the template (though plain text emails
	 * are fine) so in future, we probably want to offer a token.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function template_after_save( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Mailing' ) {
			return;
		}

		// Maybe cast as an object.
		if ( is_object( $objectRef ) ) {
			$mailing = $objectRef;
		} else {
			$mailing = (object) $objectRef;
		}

		// Do not sync on send.
		if ( empty( $objectRef->scheduled_id ) ) {
			return;
		}

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a WordPress post from an email template in CiviCRM prior to 4.6.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function template_before_save_legacy( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Mailing' ) {
			return;
		}

		// Do not sync on send.
		if ( isset( $objectRef['now'] ) AND $objectRef['now'] == 1 ) {
			return;
		}

		// Make sure we have a message template.
		if ( ! isset( $objectRef['body_html'] ) AND ! isset( $objectRef['body_text'] ) ) {
			return;
		}

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Create a post from this data.
		$post_id = $this->wp->create_post_from_mailing( $objectId, $objectRef );

		// Make sure we created a post successfully.
		if ( ! $post_id ) {
			return;
		}

		// Get permalink.
		$permalink = get_permalink( $post_id );

		// Append to plain text, if present.
		if ( isset( $objectRef['body_text'] ) ) {

			// Get link for insertion.
			$plain_text = $this->mailing_url_get_plain( $post_id );

			// Get possible position of an existing instance of a link.
			$offset = strpos( $objectRef['body_text'], $plain_text );

			// Do we already have an inserted link? (happens in re-used mailings)
			if ( false !== $offset ) {

				// Strip everything from that point to the end.
				$objectRef['body_text'] = substr_replace( $objectRef['body_text'], '', $offset );

			} else {

				// Give new link some space.
				$objectRef['body_text'] .= "\r\n\r\n";

			}

			// Append to text and insert permalink.
			$objectRef['body_text'] .= $plain_text . "\r\n" . $permalink . "\r\n";

		}

		// Apply to html, if present.
		if ( isset( $objectRef['body_html'] ) ) {

			// Get link for insertion.
			$html = $this->mailing_url_get_html( $permalink, $post_id );

			// Wrap this in a div.
			$html = '<div class="civicrm_wp_mail_sync_url">' . $html . '</div>';

			// Do we already have an inserted link (happens in re-used mailings)
			if ( false !== strpos( $objectRef['body_html'], '<!--civicrm-wp-mail-sync-url-->' ) ) {

				// Yes, replace what's between the html comments.
				$objectRef['body_html'] = preg_replace(
					'#<!--civicrm-wp-mail-sync-url-->(.*?)<!--civicrm-wp-mail-sync-url-->#s',
					$html, // Replacement
					$objectRef['body_html'] // Source
				);

			} else {

				// Wrap this with two comments (so we can tell if this is a reused template above)
				$html = '<!--civicrm-wp-mail-sync-url-->' . $html . '<!--/civicrm-wp-mail-syncurl-->';

				// Append to HTML.
				$objectRef['body_html'] .= "\r\n\r\n" . $html;

			}

		}

	}



	/**
	 * Intercept template after it has been saved in CiviCRM prior to 4.6.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function template_after_save_legacy( $op, $objectName, $objectId, $objectRef ) {

		// Disabled.
		return;

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Mailing' ) {
			return;
		}

		// Do not sync on send.
		if ( isset( $objectRef['now'] ) AND $objectRef['now'] == 1 ) {
			return;
		}

		// Make sure we have a message template.
		if ( ! isset( $objectRef['body_html'] ) AND ! isset( $objectRef['body_text'] ) ) {
			return;
		}

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	/**
	 * Intercept every email before it is sent.
	 *
	 * @since 0.1
	 *
	 * @param array $params The message parameters.
	 * @param string $context The message context.
	 */
	public function mailing_before_send( $params, $context = null ) {

		// Target our context.
		if ( $context != 'civimail' ) {
			return;
		}

		// Disabled.
		return;

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'context' => $context,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	/**
	 * Intercept token values.
	 *
	 * @since 0.1
	 *
	 * @param array $values The token values.
	 * @param array $contact_id An array of numerical IDs of the Civi contacts.
	 * @param int $job_id The job ID.
	 * @param array $tokens The tokens whose values need replacing.
	 */
	public function mailing_token_values( &$values, $contact_ids, $job_id = null, $tokens = [] ) {

		// Disabled.
		return;

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'values' => $values,
			'contact_ids' => $contact_ids,
			'job_id' => $job_id,
			'tokens' => $tokens,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Target our token
		if ( ! isset( $tokens['mailing']['viewUrl'] ) ) {
			return;
		}

		// Replace view url token?

	}



	/**
	 * Intercept tokens.
	 *
	 * @since 0.1
	 *
	 * @param array $tokens The tokens.
	 * @return void
	 */
	public function mailing_tokens( $tokens ) {

		// Disabled.
		return;

		// Unset view url token?

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'tokens' => $tokens,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a personalised message.
	 *
	 * @since 0.1
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing.
	 * @param int $contact_id The numerical ID of the Civi contact.
	 * @param str $type Either 'html' or 'text' (default 'html')
	 * @return str $message The formatted message.
	 */
	public function mailing_render( $mailing_id, $contact_id = null, $type = 'html' ) {

		// Init CiviCRM or die.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// If we don't have a passed contact, use logged in user.
		if ( is_null( $contact_id ) AND is_user_logged_in() ) {

			// Get current user.
			$current_user = wp_get_current_user();

			// Get Civi contact ID.
			$contact_id = $this->contact_id_get_by_user_id( $current_user->ID );

		}

		/*
		// Replace tokens (fails due to buggy permissions)
		$page = new CRM_Mailing_Page_View();
		$value = $page->run( $mailing_id, $contact_id, FALSE );
		*/

		// The following copied from CRM_Mailing_Page_Preview.
		// @see CRM/Mailing/Page/Preview.php

		// Init mailing.
		$mailing = new CRM_Mailing_BAO_Mailing();

		// Set ID.
		$mailing->id = $mailing_id;

		// Try and find it.
		if ( ! $mailing->find( true ) ) {

			// Say what?
			$text = __( '<p>Sorry, this email has not been found.</p>', 'civicrm-wp-mail-sync' );

			/**
			 * Filter the "not found" message.
			 *
			 * @since 0.1
			 *
			 * @param str $text The existing message text.
			 * @param int $mailing_id The numeric ID of the Mailing.
			 * @return str $text The modified message text.
			 */
			$text = apply_filters( 'civicrm_wp_mail_sync_email_render_not_found', $text, $mailing_id );

			// --<
			return $text;

		}

		// What's the status of this mailing?
		if ( ! $this->mailing_is_viewable( $mailing, $contact_id ) ) {

			// Say what?
			$text = __( '<p>Sorry, but you are not allowed to view this email.</p>', 'civicrm-wp-mail-sync' );

			/**
			 * Filter the "not allowed" message.
			 *
			 * @since 0.1
			 *
			 * @param str $text The existing message text.
			 * @param int $mailing_id The numeric ID of the Mailing.
			 * @return str $text The modified message text.
			 */
			$text = apply_filters( 'civicrm_wp_mail_sync_email_render_not_allowed', $text, $mailing_id );

			// --<
			return $text;

		}

		// Set empty header and footer.
		$mailing->header_id = false;
		$mailing->footer_id = false;

		// Replace tokens.
		CRM_Mailing_BAO_Mailing::tokenReplace( $mailing );

		// Get and format attachments.
		$attachments = CRM_Core_BAO_File::getEntityFile(
			'civicrm_mailing',
			$mailing->id
		);

		// Get details of contact with token value including Custom Field Token Values. See CRM-3734
		$returnProperties = $mailing->getReturnProperties();
		$params = [ 'contact_id' => $contact_id ];

		// Get details.
		$details = CRM_Utils_Token::getTokenDetails(
			$params,
			$returnProperties,
			TRUE, TRUE, NULL,
			$mailing->getFlattenedTokens(),
			'CRM_Mailing_Page_Preview'
		);

		// What?
		$mime = $mailing->compose(
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



	// -------------------------------------------------------------------------



	/**
	 * Get text to prefix "View in Browser" link in a plain text message.
	 *
	 * @since 0.1
	 *
	 * @param int The numeric ID of the WordPress post.
	 * @return str Text and link to "View in browser".
	 */
	public function mailing_url_get_plain( $post_id = null ) {

		// Define text.
		$plain_text = __( 'Unable to view this email? View it here:', 'civicrm-wp-mail-sync' );

		/**
		 * Filter the "View in Browser" link in a Plain Text message.
		 *
		 * @since 0.1
		 *
		 * @param str $plain_text The existing message text.
		 * @param int $post_id The numeric ID of the WordPress Post.
		 * @return str $plain_text The modified message text.
		 */
		return apply_filters( 'civicrm_wp_mail_sync_mail_plain_url', $plain_text, $post_id );

	}



	/**
	 * Get text and link to add "View in Browser" link to an HTML message.
	 *
	 * @since 0.1
	 *
	 * @param str The permalink of the WordPress post.
	 * @param int The numeric ID of the WordPress post.
	 * @return str Text and link to "View in browser".
	 */
	public function mailing_url_get_html( $permalink, $post_id = null ) {

		// Define html and insert permalink.
		$html = sprintf(
			__( 'Unable to view this email? <a href="%s">Click here to view it in your browser</a>.', 'civicrm-wp-mail-sync' ),
			$permalink
		);

		/**
		 * Filter the "View in Browser" link in an HTML message.
		 *
		 * @since 0.1
		 *
		 * @param str $text The existing message text.
		 * @param int $mailing_id The numeric ID of the Mailing.
		 * @return str $text The modified message text.
		 */
		return apply_filters( 'civicrm_wp_mail_sync_mail_html_url', $html, $permalink, $post_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if email is viewable.
	 *
	 * @since 0.1
	 *
	 * @param object $mailing The CiviCRM mailing object.
	 * @param int $contact_id The numerical ID of the Civi contact.
	 * @return bool $is_viewable True if viewable, false otherwise.
	 */
	public function mailing_is_viewable( $mailing, $contact_id = null ) {

		// Allow if the email is public and user has permissions.
		if (
			$mailing->visibility == 'Public Pages' AND
			CRM_Core_Permission::check('view public CiviMail content')
		) {
			return true;
		}

		// If user is an admin, always allow.
		if (
			CRM_Core_Permission::check('administer CiviCRM') OR
			CRM_Core_Permission::check('access CiviMail')
		) {
			return true;
		}

		// If it's our post type archive page, allow...
		// Because we can only ever see the mailings we've been sent.
		if ( $this->wp->is_mailing_archive() ) {
			return true;
		}

		// At this point, we *must* have a logged in user.
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if current contact was a recipient.
		if ( $this->contact_is_recipient( $mailing->id, $contact_id ) ) {
			return true;
		}

		// --<
		return false;

	}



	/**
	 * Get all Civi Mailings.
	 *
	 * @since 0.1
	 *
	 * @return bool|array $mailings The Civi API array containg the mailings.
	 */
	public function mailings_get_all() {

		// Init CiviCRM or die.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Construct array.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => '0',
			],
		];

		// Call API.
		$mailings = civicrm_api( 'mailing', 'get', $params );

		// --<
		return $mailings;

	}



	/**
	 * Get all Civi Mailings for a Contact.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numerical ID of the Civi contact.
	 * @return array $mailings The Civi API array containg the mailings.
	 */
	public function mailings_get_by_contact_id( $contact_id ) {

		// Init CiviCRM or die.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Construct array.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			/*
			//'type' => 'Delivered',
			'options' => [
				'Delivered' => 'Delivered',
				'Bounced' => 'Bounced',
				//'limit' => '100000',
			],
			*/
		];

		// Call API.
		$mailings = civicrm_api( 'mailing_contact', 'get', $params );

		// --<
		return $mailings;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get CiviCRM contact ID by WordPress user ID.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The numeric ID of the WordPress user.
	 * @return int $contact_id The numeric ID of the CiviCRM Contact.
	 */
	public function contact_id_get_by_user_id( $user_id ) {

		// Init or die.
		if ( ! $this->is_initialised() ) {
			return;
		}

		// Make sure Civi file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Do initial search.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $user_id );

		// Return it if we get one.
		if ( $contact_id ) {
			return $contact_id;
		}

		// Fallback.
		return false;

	}



	/**
	 * Get all Civi Contacts for a Mailing.
	 *
	 * @since 0.1
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing.
	 * @return array $contacts The Civi API array containg the contacts.
	 */
	public function contacts_get_by_mailing_id( $mailing_id ) {

		// Init CiviCRM or die.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Construct array.
		$params = [
			'version' => 3,
			'id' => $mailing_id,
			'options' => [
				'limit' => '0',
			],
		];

		// Call API
		$contacts = civicrm_api( 'mailing_recipients', 'get', $params );

		// --<
		return $contacts;

	}



	/**
	 * Check if a Civi Contact was a recipient of a Mailing.
	 *
	 * @since 0.1
	 *
	 * @param int $mailing_id The numerical ID of the Civi mailing.
	 * @param int $contact_id The numerical ID of the Civi contact.
	 * @return bool True if contact was a recipient of this mailing, false otherwise.
	 */
	public function contact_is_recipient( $mailing_id, $contact_id ) {

		// Init CiviCRM or die.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Get mailings.
		$mailings = $this->mailings_get_by_contact_id( $contact_id );

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'mailing_id' => $mailing_id,
			'contact_id' => $contact_id,
			'mailings' => $mailings,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Did we get any?
		if ( count( $mailings['values'] ) > 0 ) {

			// Get recipient IDs array.
			$mailing_ids = array_keys( $mailings['values'] );

			// Is our mailing in this array?
			if ( in_array( $mailing_id, $mailing_ids ) ) {
				return true;
			}

		}

		// Fallback.
		return false;

	}



} // Class ends.




<?php /*
--------------------------------------------------------------------------------
CiviCRM_WP_Mail_Sync_WordPress Class
--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating WordPress functionality
 */
class CiviCRM_WP_Mail_Sync_WordPress {

	/** 
	 * Properties
	 */
	
	// Admin utilities
	public $admin;
	
	// CiviCRM utilities
	public $civi;
	
	// CPT name
	public $cpt_name = 'mailing';
	
	
	
	/** 
	 * Initialises this object
	 * 
	 * @return object
	 */
	function __construct() {
	
		// register hooks on WordPress plugins loaded
		add_action( 'plugins_loaded', array( $this, 'register_hooks' ) );
		
		// --<
		return $this;

	}
	
	
	
	/**
	 * Set references to other objects
	 * 
	 * @param object $admin_object Reference to this plugin's Admin object
	 * @param object $civi_object Reference to this plugin's CIviCRM object
	 * @return void
	 */
	public function set_references( &$admin_object, &$civi_object ) {
	
		// store reference to Admin object
		$this->admin = $admin_object;
		
		// store reference to CiviCRM object
		$this->civi = $civi_object;
		
	}
	
	
		
	/**
	 * Register hooks on BuddyPress loaded
	 * 
	 * @return void
	 */
	public function register_hooks() {
		
		// register post type
		add_action( 'init', array( $this, 'register_cpt' ) );
		
		// filter the query
		add_action( 'pre_get_posts', array( $this, 'parse_query' ), 100, 1 );
		
		// modify the content
		//add_filter( 'the_content', array( $this, 'parse_content' ), 100, 1 );
		
	}
	
	
		
	//##########################################################################
	
	
	
	/**
	 * Register a custom post type
	 *
	 * @return void
	 */
	public function register_cpt() {
		
		// only call this once
		static $registered;
		
		// bail if already done
		if ( $registered ) return;
	
		// working paper group
		register_post_type( $this->cpt_name, array( 
		
			'label' => __( 'Mailings' ),
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => false,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array( 
				'slug' => apply_filters( 'civicrm_wp_mail_sync_cpt_slug', 'mailings' ),
				'with_front' => false,
				'feeds' => false,
			),
			'has_archive' => true,
			'query_var' => true,
			'exclude_from_search' => true,
			'can_export' => false,
			'supports' => array(
				'title',
				'editor'
			),
			'labels' => array (
				'name' => __( 'Mailings', 'civicrm-wp-mail-sync' ),
				'singular_name' => __( 'Mailing', 'civicrm-wp-mail-sync' ),
				'menu_name' => __( 'Mailings', 'civicrm-wp-mail-sync' ),
				'add_new' => __( 'Add Mailing', 'civicrm-wp-mail-sync' ),
				'add_new_item' => __( 'Add New Mailing', 'civicrm-wp-mail-sync' ),
				'edit' => __( 'Edit', 'civicrm-wp-mail-sync' ),
				'edit_item' => __( 'Edit Mailing', 'civicrm-wp-mail-sync' ),
				'new_item' => __( 'New Mailing', 'civicrm-wp-mail-sync' ),
				'view' => __( 'View Mailing', 'civicrm-wp-mail-sync' ),
				'view_item' => __( 'View Mailing', 'civicrm-wp-mail-sync' ),
				'search_items' => __( 'Search Mailings', 'civicrm-wp-mail-sync' ),
				'not_found' => __( 'No Mailings found', 'civicrm-wp-mail-sync' ),
				'not_found_in_trash' => __( 'No Mailings found in Trash', 'civicrm-wp-mail-sync' ),
			)
			
		) );
		
		//flush_rewrite_rules();
		
		// flag
		$registered = true;
	
	}
	
	
	
	/**
	 * Create a post with the content of a Mailing
	 * 
	 * @param int $mailing_id The numerical Civi mailing ID
	 * @param array $mailing The Civi data for the mailing
	 * @return int $post_id The numeric ID of the new post
	 */
	public function create_post_from_mailing( $mailing_id, $mailing ) {
	
		// init content
		$content = '';
		
		// use plain text content if we have it
		if ( isset( $mailing['body_text'] ) AND ! empty( $mailing['body_text'] ) ) {
			$content = $mailing['body_text'];
		}
	
		// override with HTML content if we have it
		if ( isset( $mailing['body_html'] ) AND ! empty( $mailing['body_html'] ) ) {
			$content = $mailing['body_html'];
		}
		
		// sanity check
		if ( empty( $content ) ) return;
		
		// create a WordPress post
		$post_id = $this->create_post( $mailing['subject'], $content );
		
		// store linkages between post amd mailing
		$this->admin->link_post_and_mailing( $post_id, $mailing_id );
		
		// --<
		return $post_id;
		
	}
	
	
	
	/**
	 * Create a post of our custom post type
	 * 
	 * @param str $title The subject line of the mailing
	 * @param str $content The HTML content of the mailing
	 * @return int $post_id The numeric ID of the new post
	 */
	public function create_post( $title, $content ) {
	
		// define mailing post
		$post = array(
			'post_status' => 'publish',
			'post_type' => $this->cpt_name,
			'post_content' => $content,
			'post_excerpt' => $content,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'to_ping' => '', // quick fix for Windows
			'pinged' => '', // quick fix for Windows
			'post_content_filtered' => '', // quick fix for Windows
		);
		
		// allow overrides of title
		$post['post_title'] = apply_filters( 
			'civicrm_wp_mail_sync_cpt_post_title',
			$title
		);
		
		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		
		// --<
		return $post_id;
		
	}
	
	
	
	/**
	 * Delete a post of our custom post type
	 * 
	 * @param int $post_id The numerical ID of the WordPress post
	 * @return bool $force_delete If true, trash will be bypassed
	 */
	public function delete_post( $post_id, $force_delete ) {
	
		// bypass trash
		$force_delete = true;
	
		// delete and return success value
		return wp_delete_post( $post_id, $force_delete );
		
	}
	
	
	
	/**
	 * Get all posts of our custom post type
	 * 
	 * @return array $posts Array of post objects
	 */
	public function get_posts() {
		
		// get them
		$posts = get_posts( array(
			'post_type' => $this->wp->cpt_name,
		) );
		
		// --<
		return $posts;

	}
	
	
	
	/** 
	 * Parses query
	 *
	 * @param object $query The current query
	 * @return object $query The modified query
	 */
	public function parse_query( $query ) {
		
		// bail if administrator
		//if( is_super_admin() ) return $query;
		
		// bail if admin
		if( is_admin() ) return $query;
		
		// bail if not main query
		if ( ! $query->is_main_query() ) return $query;
		
		// bail if not our post type
		if ( ! is_post_type_archive( $this->cpt_name ) ) return $query;
		
		// init post ID array
		$post_ids = array();
		
		// if we're logged in
		if ( is_user_logged_in() ) {
		
			// get current user
			$current_user = wp_get_current_user();
		
			// get Civi contact ID
			$contact_id = $this->civi->get_contact_id_by_user_id( $current_user->ID );
		
			// if we get one
			if ( $contact_id !== false ) {
			
				// get mailings for this user
				$mailings = $this->civi->get_mailings_by_contact_id( $contact_id );
				
				// did we get any?
				if ( isset( $mailings['values'] ) AND count( $mailings['values'] ) > 0 ) {
				
					// get mailing IDs
					$mailing_ids = array_keys( $mailings['values'] );
					
					/*
					print_r( array( 
						'mailing_ids' => $mailing_ids,
					)); //die();
					*/
					
					// get the post IDs
					foreach( $mailing_ids AS $mailing_id ) {
						$post_ids[] = $this->admin->get_post_id_by_mailing_id( $mailing_id );
					}
				
				}
				
			}
		
		}
		
		// if $post_ids is empty, pass the largest bigint(20) value to ensure no posts are matched
		$post_ids = empty( $post_ids ) ? array( '18446744073709551615' ) : $post_ids;

		// restrict to those posts
		$query->set( 'post__in', $post_ids );
		
		/*
		print_r( array( 
			'query' => $query,
		)); die();
		*/
	
		// --<
		return $query;
		
	}
	
	
	
	/** 
	 * Parses post content
	 *
	 * @param str $content The content of the post
	 * @return str $content
	 */
	public function parse_content( $content ) {
	
		// reference our post
		global $post;
		
		// replace tokens...
		
		// --<
		return $content;
		
	}
	
	
	
} // class ends







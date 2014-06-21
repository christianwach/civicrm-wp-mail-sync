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
		
		// filter the query
		//add_action( 'pre_get_posts', array( $this, 'parse_query' ), 100, 1 );
		
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
	 * Parses query
	 *
	 * @param object $query The current query
	 * @return object $query The modified query
	 */
	public function parse_query( $query ) {
		
		// bail if admin
		if( is_admin() ) return $query;
		
		// bail if not main query
		if ( ! $query->is_main_query() ) return $query;
		
		// bail if not our post type
		if ( ! is_post_type_archive( $this->cpt_name ) ) return $query;
	
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







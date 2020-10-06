<?php
/**
 * WordPress Class.
 *
 * Handles general WordPress functionality.
 *
 * @package CiviCRM_WP_Mail_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM WordPress Mail Sync WordPress Utilities Class
 *
 * A class that encapsulates WordPress functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Mail_Sync_WordPress {

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
	 * CiviCRM Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * CPT name.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $cpt_name The CPT name.
	 */
	public $cpt_name = 'mailing';



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
		$this->civicrm = $this->plugin->civicrm;

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.2
		 */
		do_action( 'civicrm_wp_mail_sync_wp_initialised' );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Register Custom Post Type.
		add_action( 'init', [ $this, 'register_cpt' ] );

		// Filter the query.
		add_action( 'pre_get_posts', [ $this, 'parse_query' ], 100, 1 );

		// Modify the content.
		add_filter( 'the_content', [ $this, 'parse_content' ], 100, 1 );
		add_filter( 'the_excerpt', [ $this, 'parse_content' ], 100, 1 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Register a custom post type.
	 *
	 * @since 0.1
	 */
	public function register_cpt() {

		// Only call this once.
		static $registered;
		if ( $registered ) {
			return;
		}

		/**
		 * Filter the CPT slug.
		 *
		 * @since 0.1
		 *
		 * @param str The default slug.
		 * @return str The modified slug.
		 */
		$slug = apply_filters( 'civicrm_wp_mail_sync_cpt_slug', 'mailings' );

		// Mailing CPT.
		register_post_type( $this->cpt_name, [

			'label' => __( 'Mailings' ),
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => [
				'slug' => $slug,
				'with_front' => false,
				'feeds' => false,
			],
			'has_archive' => true,
			'query_var' => true,
			'exclude_from_search' => true,
			'can_export' => false,
			'supports' => [
				'title',
				'editor',
			],
			'labels' => [
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
			],

		] );

		//flush_rewrite_rules();

		// Flag.
		$registered = true;

	}



	/**
	 * Create a post with the content of a Mailing.
	 *
	 * @since 0.1
	 *
	 * @param int $mailing_id The numerical Civi mailing ID.
	 * @param array $mailing The Civi data for the mailing.
	 * @return int $post_id The numeric ID of the new post.
	 */
	public function create_post_from_mailing( $mailing_id, $mailing ) {

		// Check if we already have a post.
		$existing_post_id = $this->admin->get_post_id_by_mailing_id( $mailing_id );

		// Return it, if we have one.
		if ( $existing_post_id !== false ) {
			return $existing_post_id;
		}

		// Init content.
		$content = '';

		// Use plain text content if we have it.
		if ( isset( $mailing['body_text'] ) AND ! empty( $mailing['body_text'] ) ) {
			$content = $mailing['body_text'];
		}

		// Override with HTML content if we have it.
		if ( isset( $mailing['body_html'] ) AND ! empty( $mailing['body_html'] ) ) {
			$content = $mailing['body_html'];
		}

		// Sanity check.
		if ( empty( $content ) ) {
			return;
		}

		// Create a WordPress post.
		$post_id = $this->create_post( $mailing['subject'], $content );

		// Store linkages between post amd mailing.
		$this->admin->link_post_and_mailing( $post_id, $mailing_id );

		// --<
		return $post_id;

	}



	/**
	 * Create a post of our custom post type.
	 *
	 * @since 0.1
	 *
	 * @param str $title The subject line of the mailing.
	 * @param str $content The HTML content of the mailing.
	 * @return int $post_id The numeric ID of the new post.
	 */
	public function create_post( $title, $content ) {

		// Define mailing post.
		$post = [
			'post_status' => 'publish',
			'post_type' => $this->cpt_name,
			'post_content' => $content,
			'post_excerpt' => $content,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'to_ping' => '', // Quick fix for Windows.
			'pinged' => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
		];

		/**
		 * Filter the WordPress Post Title.
		 *
		 * @since 0.1
		 *
		 * @param str $title The existing title.
		 * @return str $title The modified title.
		 */
		$post['post_title'] = apply_filters( 'civicrm_wp_mail_sync_cpt_post_title', $title );

		// Insert the post into the database.
		$post_id = wp_insert_post( $post );

		// --<
		return $post_id;

	}



	/**
	 * Delete a post of our custom post type.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numerical ID of the WordPress post.
	 * @return bool $force_delete If true, trash will be bypassed.
	 */
	public function delete_post( $post_id, $force_delete ) {

		// Delete and return success value.
		return wp_delete_post( $post_id, $force_delete );

	}



	/**
	 * Get all posts of our custom post type.
	 *
	 * @since 0.1
	 *
	 * @return array $posts Array of post objects.
	 */
	public function get_posts() {

		// Get them.
		$posts = get_posts( [
			'post_type' => $this->cpt_name,
		] );

		// --<
		return $posts;

	}



	/**
	 * Delete all posts of our custom post type.
	 *
	 * @since 0.1
	 */
	public function delete_posts() {

		// Get the posts.
		$posts = $this->get_posts();

		// Delete them one by one.
		if ( count( $posts ) > 0 ) {
			foreach( $posts AS $post ) {
				$this->delete_post( $post->ID, true );
			}
		}

	}



	/**
	 * Parse the query.
	 *
	 * @since 0.1
	 *
	 * @param object $query The current query.
	 * @return object $query The modified query.
	 */
	public function parse_query( $query ) {

		/*
		// Bail if administrator.
		if ( is_super_admin() ) {
			return $query;
		}
		*/

		// Bail if in WordPress admin.
		if ( is_admin() ) {
			return $query;
		}

		// Bail if not main query.
		if ( ! $query->is_main_query() ) {
			return $query;
		}

		// Bail if not our post type.
		if ( $query->get( 'post_type' ) != $this->cpt_name ) {
			return $query;
		}

		// Bail if it's not our post type's archive page.
		if ( ! is_post_type_archive( $this->cpt_name ) ) {
			return $query;
		}

		// Init post ID array.
		$post_ids = [];

		// If we're logged in.
		if ( is_user_logged_in() ) {

			// Get current user.
			$current_user = wp_get_current_user();

			// Get Civi contact ID.
			$contact_id = $this->civicrm->contact_id_get_by_user_id( $current_user->ID );

			// If we get one.
			if ( $contact_id !== false ) {

				// Get mailings for this user.
				$mailings = $this->civicrm->mailings_get_by_contact_id( $contact_id );

				// Did we get any?
				if ( isset( $mailings['values'] ) AND count( $mailings['values'] ) > 0 ) {

					// Get mailing IDs.
					$mailing_ids = array_keys( $mailings['values'] );

					// Get the post IDs.
					foreach( $mailing_ids AS $mailing_id ) {
						$post_ids[] = $this->admin->get_post_id_by_mailing_id( $mailing_id );
					}

				}

			}

		}

		// If $post_ids is empty, pass the largest bigint(20) value to ensure no posts are matched
		// This is a temporary measure until we have proper checks for the type of email.
		$post_ids = empty( $post_ids ) ? [ '18446744073709551615' ] : $post_ids;

		// Restrict to those posts.
		$query->set( 'post__in', $post_ids );

		// --<
		return $query;

	}



	/**
	 * Parses post content.
	 *
	 * @since 0.1
	 *
	 * @param str $content The content of the post.
	 * @return str $content The modified content.
	 */
	public function parse_content( $content ) {

		// Reference our post.
		global $post;

		// Sanity check.
		if ( ! is_object( $post ) ) {
			return $content;
		}

		// Only parse our post type.
		if ( $post->post_type != $this->cpt_name ) {
			return $content;
		}

		// Get mailing for this post.
		$mailing_id = $this->admin->get_mailing_id_by_post_id( $post->ID );

		// Get rendered content.
		$content = $this->civicrm->mailing_render( $mailing_id );

		// --<
		return $content;

	}



	/**
	 * Get the name of the custom post type.
	 *
	 * @since 0.1
	 *
	 * @return str $cpt_name The name of the custom post type.
	 */
	public function get_cpt_name() {

		// --<
		return $this->cpt_name;

	}



	/**
	 * Check if we're viewing the mailing archive page.
	 *
	 * @since 0.1
	 *
	 * @return bool True if archive page, false otherwise.
	 */
	public function is_mailing_archive() {

		// --<
		return is_post_type_archive( $this->cpt_name );

	}



} // Class ends.




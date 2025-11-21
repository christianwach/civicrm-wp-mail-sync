<?php
/**
 * WordPress Class.
 *
 * Handles general WordPress functionality.
 *
 * @package CiviCRM_WP_Mail_Sync
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
	 * @var CiviCRM_WP_Mail_Sync
	 */
	public $plugin;

	/**
	 * Admin Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var CiviCRM_WP_Mail_Sync_Admin
	 */
	public $admin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var CiviCRM_WP_Mail_Sync_CiviCRM
	 */
	public $civicrm;

	/**
	 * CPT name.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $cpt_name = 'mailing';

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param CiviCRM_WP_Mail_Sync $plugin The plugin object.
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
		$this->admin   = $this->plugin->admin;
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

		// Register our Custom Post Type.
		add_action( 'init', [ $this, 'register_cpt' ] );

		// Filter the query.
		add_action( 'pre_get_posts', [ $this, 'parse_query' ], 100, 1 );

		// Modify the content.
		add_filter( 'the_content', [ $this, 'parse_content' ], 100, 1 );
		add_filter( 'the_excerpt', [ $this, 'parse_content' ], 100, 1 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register our Custom Post Type.
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
		 * @param string The default slug.
		 */
		$slug = apply_filters( 'civicrm_wp_mail_sync_cpt_slug', 'mailings' );

		// Define CPT args.
		$args = [
			'label'               => __( 'Mailings', 'civicrm-wp-mail-sync' ),
			'description'         => '',
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'rewrite'             => [
				'slug'       => $slug,
				'with_front' => false,
				'feeds'      => false,
			],
			'has_archive'         => true,
			'query_var'           => true,
			'exclude_from_search' => true,
			'can_export'          => false,
			'supports'            => [
				'title',
				'editor',
			],
			'labels'              => [
				'name'               => __( 'Mailings', 'civicrm-wp-mail-sync' ),
				'singular_name'      => __( 'Mailing', 'civicrm-wp-mail-sync' ),
				'menu_name'          => __( 'Mailings', 'civicrm-wp-mail-sync' ),
				'add_new'            => __( 'Add Mailing', 'civicrm-wp-mail-sync' ),
				'add_new_item'       => __( 'Add New Mailing', 'civicrm-wp-mail-sync' ),
				'edit'               => __( 'Edit', 'civicrm-wp-mail-sync' ),
				'edit_item'          => __( 'Edit Mailing', 'civicrm-wp-mail-sync' ),
				'new_item'           => __( 'New Mailing', 'civicrm-wp-mail-sync' ),
				'view'               => __( 'View Mailing', 'civicrm-wp-mail-sync' ),
				'view_item'          => __( 'View Mailing', 'civicrm-wp-mail-sync' ),
				'search_items'       => __( 'Search Mailings', 'civicrm-wp-mail-sync' ),
				'not_found'          => __( 'No Mailings found', 'civicrm-wp-mail-sync' ),
				'not_found_in_trash' => __( 'No Mailings found in Trash', 'civicrm-wp-mail-sync' ),
			],
		];

		// Mailing CPT.
		// phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral
		register_post_type( $this->cpt_name, $args );

		/*
		// Force flush Rewrite Rules.
		flush_rewrite_rules();
		*/

		// Flag.
		$registered = true;

	}

	/**
	 * Create a WordPress Post with the content of a CiviCRM Mailing.
	 *
	 * @since 0.1
	 *
	 * @param int   $mailing_id The numeric ID of the CiviCRM Mailing.
	 * @param array $mailing The data for the CiviCRM Mailing.
	 * @return int $post_id The numeric ID of the new WordPress Post.
	 */
	public function create_post_from_mailing( $mailing_id, $mailing ) {

		// Check if we already have a WordPress Post.
		$existing_post_id = $this->admin->get_post_id_by_mailing_id( $mailing_id );

		// Return it, if we have one.
		if ( false !== $existing_post_id ) {
			return $existing_post_id;
		}

		// Init content.
		$content = '';

		// Use plain text content if we have it.
		if ( isset( $mailing['body_text'] ) && ! empty( $mailing['body_text'] ) ) {
			$content = $mailing['body_text'];
		}

		// Override with HTML content if we have it.
		if ( isset( $mailing['body_html'] ) && ! empty( $mailing['body_html'] ) ) {
			$content = $mailing['body_html'];
		}

		// Sanity check.
		if ( empty( $content ) ) {
			return;
		}

		// Create a WordPress Post.
		$post_id = $this->create_post( $mailing['subject'], $content );

		// Store linkages between WordPress Post and CiviCRM Mailing.
		$this->admin->link_post_and_mailing( $post_id, $mailing_id );

		// --<
		return $post_id;

	}

	/**
	 * Create a WordPress Post of our Custom Post Type.
	 *
	 * @since 0.1
	 *
	 * @param str $title The subject line of the CiviCRM Mailing.
	 * @param str $content The HTML content of the CiviCRM Mailing.
	 * @return int $post_id The numeric ID of the new WordPress Post.
	 */
	public function create_post( $title, $content ) {

		// Define WordPress Post data.
		$post = [
			'post_status'           => 'publish',
			'post_type'             => $this->cpt_name,
			'post_content'          => $content,
			'post_excerpt'          => $content,
			'comment_status'        => 'closed',
			'ping_status'           => 'closed',
			'to_ping'               => '', // Quick fix for Windows.
			'pinged'                => '', // Quick fix for Windows.
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

		// Insert the WordPress Post into the database.
		$post_id = wp_insert_post( $post );

		// --<
		return $post_id;

	}

	/**
	 * Delete a WordPress Post of our Custom Post Type.
	 *
	 * @since 0.1
	 *
	 * @param int  $post_id The numeric ID of the WordPress Post.
	 * @param bool $force_delete If true, trash will be bypassed.
	 * @return WP_Post|false|null Post data on success, false or null on failure.
	 */
	public function delete_post( $post_id, $force_delete ) {

		// Delete and return success value.
		return wp_delete_post( $post_id, $force_delete );

	}

	/**
	 * Get all WordPress Posts of our Custom Post Type.
	 *
	 * @since 0.1
	 *
	 * @return array $posts Array of WordPress Post objects.
	 */
	public function get_posts() {

		// Get them.
		$posts = get_posts(
			[
				'post_type' => $this->cpt_name,
			]
		);

		// --<
		return $posts;

	}

	/**
	 * Delete all WordPress Posts of our Custom Post Type.
	 *
	 * @since 0.1
	 */
	public function delete_posts() {

		// Get the WordPress Posts.
		$posts = $this->get_posts();

		// Delete them one by one.
		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
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

		// Bail if in WordPress admin.
		if ( is_admin() ) {
			return $query;
		}

		// Bail if not main query.
		if ( ! $query->is_main_query() ) {
			return $query;
		}

		// Bail if not our Custom Post Type.
		if ( $query->get( 'post_type' ) !== $this->cpt_name ) {
			return $query;
		}

		// Bail if it's not our Custom Post Type's Archive Page.
		if ( ! is_post_type_archive( $this->cpt_name ) ) {
			return $query;
		}

		// Init Post ID array.
		$post_ids = [];

		// If we're logged in.
		if ( is_user_logged_in() ) {

			// Get current user.
			$current_user = wp_get_current_user();

			// Get CiviCRM Contact ID.
			$contact_id = $this->civicrm->contact_id_get_by_user_id( $current_user->ID );

			// If we get one.
			if ( false !== $contact_id ) {

				// Get CiviCRM Mailings for this user.
				$mailings = $this->civicrm->mailings_get_by_contact_id( $contact_id );

				// Did we get any?
				if ( ! empty( $mailings ) ) {

					// Get CiviCRM Mailing IDs.
					$mailing_ids = array_keys( $mailings );

					// Get the WordPress Post IDs.
					foreach ( $mailing_ids as $mailing_id ) {
						$post_ids[] = $this->admin->get_post_id_by_mailing_id( $mailing_id );
					}

				}

			}

		}

		/*
		 * If $post_ids is empty, pass the largest bigint(20) value to ensure
		 * no posts are matched. This is a temporary measure until we have
		 * proper checks for the type of email.
		 */
		$post_ids = empty( $post_ids ) ? [ '18446744073709551615' ] : $post_ids;

		// Restrict to those posts.
		$query->set( 'post__in', $post_ids );

		// --<
		return $query;

	}

	/**
	 * Parse the content of a WordPress Post.
	 *
	 * @since 0.1
	 *
	 * @param str $content The existing content of the WordPress Post.
	 * @return str $content The modified content of the WordPress Post.
	 */
	public function parse_content( $content ) {

		// Reference the Post object.
		global $post;

		// Sanity check.
		if ( ! is_object( $post ) ) {
			return $content;
		}

		// Only parse our Custom Post Type.
		if ( $post->post_type !== $this->cpt_name ) {
			return $content;
		}

		// Get CiviCRM Mailing for this WordPress Post.
		$mailing_id = $this->admin->get_mailing_id_by_post_id( $post->ID );

		// Get rendered content.
		$content = $this->civicrm->mailing_render( $mailing_id );

		// --<
		return $content;

	}

	/**
	 * Get the name of the Custom Post Type.
	 *
	 * @since 0.1
	 *
	 * @return str $cpt_name The name of the Custom Post Type.
	 */
	public function get_cpt_name() {

		// --<
		return $this->cpt_name;

	}

	/**
	 * Check if we're viewing the Mailing Archive Page.
	 *
	 * @since 0.1
	 *
	 * @return bool True if viewing the Mailing Archive Page, false otherwise.
	 */
	public function is_mailing_archive() {

		// --<
		return is_post_type_archive( $this->cpt_name );

	}

}

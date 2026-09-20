<?php
/**
 * The business_card post type, its capabilities and the card_holder role.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the post type and manages roles and capabilities.
 */
class DBCP_Post_Type {

	/**
	 * Post type name.
	 */
	const POST_TYPE = 'business_card';

	/**
	 * Query var used for the single card and the vCard endpoint.
	 */
	const QUERY_VAR = 'business_card';

	/**
	 * Role created on activation.
	 */
	const ROLE = 'card_holder';

	/**
	 * Roles that receive the full capability set on activation.
	 *
	 * @var string[]
	 */
	const MANAGER_ROLES = array( 'administrator', 'editor' );

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'pre_get_posts', array( $this, 'scope_admin_list' ) );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Register the post type. Also called directly on activation so rewrite rules can be flushed.
	 *
	 * @return void
	 */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Business Cards', 'post type general name', 'digital-business-card-posts' ),
			'singular_name'         => _x( 'Business Card', 'post type singular name', 'digital-business-card-posts' ),
			'menu_name'             => _x( 'Business Cards', 'admin menu', 'digital-business-card-posts' ),
			'name_admin_bar'        => _x( 'Business Card', 'add new on admin bar', 'digital-business-card-posts' ),
			'add_new'               => __( 'Add New', 'digital-business-card-posts' ),
			'add_new_item'          => __( 'Add New Business Card', 'digital-business-card-posts' ),
			'new_item'              => __( 'New Business Card', 'digital-business-card-posts' ),
			'edit_item'             => __( 'Edit Business Card', 'digital-business-card-posts' ),
			'view_item'             => __( 'View Card', 'digital-business-card-posts' ),
			'all_items'             => __( 'All Business Cards', 'digital-business-card-posts' ),
			'search_items'          => __( 'Search Business Cards', 'digital-business-card-posts' ),
			'not_found'             => __( 'No business cards found.', 'digital-business-card-posts' ),
			'not_found_in_trash'    => __( 'No business cards found in Trash.', 'digital-business-card-posts' ),
			'featured_image'        => __( 'Contact photo', 'digital-business-card-posts' ),
			'set_featured_image'    => __( 'Set contact photo', 'digital-business-card-posts' ),
			'remove_featured_image' => __( 'Remove contact photo', 'digital-business-card-posts' ),
			'use_featured_image'    => __( 'Use as contact photo', 'digital-business-card-posts' ),
			'item_published'        => __( 'Business card published.', 'digital-business-card-posts' ),
			'item_updated'          => __( 'Business card updated.', 'digital-business-card-posts' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Digital business cards, one per person.', 'digital-business-card-posts' ),
				'public'              => true,
				'exclude_from_search' => true,
				'show_in_rest'        => true,
				'rest_base'           => 'business-cards',
				'menu_position'       => 21,
				'menu_icon'           => 'dashicons-id-alt',
				'supports'            => array( 'title', 'thumbnail', 'author' ),
				'has_archive'         => false,
				'query_var'           => self::QUERY_VAR,
				'rewrite'             => array(
					'slug'       => (string) DBCP_Settings::get( 'rewrite_base' ),
					'with_front' => false,
					'feeds'      => false,
					'pages'      => false,
				),
				'capability_type'     => 'business_card',
				'map_meta_cap'        => true,
				'delete_with_user'    => false,
			)
		);
	}

	/**
	 * Every primitive capability generated for the post type.
	 *
	 * @return string[]
	 */
	public static function all_capabilities(): array {
		return array(
			'edit_business_cards',
			'edit_others_business_cards',
			'edit_private_business_cards',
			'edit_published_business_cards',
			'publish_business_cards',
			'read_private_business_cards',
			'delete_business_cards',
			'delete_others_business_cards',
			'delete_private_business_cards',
			'delete_published_business_cards',
		);
	}

	/**
	 * Capabilities a Card Holder gets: enough to manage their own cards only. See D19.
	 *
	 * @return string[]
	 */
	public static function own_capabilities(): array {
		return array(
			'read',
			'upload_files',
			'edit_business_cards',
			'publish_business_cards',
			'delete_business_cards',
			'edit_published_business_cards',
			'delete_published_business_cards',
		);
	}

	/**
	 * Grant capabilities to the manager roles and create the Card Holder role. Idempotent.
	 *
	 * @return void
	 */
	public static function add_capabilities(): void {
		foreach ( self::MANAGER_ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::all_capabilities() as $cap ) {
				$role->add_cap( $cap );
			}
		}

		$holder = get_role( self::ROLE );
		if ( ! $holder ) {
			$holder = add_role( self::ROLE, __( 'Card Holder', 'digital-business-card-posts' ), array() );
		}
		if ( $holder ) {
			foreach ( self::own_capabilities() as $cap ) {
				$holder->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove the capabilities from every role and delete the Card Holder role. Used on uninstall.
	 *
	 * @return void
	 */
	public static function remove_capabilities(): void {
		$roles = wp_roles();
		foreach ( $roles->role_objects as $role ) {
			foreach ( self::all_capabilities() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
		remove_role( self::ROLE );
	}

	/**
	 * In the admin list, users who cannot edit others' cards see only their own. See D20.
	 *
	 * @param WP_Query $query The query.
	 * @return void
	 */
	public function scope_admin_list( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( current_user_can( 'edit_others_business_cards' ) ) {
			return;
		}
		$query->set( 'author', get_current_user_id() );
	}

	/**
	 * Title placeholder on the edit screen.
	 *
	 * @param string  $placeholder Default placeholder.
	 * @param WP_Post $post        Post being edited.
	 * @return string
	 */
	public function title_placeholder( string $placeholder, WP_Post $post ): string {
		if ( self::POST_TYPE === $post->post_type ) {
			return __( 'Display name, e.g. John Janney (filled from first and last name if empty)', 'digital-business-card-posts' );
		}
		return $placeholder;
	}

	/**
	 * Post updated messages for the post type.
	 *
	 * @param array<string, array<int, string>> $messages Messages by post type.
	 * @return array<string, array<int, string>>
	 */
	public function updated_messages( array $messages ): array {
		$post = get_post();
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return $messages;
		}
		$link = get_permalink( $post );
		$view = sprintf( ' <a href="%s">%s</a>', esc_url( $link ), esc_html__( 'View card', 'digital-business-card-posts' ) );

		$messages[ self::POST_TYPE ] = array(
			0  => '',
			1  => __( 'Business card updated.', 'digital-business-card-posts' ) . $view,
			4  => __( 'Business card updated.', 'digital-business-card-posts' ),
			6  => __( 'Business card published.', 'digital-business-card-posts' ) . $view,
			7  => __( 'Business card saved.', 'digital-business-card-posts' ),
			8  => __( 'Business card submitted.', 'digital-business-card-posts' ),
			10 => __( 'Business card draft updated.', 'digital-business-card-posts' ),
		);
		return $messages;
	}
}

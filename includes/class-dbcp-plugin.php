<?php
/**
 * Plugin bootstrap: wires the components, handles activation, deactivation and admin notices.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class (singleton).
 */
final class DBCP_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var DBCP_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings component.
	 *
	 * @var DBCP_Settings
	 */
	public $settings;

	/**
	 * Post type component.
	 *
	 * @var DBCP_Post_Type
	 */
	public $post_type;

	/**
	 * Meta component.
	 *
	 * @var DBCP_Meta
	 */
	public $meta;

	/**
	 * The vCard endpoint component.
	 *
	 * @var DBCP_VCard
	 */
	public $vcard;

	/**
	 * QR component.
	 *
	 * @var DBCP_QR
	 */
	public $qr;

	/**
	 * Template and shortcode component.
	 *
	 * @var DBCP_Template
	 */
	public $template;

	/**
	 * Get the singleton.
	 *
	 * @return DBCP_Plugin
	 */
	public static function instance(): DBCP_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register_hooks();
		}
		return self::$instance;
	}

	/**
	 * Build the components.
	 */
	private function __construct() {
		$this->settings  = new DBCP_Settings();
		$this->post_type = new DBCP_Post_Type();
		$this->meta      = new DBCP_Meta();
		$this->vcard     = new DBCP_VCard();
		$this->qr        = new DBCP_QR();
		$this->template  = new DBCP_Template();
	}

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ), 1 );
		add_action( 'admin_notices', array( $this, 'gd_notice' ) );

		$this->settings->register_hooks();
		$this->post_type->register_hooks();
		$this->meta->register_hooks();
		$this->vcard->register_hooks();
		$this->qr->register_hooks();
		$this->template->register_hooks();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'digital-business-card-posts', false, dirname( plugin_basename( DBCP_FILE ) ) . '/languages' );
	}

	/**
	 * Whether the GD extension is available.
	 *
	 * @return bool
	 */
	public static function has_gd(): bool {
		return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagepng' );
	}

	/**
	 * Show an admin notice when GD is missing.
	 *
	 * @return void
	 */
	public function gd_notice(): void {
		if ( self::has_gd() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Digital Business Card Posts: the PHP GD extension is not installed. QR codes cannot be generated and contact photos cannot be resized until it is enabled.', 'digital-business-card-posts' )
		);
	}

	/**
	 * Activation: register the post type and rewrite rules, add capabilities and the role,
	 * create the QR cache directory, flush rewrite rules.
	 *
	 * @return void
	 */
	public static function activate(): void {
		DBCP_Post_Type::register();
		DBCP_VCard::add_rewrite_rules();
		DBCP_Post_Type::add_capabilities();
		DBCP_QR::ensure_cache_dir();
		flush_rewrite_rules();
		// Belt and braces: flush again on the next normal request once every plugin has loaded.
		DBCP_Settings::request_flush();
	}

	/**
	 * Deactivation: flush rewrite rules. Roles, capabilities, settings and cards are kept.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}

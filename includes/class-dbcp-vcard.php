<?php
/**
 * The vCard endpoint: rewrite rule, request handling, photo resize and headers.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves /card/{slug}/vcard/ as a vCard 3.0 built from post meta at request time.
 */
class DBCP_VCard {

	/**
	 * Query var that marks a vCard request.
	 */
	const QUERY_VAR = 'dbcp_vcard';

	/**
	 * Photo edge length in pixels.
	 */
	const PHOTO_SIZE = 400;

	/**
	 * JPEG quality for the embedded photo.
	 */
	const PHOTO_QUALITY = 82;

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ), 11 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 0 );
	}

	/**
	 * Add the rewrite rule. Runs after the post type is registered on init, and directly on activation.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules(): void {
		$base = (string) DBCP_Settings::get( 'rewrite_base' );
		add_rewrite_rule(
			'^' . preg_quote( $base, '#' ) . '/([^/]+)/vcard/?$',
			'index.php?' . DBCP_Post_Type::QUERY_VAR . '=$matches[1]&' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/**
	 * Register the query var.
	 *
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public function query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * URL of the vCard for a card.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	public static function get_url( int $post_id ): string {
		$permalink = get_permalink( $post_id );
		if ( ! $permalink ) {
			return '';
		}
		if ( get_option( 'permalink_structure' ) ) {
			return trailingslashit( $permalink ) . 'vcard/';
		}
		return add_query_arg( self::QUERY_VAR, '1', $permalink );
	}

	/**
	 * Serve the vCard when the request asks for one.
	 *
	 * @return void
	 */
	public function maybe_serve(): void {
		if ( '1' !== (string) get_query_var( self::QUERY_VAR, '' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || DBCP_Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			// Unpublished, trashed, missing or not a card: a normal 404 (D16).
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		$vcard = self::build_for_post( $post );

		nocache_headers();
		header( 'Content-Type: text/vcard; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . self::filename( $post ) . '"' );
		header( 'Content-Length: ' . strlen( $vcard ) );
		header( 'X-Robots-Tag: noindex, nofollow' );

		// The body is a vCard, not HTML; it is built by DBCP_VCard_Builder with vCard escaping (D11).
		echo $vcard; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download filename for a card.
	 *
	 * @param WP_Post $post The card.
	 * @return string
	 */
	public static function filename( WP_Post $post ): string {
		$slug = sanitize_file_name( $post->post_name );
		if ( '' === $slug ) {
			$slug = 'card-' . $post->ID;
		}
		return $slug . '.vcf';
	}

	/**
	 * Build the vCard string for a card.
	 *
	 * @param WP_Post $post The card.
	 * @return string
	 */
	public static function build_for_post( WP_Post $post ): string {
		$meta   = DBCP_Meta::get_all( (int) $post->ID );
		$fields = array(
			'first_name'      => (string) $meta['first_name'],
			'last_name'       => (string) $meta['last_name'],
			'full_name'       => (string) $post->post_title,
			'job_title'       => (string) $meta['job_title'],
			'company'         => (string) $meta['company'],
			'phone_work'      => (string) $meta['phone_work'],
			'phone_mobile'    => (string) $meta['phone_mobile'],
			'email'           => (string) $meta['email'],
			'website'         => (string) $meta['website'],
			'address_street'  => (string) $meta['address_street'],
			'address_suite'   => (string) $meta['address_suite'],
			'address_city'    => (string) $meta['address_city'],
			'address_state'   => (string) $meta['address_state'],
			'address_postal'  => (string) $meta['address_postal'],
			'address_country' => (string) $meta['address_country'],
			'revision'        => (int) get_post_modified_time( 'U', true, $post ),
		);

		/**
		 * Filter the fields passed to the vCard builder.
		 *
		 * @param array<string, string> $fields Raw (unescaped) field values.
		 * @param WP_Post               $post   The card.
		 */
		$fields = apply_filters( 'dbcp_vcard_fields', $fields, $post );

		$photo = self::photo_jpeg( (int) get_post_thumbnail_id( $post ) );

		return DBCP_VCard_Builder::build( $fields, $photo, 'JPEG' );
	}

	/**
	 * Resize the featured image to a square JPEG in memory (D7, D23).
	 *
	 * @param int $attachment_id Attachment ID, 0 for none.
	 * @return string Binary JPEG data, or empty string.
	 */
	public static function photo_jpeg( int $attachment_id ): string {
		if ( ! $attachment_id ) {
			return '';
		}
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return '';
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return '';
		}

		$size    = (int) apply_filters( 'dbcp_vcard_photo_size', self::PHOTO_SIZE );
		$quality = (int) apply_filters( 'dbcp_vcard_photo_quality', self::PHOTO_QUALITY );

		$result = $editor->resize( $size, $size, true );
		if ( is_wp_error( $result ) ) {
			return '';
		}
		$editor->set_quality( $quality );

		ob_start();
		$streamed = $editor->stream( 'image/jpeg' );
		$data     = (string) ob_get_clean();

		if ( is_wp_error( $streamed ) || '' === $data ) {
			return '';
		}
		return $data;
	}
}

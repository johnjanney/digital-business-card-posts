<?php
/**
 * Card page template, card data and shortcode.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the card page through template_include and provides the card markup.
 */
class DBCP_Template {

	/**
	 * Stylesheet handle.
	 */
	const STYLE_HANDLE = 'dbcp-card';

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_action( 'template_redirect', array( $this, 'card_page_headers' ) );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_style' ) );
		add_shortcode( 'digital_business_card', array( $this, 'shortcode' ) );
	}

	/**
	 * The [digital_business_card id="123"] shortcode: embeds a published card.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ): string {
		$atts    = shortcode_atts( array( 'id' => 0 ), is_array( $atts ) ? $atts : array(), 'digital_business_card' );
		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post || DBCP_Post_Type::POST_TYPE !== $post->post_type ) {
			return '';
		}
		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post_id ) ) {
			return '';
		}
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_style();
		}
		wp_enqueue_style( self::STYLE_HANDLE );
		return '<div class="dbcp-embed">' . self::render_card( $post_id ) . '</div>';
	}

	/**
	 * Whether the current request is a single card page.
	 *
	 * @return bool
	 */
	public static function is_card_page(): bool {
		return is_singular( DBCP_Post_Type::POST_TYPE );
	}

	/**
	 * Register the card stylesheet (enqueued by the page template and the shortcode).
	 *
	 * @return void
	 */
	public function register_style(): void {
		wp_register_style( self::STYLE_HANDLE, DBCP_URL . 'assets/card.css', array(), DBCP_VERSION );
	}

	/**
	 * Use the plugin template for single cards. See D13 and D21.
	 *
	 * @param string $template Template chosen by WordPress.
	 * @return string
	 */
	public function template_include( string $template ): string {
		if ( ! self::is_card_page() ) {
			return $template;
		}
		$path = self::locate( 'single' );
		return is_readable( $path ) ? $path : $template;
	}

	/**
	 * Resolve a template file, letting themes and plugins override it.
	 *
	 * @param string $name 'single' (full page) or 'card' (the card partial).
	 * @return string Absolute path.
	 */
	public static function locate( string $name ): string {
		$file = 'card' === $name ? 'card.php' : 'single-business-card.php';
		$path = DBCP_DIR . 'templates/' . $file;

		/**
		 * Filter the template file used to render a card.
		 *
		 * @param string       $path Absolute path to the template.
		 * @param string       $name 'single' for the full page, 'card' for the card partial.
		 * @param WP_Post|null $post The card being rendered, when known.
		 */
		return (string) apply_filters( 'dbcp_template_path', $path, $name, get_post() );
	}

	/**
	 * Send the X-Robots-Tag header on noindex card pages.
	 *
	 * @return void
	 */
	public function card_page_headers(): void {
		if ( ! self::is_card_page() || headers_sent() ) {
			return;
		}
		if ( DBCP_Meta::get( get_queried_object_id(), 'noindex' ) ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		}
	}

	/**
	 * The card page is a standalone document; do not show the admin bar on it.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public function hide_admin_bar( bool $show ): bool {
		return self::is_card_page() ? false : $show;
	}

	/**
	 * Everything the templates need, unescaped. Escape at output.
	 *
	 * @param int $post_id Card ID.
	 * @return array<string, mixed>
	 */
	public static function get_card_data( int $post_id ): array {
		$post = get_post( $post_id );
		$meta = DBCP_Meta::get_all( $post_id );

		$name = $post ? trim( (string) $post->post_title ) : '';
		if ( '' === $name ) {
			$name = trim( $meta['first_name'] . ' ' . $meta['last_name'] );
		}

		$address_query = array_filter(
			array(
				$meta['address_street'],
				$meta['address_suite'],
				$meta['address_city'],
				$meta['address_state'],
				$meta['address_postal'],
				$meta['address_country'],
			),
			'strlen'
		);

		$line1 = implode( ', ', array_filter( array( $meta['address_street'], $meta['address_suite'] ), 'strlen' ) );
		$line2 = trim( implode( ', ', array_filter( array( $meta['address_city'], $meta['address_state'] ), 'strlen' ) ) . ' ' . $meta['address_postal'] );
		$lines = array_values( array_filter( array( $line1, $line2, $meta['address_country'] ), 'strlen' ) );

		$accent = (string) $meta['accent_color'];
		if ( '' === $accent ) {
			$accent = (string) DBCP_Settings::get( 'default_accent_color' );
		}

		$logo = null;
		if ( $meta['logo_id'] ) {
			$src = wp_get_attachment_image_src( (int) $meta['logo_id'], 'full' );
			if ( $src ) {
				$logo = array(
					'id'     => (int) $meta['logo_id'],
					'url'    => $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
					'alt'    => '' !== $meta['company'] ? $meta['company'] : $name,
				);
			}
		}

		$title_parts = array_filter( array( $meta['job_title'], $meta['company'] ), 'strlen' );

		$data = array(
			'id'                => $post_id,
			'name'              => $name,
			'job_title'         => $meta['job_title'],
			'company'           => $meta['company'],
			'tagline'           => $meta['tagline'],
			'phone_work'        => $meta['phone_work'],
			'phone_work_href'   => self::tel_href( $meta['phone_work'] ),
			'phone_mobile'      => $meta['phone_mobile'],
			'phone_mobile_href' => self::tel_href( $meta['phone_mobile'] ),
			'email'             => $meta['email'],
			'website'           => $meta['website'],
			'website_display'   => self::website_display( $meta['website'] ),
			'address_lines'     => $lines,
			'maps_url'          => $address_query ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( implode( ', ', $address_query ) ) : '',
			'logo'              => $logo,
			'accent'            => $accent,
			'accent_press'      => self::shade( $accent, -0.1 ),
			'accent_ink'        => self::readable_on( $accent ),
			'noindex'           => (bool) $meta['noindex'],
			'permalink'         => (string) get_permalink( $post_id ),
			'vcard_url'         => DBCP_VCard::get_url( $post_id ),
			'document_title'    => $title_parts ? $name . ' — ' . implode( ', ', $title_parts ) : $name,
			'description'       => self::description( $name, $meta['job_title'], $meta['company'] ),
		);

		/**
		 * Filter the data passed to the card templates.
		 *
		 * @param array<string, mixed> $data    Card data, unescaped.
		 * @param int                  $post_id Card ID.
		 */
		return (array) apply_filters( 'dbcp_card_data', $data, $post_id );
	}

	/**
	 * Meta description text.
	 *
	 * @param string $name    Display name.
	 * @param string $title   Job title.
	 * @param string $company Company.
	 * @return string
	 */
	private static function description( string $name, string $title, string $company ): string {
		if ( '' !== $title && '' !== $company ) {
			/* translators: 1: name, 2: job title, 3: company */
			return sprintf( __( 'Contact card for %1$s, %2$s at %3$s.', 'digital-business-card-posts' ), $name, $title, $company );
		}
		if ( '' !== $company ) {
			/* translators: 1: name, 2: company */
			return sprintf( __( 'Contact card for %1$s at %2$s.', 'digital-business-card-posts' ), $name, $company );
		}
		if ( '' !== $title ) {
			/* translators: 1: name, 2: job title */
			return sprintf( __( 'Contact card for %1$s, %2$s.', 'digital-business-card-posts' ), $name, $title );
		}
		/* translators: %s: name */
		return sprintf( __( 'Contact card for %s.', 'digital-business-card-posts' ), $name );
	}

	/**
	 * The tel: href for a phone number (D22).
	 *
	 * @param string $phone Phone as typed.
	 * @return string
	 */
	public static function tel_href( string $phone ): string {
		$normalized = DBCP_VCard_Builder::normalize_phone( $phone );
		return '' === $normalized ? '' : 'tel:' . $normalized;
	}

	/**
	 * Website text for display: no scheme, no trailing slash.
	 *
	 * @param string $url Website URL.
	 * @return string
	 */
	public static function website_display( string $url ): string {
		$display = preg_replace( '#^https?://#i', '', trim( $url ) );
		return rtrim( (string) $display, '/' );
	}

	/**
	 * Lighten (positive) or darken (negative) a hex color by a fraction.
	 *
	 * @param string $hex    Color like #f7c600.
	 * @param float  $amount -1..1.
	 * @return string
	 */
	public static function shade( string $hex, float $amount ): string {
		$rgb = self::hex_to_rgb( $hex );
		if ( null === $rgb ) {
			return $hex;
		}
		$out = '#';
		foreach ( $rgb as $channel ) {
			$value = $amount < 0 ? $channel * ( 1 + $amount ) : $channel + ( 255 - $channel ) * $amount;
			$out  .= str_pad( dechex( (int) round( max( 0, min( 255, $value ) ) ) ), 2, '0', STR_PAD_LEFT );
		}
		return $out;
	}

	/**
	 * Near-black or white, whichever reads better on the given color.
	 *
	 * @param string $hex Background color.
	 * @return string
	 */
	public static function readable_on( string $hex ): string {
		$rgb = self::hex_to_rgb( $hex );
		if ( null === $rgb ) {
			return '#161616';
		}
		$lum = ( 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2] ) / 255;
		return $lum > 0.5 ? '#161616' : '#ffffff';
	}

	/**
	 * Parse #rgb or #rrggbb.
	 *
	 * @param string $hex Color.
	 * @return int[]|null [r, g, b] or null when invalid.
	 */
	private static function hex_to_rgb( string $hex ): ?array {
		$hex = ltrim( trim( $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
			return null;
		}
		return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	}

	/**
	 * Render the card partial for a card and return the markup.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	public static function render_card( int $post_id ): string {
		$path = self::locate( 'card' );
		if ( ! is_readable( $path ) ) {
			return '';
		}
		$dbcp_card = self::get_card_data( $post_id );
		ob_start();
		include $path;
		return (string) ob_get_clean();
	}

	/**
	 * Inline SVG icon markup for the card. Static, trusted markup.
	 *
	 * @param string $name save, phone, mobile, email, website, map.
	 * @return string
	 */
	public static function icon( string $name ): string {
		$paths = array(
			'save'    => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/>',
			'phone'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.8 2z"/>',
			'mobile'  => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M11 18h2"/>',
			'email'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/>',
			'website' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
			'map'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		$stroke = 'save' === $name ? '2' : '1.75';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $stroke . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}

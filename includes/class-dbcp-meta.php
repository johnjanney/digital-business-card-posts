<?php
/**
 * Card fields: registration with register_post_meta(), the meta box and saving.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card fields: registration, meta box and save.
 */
class DBCP_Meta {

	/**
	 * Nonce action for the meta box.
	 */
	const NONCE_ACTION = 'dbcp_save_card';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'dbcp_card_nonce';

	/**
	 * Name of the POST array carrying the fields.
	 */
	const POST_KEY = 'dbcp';

	/**
	 * Field definitions, keyed by meta key.
	 *
	 * Each field: label, type (text, tel, email, url, color, checkbox, attachment), rest type,
	 * sanitize callable, default, group, optional description.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		$text = 'sanitize_text_field';

		return array(
			'first_name'      => array(
				'label'    => __( 'First name', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'name',
				'required' => true,
			),
			'last_name'       => array(
				'label'    => __( 'Last name', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'name',
				'required' => true,
			),
			'job_title'       => array(
				'label'    => __( 'Job title', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'position',
			),
			'company'         => array(
				'label'    => __( 'Company', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'position',
			),
			'tagline'         => array(
				'label'       => __( 'Tagline', 'digital-business-card-posts' ),
				'type'        => 'text',
				'rest'        => 'string',
				'sanitize'    => $text,
				'default'     => '',
				'group'       => 'position',
				'description' => __( 'Optional. Shown in italics under the company. Not included in the vCard.', 'digital-business-card-posts' ),
			),
			'phone_work'      => array(
				'label'       => __( 'Work phone', 'digital-business-card-posts' ),
				'type'        => 'tel',
				'rest'        => 'string',
				'sanitize'    => $text,
				'default'     => '',
				'group'       => 'contact',
				'description' => __( 'Type it as it should be displayed, including the country code, e.g. +1 214-810-1131.', 'digital-business-card-posts' ),
			),
			'phone_mobile'    => array(
				'label'    => __( 'Mobile phone', 'digital-business-card-posts' ),
				'type'     => 'tel',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'contact',
			),
			'email'           => array(
				'label'    => __( 'Email', 'digital-business-card-posts' ),
				'type'     => 'email',
				'rest'     => 'string',
				'sanitize' => 'sanitize_email',
				'default'  => '',
				'group'    => 'contact',
			),
			'website'         => array(
				'label'       => __( 'Website', 'digital-business-card-posts' ),
				'type'        => 'url',
				'rest'        => 'string',
				'sanitize'    => array( __CLASS__, 'sanitize_url' ),
				'default'     => '',
				'group'       => 'contact',
				'description' => __( 'Full URL including https://. The card shows only the host name.', 'digital-business-card-posts' ),
			),
			'address_street'  => array(
				'label'    => __( 'Street', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'address_suite'   => array(
				'label'    => __( 'Suite', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'address_city'    => array(
				'label'    => __( 'City', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'address_state'   => array(
				'label'    => __( 'State', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'address_postal'  => array(
				'label'    => __( 'Postal code', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'address_country' => array(
				'label'    => __( 'Country', 'digital-business-card-posts' ),
				'type'     => 'text',
				'rest'     => 'string',
				'sanitize' => $text,
				'default'  => '',
				'group'    => 'address',
			),
			'logo_id'         => array(
				'label'       => __( 'Logo', 'digital-business-card-posts' ),
				'type'        => 'attachment',
				'rest'        => 'integer',
				'sanitize'    => 'absint',
				'default'     => 0,
				'group'       => 'appearance',
				'description' => __( 'Optional. Shown at the top left of the card, 96 px tall. Use a trimmed PNG with a transparent background, at least 400 px wide.', 'digital-business-card-posts' ),
			),
			'accent_color'    => array(
				'label'       => __( 'Accent color', 'digital-business-card-posts' ),
				'type'        => 'color',
				'rest'        => 'string',
				'sanitize'    => array( __CLASS__, 'sanitize_color' ),
				'default'     => (string) DBCP_Settings::get( 'default_accent_color' ),
				'group'       => 'appearance',
				'description' => __( 'Used for the square after the name, the Save contact button and focus outlines.', 'digital-business-card-posts' ),
			),
			'noindex'         => array(
				'label'    => __( 'Search engines', 'digital-business-card-posts' ),
				'type'     => 'checkbox',
				'rest'     => 'boolean',
				'sanitize' => 'rest_sanitize_boolean',
				'default'  => (bool) DBCP_Settings::get( 'default_noindex' ),
				'group'    => 'appearance',
				'checkbox' => __( 'Hide this card from search engines (noindex)', 'digital-business-card-posts' ),
			),
		);
	}

	/**
	 * Sanitize a URL for storage.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_url( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( ! preg_match( '#^https?://#i', $value ) ) {
			$value = 'https://' . $value;
		}
		return esc_url_raw( $value );
	}

	/**
	 * Sanitize a hex color; empty string when invalid.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_color( $value ): string {
		$color = sanitize_hex_color( trim( (string) $value ) );
		return is_string( $color ) ? $color : '';
	}

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes_' . DBCP_Post_Type::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . DBCP_Post_Type::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'default_title' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register every field with register_post_meta(), exposed to REST.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( self::fields() as $key => $field ) {
			$sanitize = $field['sanitize'];
			register_post_meta(
				DBCP_Post_Type::POST_TYPE,
				$key,
				array(
					'type'              => $field['rest'],
					'description'       => $field['label'],
					'single'            => true,
					'default'           => $field['default'],
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ) use ( $sanitize ) {
						return call_user_func( $sanitize, $value );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}

	/**
	 * Get one field value with its default applied.
	 *
	 * @param int    $post_id Card ID.
	 * @param string $key     Meta key.
	 * @return mixed
	 */
	public static function get( int $post_id, string $key ) {
		$fields = self::fields();
		if ( ! isset( $fields[ $key ] ) ) {
			return null;
		}
		$field = $fields[ $key ];
		if ( ! metadata_exists( 'post', $post_id, $key ) ) {
			return $field['default'];
		}
		$value = call_user_func( $field['sanitize'], get_post_meta( $post_id, $key, true ) );
		if ( 'accent_color' === $key && '' === $value ) {
			return $field['default'];
		}
		return $value;
	}

	/**
	 * Get every field value for a card.
	 *
	 * @param int $post_id Card ID.
	 * @return array<string, mixed>
	 */
	public static function get_all( int $post_id ): array {
		$values = array();
		foreach ( array_keys( self::fields() ) as $key ) {
			$values[ $key ] = self::get( $post_id, $key );
		}
		return $values;
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'dbcp_card_details',
			__( 'Card details', 'digital-business-card-posts' ),
			array( $this, 'render_meta_box' ),
			DBCP_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Group headings for the meta box.
	 *
	 * @return array<string, string>
	 */
	private static function groups(): array {
		return array(
			'name'       => __( 'Name', 'digital-business-card-posts' ),
			'position'   => __( 'Position', 'digital-business-card-posts' ),
			'contact'    => __( 'Contact', 'digital-business-card-posts' ),
			'address'    => __( 'Address', 'digital-business-card-posts' ),
			'appearance' => __( 'Appearance and visibility', 'digital-business-card-posts' ),
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post The card.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$fields = self::fields();
		$values = self::get_all( (int) $post->ID );

		foreach ( self::groups() as $group => $heading ) {
			echo '<fieldset class="dbcp-group dbcp-group-' . esc_attr( $group ) . '">';
			echo '<legend>' . esc_html( $heading ) . '</legend>';
			echo '<div class="dbcp-fields">';
			foreach ( $fields as $key => $field ) {
				if ( $field['group'] !== $group ) {
					continue;
				}
				$this->render_field( $key, $field, $values[ $key ] );
			}
			echo '</div>';
			echo '</fieldset>';
		}
	}

	/**
	 * Render one field.
	 *
	 * @param string               $key   Meta key.
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Current value.
	 * @return void
	 */
	private function render_field( string $key, array $field, $value ): void {
		$id   = 'dbcp_' . $key;
		$name = self::POST_KEY . '[' . $key . ']';

		echo '<div class="dbcp-field dbcp-field-' . esc_attr( $field['type'] ) . '">';

		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					'<span class="dbcp-label">%s</span><label for="%s"><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
					esc_html( $field['label'] ),
					esc_attr( $id ),
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( $field['checkbox'] )
				);
				break;

			case 'attachment':
				$logo_id = (int) $value;
				$src     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
				printf( '<span class="dbcp-label">%s</span>', esc_html( $field['label'] ) );
				echo '<div class="dbcp-media" data-title="' . esc_attr__( 'Select logo', 'digital-business-card-posts' ) . '" data-button="' . esc_attr__( 'Use this logo', 'digital-business-card-posts' ) . '">';
				printf( '<input type="hidden" id="%s" name="%s" value="%s" class="dbcp-media-id">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $logo_id ) );
				printf(
					'<div class="dbcp-media-preview"%s>%s</div>',
					$src ? '' : ' hidden',
					$src ? '<img src="' . esc_url( $src ) . '" alt="">' : ''
				);
				printf(
					'<button type="button" class="button dbcp-media-select">%s</button> <button type="button" class="button-link dbcp-media-remove"%s>%s</button>',
					esc_html__( 'Select logo', 'digital-business-card-posts' ),
					$src ? '' : ' hidden',
					esc_html__( 'Remove', 'digital-business-card-posts' )
				);
				echo '</div>';
				break;

			case 'color':
				printf(
					'<label for="%s" class="dbcp-label">%s</label><input type="text" id="%s" name="%s" value="%s" class="dbcp-color-field" data-default-color="%s">',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) $field['default'] )
				);
				break;

			default:
				printf(
					'<label for="%s" class="dbcp-label">%s%s</label><input type="%s" id="%s" name="%s" value="%s" class="regular-text"%s>',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					! empty( $field['required'] ) ? ' <span class="required" aria-hidden="true">*</span>' : '',
					esc_attr( $field['type'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					'url' === $field['type'] ? ' placeholder="https://"' : ''
				);
				break;
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Whether the current request carries a valid meta box submission.
	 *
	 * @return bool
	 */
	private function is_valid_submission(): bool {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return false;
		}
		return (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION );
	}

	/**
	 * Save the fields.
	 *
	 * @param int     $post_id Card ID.
	 * @param WP_Post $post    The card.
	 * @return void
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( ! $this->is_valid_submission() ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( DBCP_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Nonce verified in is_valid_submission(); every value goes through the field's sanitize callback below.
		$input = isset( $_POST[ self::POST_KEY ] ) && is_array( $_POST[ self::POST_KEY ] ) ? wp_unslash( $_POST[ self::POST_KEY ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

		foreach ( self::fields() as $key => $field ) {
			$raw = $input[ $key ] ?? '';
			if ( is_array( $raw ) ) {
				$raw = '';
			}
			$value = call_user_func( $field['sanitize'], $raw );

			if ( 'attachment' === $field['type'] && $value && ! wp_attachment_is_image( (int) $value ) ) {
				$value = 0;
			}

			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Fill an empty title from first and last name so the slug is derived from the person's name.
	 *
	 * @param array<string, mixed> $data    Slashed post data about to be inserted.
	 * @param array<string, mixed> $postarr Raw post array.
	 * @return array<string, mixed>
	 */
	public function default_title( array $data, array $postarr ): array {
		if ( DBCP_Post_Type::POST_TYPE !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}
		if ( '' !== trim( (string) ( $data['post_title'] ?? '' ) ) ) {
			return $data;
		}
		if ( ! $this->is_valid_submission() ) {
			return $data;
		}
		$post_id = (int) ( $postarr['ID'] ?? 0 );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return $data;
		}

		// Nonce verified in is_valid_submission(); both values are sanitized right here.
		$input = isset( $_POST[ self::POST_KEY ] ) && is_array( $_POST[ self::POST_KEY ] ) ? wp_unslash( $_POST[ self::POST_KEY ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		$first = sanitize_text_field( (string) ( $input['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $input['last_name'] ?? '' ) );
		$name  = trim( $first . ' ' . $last );
		if ( '' !== $name ) {
			$data['post_title'] = wp_slash( $name );
			if ( '' === (string) ( $data['post_name'] ?? '' ) && in_array( $data['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
				$data['post_name'] = sanitize_title( $name );
			}
		}
		return $data;
	}

	/**
	 * Enqueue the admin script and styles on the card edit screen and the settings page.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		$is_card_screen = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && DBCP_Post_Type::POST_TYPE === get_current_screen()->post_type;
		$is_settings    = 'settings_page_dbcp-settings' === $hook;
		if ( ! $is_card_screen && ! $is_settings ) {
			return;
		}
		if ( $is_card_screen ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'dbcp-admin', DBCP_URL . 'assets/admin.css', array(), DBCP_VERSION );
		wp_enqueue_script( 'dbcp-admin', DBCP_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), DBCP_VERSION, true );
	}
}

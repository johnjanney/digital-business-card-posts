<?php
/**
 * Plugin settings: defaults, option access, settings page and rewrite flush.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings → Digital Business Cards.
 */
class DBCP_Settings {

	/**
	 * Option name holding all settings as an array.
	 */
	const OPTION = 'dbcp_settings';

	/**
	 * Option name of the "flush rewrite rules on next load" flag.
	 */
	const FLUSH_FLAG = 'dbcp_flush_rewrite';

	/**
	 * Default values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'rewrite_base'         => 'card',
			'default_accent_color' => '#f7c600',
			'default_noindex'      => true,
			'delete_on_uninstall'  => false,
		);
	}

	/**
	 * Return every setting merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * Return one setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Sanitize a settings array. Used as the register_setting sanitize callback and on read.
	 *
	 * @param mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$base = isset( $input['rewrite_base'] ) ? sanitize_title( (string) $input['rewrite_base'] ) : '';
		$base = trim( $base, '-/' );
		if ( '' === $base ) {
			$base = $defaults['rewrite_base'];
		}

		$color = isset( $input['default_accent_color'] ) ? sanitize_hex_color( (string) $input['default_accent_color'] ) : '';
		if ( empty( $color ) ) {
			$color = $defaults['default_accent_color'];
		}

		return array(
			'rewrite_base'         => $base,
			'default_accent_color' => $color,
			'default_noindex'      => ! empty( $input['default_noindex'] ),
			'delete_on_uninstall'  => ! empty( $input['delete_on_uninstall'] ),
		);
	}

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'update_option_' . self::OPTION, array( $this, 'on_update' ), 10, 2 );
		add_action( 'add_option_' . self::OPTION, array( $this, 'request_flush' ) );
		add_action( 'init', array( $this, 'maybe_flush' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( DBCP_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=dbcp-settings' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'digital-business-card-posts' ) . '</a>' );
		return $links;
	}

	/**
	 * Set the flag so rewrite rules are flushed on the next request, after the post type is registered.
	 *
	 * @return void
	 */
	public static function request_flush(): void {
		update_option( self::FLUSH_FLAG, '1', false );
	}

	/**
	 * Flush rewrite rules once if the flag is set. Runs on init after register_post_type.
	 *
	 * @return void
	 */
	public function maybe_flush(): void {
		if ( '1' === get_option( self::FLUSH_FLAG ) ) {
			delete_option( self::FLUSH_FLAG );
			flush_rewrite_rules();
		}
	}

	/**
	 * When the rewrite base changes, flush rewrite rules.
	 *
	 * @param mixed $old_value Previous settings.
	 * @param mixed $new_value New settings.
	 * @return void
	 */
	public function on_update( $old_value, $new_value ): void {
		$old = is_array( $old_value ) ? array_merge( self::defaults(), $old_value ) : self::defaults();
		$new = is_array( $new_value ) ? array_merge( self::defaults(), $new_value ) : self::defaults();
		if ( $old['rewrite_base'] !== $new['rewrite_base'] ) {
			self::request_flush();
		}
	}

	/**
	 * Register the setting and its fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'dbcp_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'dbcp_main',
			__( 'Card pages', 'digital-business-card-posts' ),
			'__return_false',
			'dbcp_settings'
		);

		add_settings_field(
			'rewrite_base',
			__( 'Card URL base', 'digital-business-card-posts' ),
			array( $this, 'field_rewrite_base' ),
			'dbcp_settings',
			'dbcp_main',
			array( 'label_for' => 'dbcp_rewrite_base' )
		);
		add_settings_field(
			'default_accent_color',
			__( 'Default accent color', 'digital-business-card-posts' ),
			array( $this, 'field_accent_color' ),
			'dbcp_settings',
			'dbcp_main',
			array( 'label_for' => 'dbcp_default_accent_color' )
		);
		add_settings_field(
			'default_noindex',
			__( 'Search engines', 'digital-business-card-posts' ),
			array( $this, 'field_noindex' ),
			'dbcp_settings',
			'dbcp_main'
		);
		add_settings_field(
			'delete_on_uninstall',
			__( 'Uninstall', 'digital-business-card-posts' ),
			array( $this, 'field_delete' ),
			'dbcp_settings',
			'dbcp_main'
		);
	}

	/**
	 * Add the options page under Settings.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_options_page(
			__( 'Digital Business Cards', 'digital-business-card-posts' ),
			__( 'Digital Business Cards', 'digital-business-card-posts' ),
			'manage_options',
			'dbcp-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the options page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Digital Business Cards', 'digital-business-card-posts' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'dbcp_settings_group' );
				do_settings_sections( 'dbcp_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Rewrite base field.
	 *
	 * @return void
	 */
	public function field_rewrite_base(): void {
		$value = (string) self::get( 'rewrite_base' );
		?>
		<code><?php echo esc_html( home_url( '/' ) ); ?></code>
		<input type="text" id="dbcp_rewrite_base" name="<?php echo esc_attr( self::OPTION ); ?>[rewrite_base]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" pattern="[a-z0-9-]+" required>
		<code>/john-janney/</code>
		<p class="description"><?php esc_html_e( 'Lowercase letters, numbers and hyphens. Changing it flushes rewrite rules; old card URLs stop working and QR codes are regenerated as each card is opened.', 'digital-business-card-posts' ); ?></p>
		<?php
	}

	/**
	 * Default accent color field.
	 *
	 * @return void
	 */
	public function field_accent_color(): void {
		$value = (string) self::get( 'default_accent_color' );
		?>
		<input type="text" id="dbcp_default_accent_color" name="<?php echo esc_attr( self::OPTION ); ?>[default_accent_color]" value="<?php echo esc_attr( $value ); ?>" class="dbcp-color-field" data-default-color="#f7c600">
		<p class="description"><?php esc_html_e( 'Used for new cards. Existing cards keep their own accent color.', 'digital-business-card-posts' ); ?></p>
		<?php
	}

	/**
	 * Default noindex field.
	 *
	 * @return void
	 */
	public function field_noindex(): void {
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[default_noindex]" value="1" <?php checked( (bool) self::get( 'default_noindex' ) ); ?>>
			<?php esc_html_e( 'Hide new cards from search engines (noindex) by default', 'digital-business-card-posts' ); ?>
		</label>
		<?php
	}

	/**
	 * Delete-on-uninstall field.
	 *
	 * @return void
	 */
	public function field_delete(): void {
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[delete_on_uninstall]" value="1" <?php checked( (bool) self::get( 'delete_on_uninstall' ) ); ?>>
			<?php esc_html_e( 'Delete all business cards when the plugin is deleted', 'digital-business-card-posts' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'The Card Holder role, capabilities, settings and the QR code cache are always removed on uninstall.', 'digital-business-card-posts' ); ?></p>
		<?php
	}
}

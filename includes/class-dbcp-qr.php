<?php
/**
 * QR code generation, cache and edit-screen box.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a QR PNG of each card's permalink with phpqrcode, cached under uploads (D9, D12, D17).
 */
class DBCP_QR {

	/**
	 * Folder name under wp-content/uploads.
	 */
	const CACHE_DIR = 'digital-business-card-posts';

	/**
	 * Meta key holding the URL encoded in the cached PNG.
	 */
	const META_URL = '_dbcp_qr_url';

	/**
	 * Pixels per module.
	 */
	const MODULE_SIZE = 12;

	/**
	 * Quiet zone in modules.
	 */
	const MARGIN = 2;

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes_' . DBCP_Post_Type::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . DBCP_Post_Type::POST_TYPE, array( $this, 'on_save' ), 20, 2 );
		add_action( 'before_delete_post', array( $this, 'on_delete' ), 10, 2 );
	}

	/**
	 * Absolute path of the cache directory.
	 *
	 * @return string
	 */
	public static function cache_dir(): string {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . self::CACHE_DIR;
	}

	/**
	 * URL of the cache directory.
	 *
	 * @return string
	 */
	public static function cache_url(): string {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['baseurl'] ) . self::CACHE_DIR;
	}

	/**
	 * Create the cache directory with an index.html guard. Returns false when it cannot be created.
	 *
	 * @return bool
	 */
	public static function ensure_cache_dir(): bool {
		$dir = self::cache_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		$index = trailingslashit( $dir ) . 'index.html';
		if ( ! file_exists( $index ) ) {
			// Small guard file, written once. WP_Filesystem is not initialised on activation.
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		return wp_is_writable( $dir );
	}

	/**
	 * Delete the cache directory and everything in it.
	 *
	 * @return void
	 */
	public static function delete_cache_dir(): void {
		$dir = self::cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( trailingslashit( $dir ) . '*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- own cache directory, created with wp_mkdir_p.
	}

	/**
	 * Cached PNG path for a card.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	public static function path( int $post_id ): string {
		return trailingslashit( self::cache_dir() ) . $post_id . '.png';
	}

	/**
	 * Public URL of the cached PNG for a card.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	public static function url( int $post_id ): string {
		return trailingslashit( self::cache_url() ) . $post_id . '.png';
	}

	/**
	 * Return the PNG path for a card, generating or regenerating it when the permalink changed
	 * or the file is missing. Returns empty string when the card is not published or GD is missing.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	public static function get( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post || DBCP_Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}
		$url  = (string) get_permalink( $post_id );
		$path = self::path( $post_id );

		$cached_url = (string) get_post_meta( $post_id, self::META_URL, true );
		if ( $cached_url === $url && file_exists( $path ) ) {
			return $path;
		}
		return self::generate( $post_id, $url ) ? $path : '';
	}

	/**
	 * Generate the PNG for a URL and record the URL in meta.
	 *
	 * @param int    $post_id Card ID.
	 * @param string $url     Text to encode.
	 * @return bool
	 */
	public static function generate( int $post_id, string $url ): bool {
		if ( '' === $url || ! DBCP_Plugin::has_gd() || ! self::ensure_cache_dir() ) {
			return false;
		}
		self::load_library();

		$path = self::path( $post_id );

		/**
		 * Filter the pixel size of one QR module.
		 *
		 * @param int $size Pixels per module.
		 */
		$size = max( 1, (int) apply_filters( 'dbcp_qr_module_size', self::MODULE_SIZE ) );

		try {
			QRcode::png( $url, $path, QR_ECLEVEL_H, $size, self::MARGIN );
		} catch ( Exception $e ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return false;
		}
		update_post_meta( $post_id, self::META_URL, $url );
		return true;
	}

	/**
	 * Load phpqrcode once. It defines global constants and classes, so it is loaded lazily.
	 *
	 * @return void
	 */
	private static function load_library(): void {
		if ( ! class_exists( 'QRcode', false ) ) {
			require_once DBCP_DIR . 'vendor/phpqrcode/phpqrcode.php';
		}
	}

	/**
	 * Regenerate after save when the card is published (permalink may have changed).
	 *
	 * @param int     $post_id Card ID.
	 * @param WP_Post $post    The card.
	 * @return void
	 */
	public function on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		self::get( $post_id );
	}

	/**
	 * Remove the cached PNG when a card is deleted.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public function on_delete( int $post_id, WP_Post $post ): void {
		if ( DBCP_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}
		$path = self::path( $post_id );
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Register the QR meta box in the sidebar.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'dbcp_qr',
			__( 'QR code', 'digital-business-card-posts' ),
			array( $this, 'render_meta_box' ),
			DBCP_Post_Type::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the QR meta box.
	 *
	 * @param WP_Post $post The card.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		echo '<div class="dbcp-qr">';

		if ( 'publish' !== $post->post_status ) {
			echo '<p>' . esc_html__( 'Publish the card to generate its QR code.', 'digital-business-card-posts' ) . '</p>';
			echo '</div>';
			return;
		}
		if ( ! DBCP_Plugin::has_gd() ) {
			echo '<p>' . esc_html__( 'The PHP GD extension is required to generate QR codes.', 'digital-business-card-posts' ) . '</p>';
			echo '</div>';
			return;
		}

		$path = self::get( (int) $post->ID );
		if ( '' === $path ) {
			echo '<p>' . esc_html__( 'The QR code could not be generated. Check that the uploads folder is writable.', 'digital-business-card-posts' ) . '</p>';
			echo '</div>';
			return;
		}

		$url       = self::url( (int) $post->ID ) . '?v=' . (int) filemtime( $path );
		$permalink = (string) get_permalink( $post );
		$filename  = sanitize_file_name( $post->post_name ? $post->post_name . '-qr.png' : 'card-' . $post->ID . '-qr.png' );

		printf( '<img src="%s" alt="%s" width="240" height="240">', esc_url( $url ), esc_attr__( 'QR code for this card', 'digital-business-card-posts' ) );
		printf( '<p class="dbcp-qr-url"><a href="%1$s" target="_blank" rel="noopener">%1$s</a></p>', esc_url( $permalink ) );
		printf(
			'<p class="dbcp-qr-actions"><a class="button button-secondary" href="%s" download="%s">%s</a></p>',
			esc_url( $url ),
			esc_attr( $filename ),
			esc_html__( 'Download PNG', 'digital-business-card-posts' )
		);
		echo '<p class="description">' . esc_html__( 'Error-correction level H. Regenerated automatically when the card URL changes.', 'digital-business-card-posts' ) . '</p>';
		echo '</div>';
	}
}

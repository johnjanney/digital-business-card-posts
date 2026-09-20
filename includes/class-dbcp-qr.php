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
 * QR code generation, cache and edit-screen box.
 */
class DBCP_QR {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
	}

	/**
	 * Create the cache directory. Filled in by step 6.
	 *
	 * @return void
	 */
	public static function ensure_cache_dir(): void {
	}

	/**
	 * Delete the cache directory. Filled in by step 6.
	 *
	 * @return void
	 */
	public static function delete_cache_dir(): void {
	}
}

<?php
/**
 * The vCard endpoint: rewrite rule, request handling, photo resize, headers.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The vCard endpoint: rewrite rule, request handling, photo resize, headers.
 */
class DBCP_VCard {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
	}

	/**
	 * Add the vCard rewrite rule. Filled in by step 4.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules(): void {
	}
}

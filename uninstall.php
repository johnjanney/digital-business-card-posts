<?php
/**
 * Uninstall: remove the role, the capabilities, the settings and the QR cache.
 * Card posts are deleted only when the "delete data on uninstall" setting is on.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-dbcp-settings.php';
require_once __DIR__ . '/includes/class-dbcp-post-type.php';
require_once __DIR__ . '/includes/class-dbcp-qr.php';

$dbcp_settings = DBCP_Settings::all();

if ( ! empty( $dbcp_settings['delete_on_uninstall'] ) ) {
	$dbcp_card_ids = get_posts(
		array(
			'post_type'              => DBCP_Post_Type::POST_TYPE,
			'post_status'            => 'any',
			'numberposts'            => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	foreach ( $dbcp_card_ids as $dbcp_card_id ) {
		wp_delete_post( (int) $dbcp_card_id, true );
	}
}

DBCP_Post_Type::remove_capabilities();
DBCP_QR::delete_cache_dir();

delete_option( DBCP_Settings::OPTION );
delete_option( DBCP_Settings::FLUSH_FLAG );

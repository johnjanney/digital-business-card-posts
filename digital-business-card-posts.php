<?php
/**
 * Plugin Name:       Digital Business Card Posts
 * Plugin URI:        https://github.com/johnjanney/digital-business-card-posts
 * Description:       Digital business card pages, one per person, each with a downloadable vCard and a QR code.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            John Janney
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       digital-business-card-posts
 * Domain Path:       /languages
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DBCP_VERSION', '1.0.1' );
define( 'DBCP_FILE', __FILE__ );
define( 'DBCP_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBCP_URL', plugin_dir_url( __FILE__ ) );

require_once DBCP_DIR . 'includes/class-dbcp-settings.php';
require_once DBCP_DIR . 'includes/class-dbcp-post-type.php';
require_once DBCP_DIR . 'includes/class-dbcp-meta.php';
require_once DBCP_DIR . 'includes/class-dbcp-vcard-builder.php';
require_once DBCP_DIR . 'includes/class-dbcp-vcard.php';
require_once DBCP_DIR . 'includes/class-dbcp-qr.php';
require_once DBCP_DIR . 'includes/class-dbcp-template.php';
require_once DBCP_DIR . 'includes/class-dbcp-plugin.php';

register_activation_hook( __FILE__, array( 'DBCP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DBCP_Plugin', 'deactivate' ) );

DBCP_Plugin::instance();

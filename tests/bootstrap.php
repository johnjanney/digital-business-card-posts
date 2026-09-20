<?php
/**
 * PHPUnit bootstrap. The vCard builder has no WordPress dependencies, so no WordPress is loaded.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/class-dbcp-vcard-builder.php';

define( 'DBCP_TESTS_FIXTURES', __DIR__ . '/fixtures' );
define( 'DBCP_TESTS_ROOT', dirname( __DIR__ ) );

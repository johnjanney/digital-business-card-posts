<?php
/**
 * Smoke test for the vendored phpqrcode library: generates a PNG at EC level H. Skipped without GD.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * phpqrcode smoke test.
 */
final class QRSmokeTest extends TestCase {

	/**
	 * Generate a PNG and check it is a square PNG of the expected size.
	 */
	public function test_generates_png_at_level_h(): void {
		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagepng' ) ) {
			$this->markTestSkipped( 'GD is not available; run bin/test-docker.sh.' );
		}
		require_once DBCP_TESTS_ROOT . '/vendor/phpqrcode/phpqrcode.php';

		$path = tempnam( sys_get_temp_dir(), 'dbcp-qr-' );
		$url  = 'https://example.com/card/john-janney/';
		QRcode::png( $url, $path, QR_ECLEVEL_H, 12, 2 );

		$this->assertFileExists( $path );
		$info = getimagesize( $path );
		$this->assertNotFalse( $info );
		$this->assertSame( IMAGETYPE_PNG, $info[2] );
		$this->assertSame( $info[0], $info[1], 'QR must be square' );
		// Width = (modules + 2 * margin) * pixel size. Modules = 17 + 4 * version (21, 25, 29, ...).
		$this->assertSame( 0, $info[0] % 12, 'Width must be a whole number of 12 px modules' );
		$modules = (int) ( $info[0] / 12 ) - 4;
		$this->assertGreaterThanOrEqual( 21, $modules );
		$this->assertSame( 0, ( $modules - 17 ) % 4, 'Module count must match a QR version' );
		unlink( $path );
	}
}

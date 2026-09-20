<?php
/**
 * Tests for DBCP_VCard_Builder: escaping, folding, line endings, TEL types and PHOTO embedding.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * DBCP_VCard_Builder tests.
 */
final class VCardBuilderTest extends TestCase {

	/**
	 * Sample data from PROJECTBRIEF.md §9.
	 *
	 * @return array<string, string>
	 */
	private function sample(): array {
		return array(
			'first_name'      => 'John',
			'last_name'       => 'Janney',
			'job_title'       => 'Chief Growth Officer',
			'company'         => 'Baitulmaal, Inc.',
			'phone_work'      => '+1 214-810-1131',
			'phone_mobile'    => '+1 469-619-7273',
			'email'           => 'johnjanney@baitulmaal.org',
			'website'         => 'https://baitulmaal.org',
			'address_street'  => '2300 Valley View Lane',
			'address_suite'   => 'Suite 370',
			'address_city'    => 'Irving',
			'address_state'   => 'TX',
			'address_postal'  => '75062',
			'address_country' => 'United States',
		);
	}

	/**
	 * Unfold a vCard back into logical lines (for assertions on content).
	 *
	 * @param string $vcard Folded vCard.
	 * @return string[]
	 */
	private function unfold( string $vcard ): array {
		$unfolded = str_replace( "\r\n ", '', $vcard );
		return explode( "\r\n", rtrim( $unfolded, "\r\n" ) );
	}

	// -- Escaping ---------------------------------------------------------------

	public function test_escape_backslash(): void {
		$this->assertSame( 'a\\\\b', DBCP_VCard_Builder::escape( 'a\\b' ) );
	}

	public function test_escape_comma(): void {
		$this->assertSame( 'Baitulmaal\\, Inc.', DBCP_VCard_Builder::escape( 'Baitulmaal, Inc.' ) );
	}

	public function test_escape_semicolon(): void {
		$this->assertSame( 'a\;b', DBCP_VCard_Builder::escape( 'a;b' ) );
	}

	public function test_escape_newline_variants(): void {
		$this->assertSame( 'line1\\nline2', DBCP_VCard_Builder::escape( "line1\nline2" ) );
		$this->assertSame( 'line1\\nline2', DBCP_VCard_Builder::escape( "line1\r\nline2" ) );
		$this->assertSame( 'line1\\nline2', DBCP_VCard_Builder::escape( "line1\rline2" ) );
	}

	public function test_escape_backslash_before_other_characters_is_not_double_escaped(): void {
		// A literal backslash followed by a comma: the backslash is escaped first, then the comma.
		$this->assertSame( 'a\\\\\\,b', DBCP_VCard_Builder::escape( 'a\\,b' ) );
	}

	public function test_escaped_values_appear_in_output(): void {
		$vcard = DBCP_VCard_Builder::build(
			array(
				'first_name' => 'Ann;Marie',
				'last_name'  => 'O\\Neil, Jr.',
				'company'    => "Line1\nLine2",
			)
		);
		$lines = $this->unfold( $vcard );
		$this->assertContains( 'N:O\\\\Neil\\, Jr.;Ann\;Marie;;;', $lines );
		$this->assertContains( 'FN:Ann\;Marie O\\\\Neil\\, Jr.', $lines );
		$this->assertContains( 'ORG:Line1\\nLine2', $lines );
	}

	// -- Folding ----------------------------------------------------------------

	public function test_short_line_is_not_folded(): void {
		$line = str_repeat( 'a', 75 );
		$this->assertSame( $line . "\r\n", DBCP_VCard_Builder::fold( $line ) );
	}

	public function test_fold_at_75_octets_with_leading_space_on_continuation(): void {
		$line   = str_repeat( 'a', 75 ) . str_repeat( 'b', 74 ) . 'c';
		$folded = DBCP_VCard_Builder::fold( $line );
		$this->assertSame(
			str_repeat( 'a', 75 ) . "\r\n" . ' ' . str_repeat( 'b', 74 ) . "\r\n" . ' c' . "\r\n",
			$folded
		);
	}

	public function test_every_physical_line_is_at_most_75_octets(): void {
		$vcard = DBCP_VCard_Builder::build( $this->sample(), file_get_contents( DBCP_TESTS_FIXTURES . '/photo-large.jpg' ) );
		foreach ( explode( "\r\n", $vcard ) as $physical ) {
			$this->assertLessThanOrEqual( 75, strlen( $physical ), 'Physical line too long: ' . $physical );
		}
	}

	public function test_continuation_lines_start_with_a_single_space(): void {
		$vcard = DBCP_VCard_Builder::build( $this->sample(), file_get_contents( DBCP_TESTS_FIXTURES . '/photo.jpg' ) );
		$lines = explode( "\r\n", rtrim( $vcard, "\r\n" ) );
		$continuations = 0;
		foreach ( $lines as $physical ) {
			if ( 0 === strpos( $physical, ' ' ) ) {
				++$continuations;
				$this->assertMatchesRegularExpression( '/^ [^ ]/', $physical, 'Continuation must start with exactly one space' );
			} else {
				$this->assertMatchesRegularExpression( '/^[A-Z]+[;:]/', $physical, 'Non-continuation line must start with a property name' );
			}
		}
		$this->assertGreaterThan( 5, $continuations, 'The embedded photo must produce continuation lines' );
	}

	public function test_fold_never_splits_a_utf8_sequence(): void {
		// 'é' is two octets. 74 ASCII + 'é' would put the split inside the sequence.
		$line   = 'NOTE:' . str_repeat( 'a', 69 ) . 'é' . str_repeat( 'b', 10 );
		$folded = DBCP_VCard_Builder::fold( $line );
		$parts  = explode( "\r\n", rtrim( $folded, "\r\n" ) );
		$this->assertCount( 2, $parts );
		$this->assertSame( 'NOTE:' . str_repeat( 'a', 69 ), $parts[0] );
		$this->assertSame( ' é' . str_repeat( 'b', 10 ), $parts[1] );
		$this->assertSame( $line, str_replace( "\r\n ", '', rtrim( $folded, "\r\n" ) ) );
		$this->assertTrue( mb_check_encoding( $parts[0], 'UTF-8' ) );
		$this->assertTrue( mb_check_encoding( $parts[1], 'UTF-8' ) );
	}

	public function test_unfolding_restores_the_logical_line(): void {
		$line = 'X:' . str_repeat( 'abcdefghij', 40 );
		$this->assertSame( $line, str_replace( "\r\n ", '', rtrim( DBCP_VCard_Builder::fold( $line ), "\r\n" ) ) );
	}

	// -- Line endings -----------------------------------------------------------

	public function test_lines_end_with_crlf_only(): void {
		$vcard = DBCP_VCard_Builder::build( $this->sample(), file_get_contents( DBCP_TESTS_FIXTURES . '/photo.jpg' ) );
		$this->assertStringEndsWith( "END:VCARD\r\n", $vcard );
		$this->assertSame( 0, preg_match( '/(?<!\r)\n/', $vcard ), 'Found a bare LF' );
		$this->assertSame( 0, preg_match( '/\r(?!\n)/', $vcard ), 'Found a bare CR' );
		$this->assertSame( substr_count( $vcard, "\r\n" ), substr_count( $vcard, "\n" ) );
	}

	public function test_begin_version_and_end(): void {
		$lines = $this->unfold( DBCP_VCard_Builder::build( $this->sample() ) );
		$this->assertSame( 'BEGIN:VCARD', $lines[0] );
		$this->assertSame( 'VERSION:3.0', $lines[1] );
		$this->assertSame( 'END:VCARD', $lines[ count( $lines ) - 1 ] );
	}

	// -- TEL types --------------------------------------------------------------

	public function test_tel_types_work_and_cell(): void {
		$lines = $this->unfold( DBCP_VCard_Builder::build( $this->sample() ) );
		$this->assertContains( 'TEL;TYPE=WORK,VOICE:+12148101131', $lines );
		$this->assertContains( 'TEL;TYPE=CELL,VOICE:+14696197273', $lines );
	}

	public function test_tel_omitted_when_empty(): void {
		$fields                 = $this->sample();
		$fields['phone_mobile'] = '';
		$vcard                  = DBCP_VCard_Builder::build( $fields );
		$this->assertStringContainsString( 'TEL;TYPE=WORK,VOICE:', $vcard );
		$this->assertStringNotContainsString( 'TEL;TYPE=CELL', $vcard );
	}

	public function test_normalize_phone(): void {
		$this->assertSame( '2148101131', DBCP_VCard_Builder::normalize_phone( '(214) 810-1131' ) );
		$this->assertSame( '+12148101131', DBCP_VCard_Builder::normalize_phone( ' +1 (214) 810.1131 ' ) );
		$this->assertSame( '', DBCP_VCard_Builder::normalize_phone( 'n/a' ) );
	}

	// -- Other properties ---------------------------------------------------------

	public function test_n_fn_org_title_email_url_adr(): void {
		$lines = $this->unfold( DBCP_VCard_Builder::build( $this->sample() ) );
		$this->assertContains( 'N:Janney;John;;;', $lines );
		$this->assertContains( 'FN:John Janney', $lines );
		$this->assertContains( 'ORG:Baitulmaal\\, Inc.', $lines );
		$this->assertContains( 'TITLE:Chief Growth Officer', $lines );
		$this->assertContains( 'EMAIL;TYPE=WORK,INTERNET:johnjanney@baitulmaal.org', $lines );
		$this->assertContains( 'URL:https://baitulmaal.org', $lines );
		$this->assertContains( 'ADR;TYPE=WORK:;Suite 370;2300 Valley View Lane;Irving;TX;75062;United States', $lines );
	}

	public function test_full_name_override(): void {
		$fields              = $this->sample();
		$fields['full_name'] = 'Dr. John Janney';
		$lines               = $this->unfold( DBCP_VCard_Builder::build( $fields ) );
		$this->assertContains( 'FN:Dr. John Janney', $lines );
		$this->assertContains( 'N:Janney;John;;;', $lines );
	}

	public function test_adr_omitted_when_all_parts_empty(): void {
		$vcard = DBCP_VCard_Builder::build( array( 'first_name' => 'A', 'last_name' => 'B' ) );
		$this->assertStringNotContainsString( 'ADR', $vcard );
	}

	// -- PHOTO ------------------------------------------------------------------

	public function test_photo_embedded_as_base64_jpeg(): void {
		$jpeg  = file_get_contents( DBCP_TESTS_FIXTURES . '/photo.jpg' );
		$vcard = DBCP_VCard_Builder::build( $this->sample(), $jpeg );
		$lines = $this->unfold( $vcard );

		$photo_line = null;
		foreach ( $lines as $line ) {
			if ( 0 === strpos( $line, 'PHOTO;' ) ) {
				$photo_line = $line;
			}
		}
		$this->assertNotNull( $photo_line, 'PHOTO property missing' );
		$this->assertStringStartsWith( 'PHOTO;ENCODING=b;TYPE=JPEG:', $photo_line );
		$encoded = substr( $photo_line, strlen( 'PHOTO;ENCODING=b;TYPE=JPEG:' ) );
		$this->assertSame( $jpeg, base64_decode( $encoded, true ), 'Decoded photo must equal the input bytes' );
		$this->assertSame( "\xFF\xD8", substr( base64_decode( $encoded, true ), 0, 2 ), 'JPEG SOI marker' );
	}

	public function test_photo_first_physical_line_is_75_octets_and_folded_like_the_reference(): void {
		$jpeg  = file_get_contents( DBCP_TESTS_FIXTURES . '/photo-large.jpg' );
		$vcard = DBCP_VCard_Builder::build( $this->sample(), $jpeg );
		$physical = explode( "\r\n", $vcard );
		$index = null;
		foreach ( $physical as $i => $line ) {
			if ( 0 === strpos( $line, 'PHOTO;' ) ) {
				$index = $i;
			}
		}
		$this->assertNotNull( $index );
		$this->assertSame( 75, strlen( $physical[ $index ] ) );
		$this->assertSame( 75, strlen( $physical[ $index + 1 ] ) );
		$this->assertSame( ' ', $physical[ $index + 1 ][0] );
	}

	public function test_photo_omitted_when_empty(): void {
		$this->assertStringNotContainsString( 'PHOTO', DBCP_VCard_Builder::build( $this->sample(), '' ) );
	}

	public function test_photo_type_token_is_sanitized(): void {
		$vcard = DBCP_VCard_Builder::build( $this->sample(), 'x', 'image/png' );
		$this->assertStringContainsString( 'PHOTO;ENCODING=b;TYPE=IMAGEPNG:', $vcard );
		$vcard = DBCP_VCard_Builder::build( $this->sample(), 'x', 'png' );
		$this->assertStringContainsString( 'PHOTO;ENCODING=b;TYPE=PNG:', $vcard );
	}

	// -- Reference prototype ----------------------------------------------------

	public function test_matches_reference_vcf_structure(): void {
		$reference = file_get_contents( DBCP_TESTS_ROOT . '/reference/john-janney.vcf' );
		$this->assertNotFalse( $reference );
		$ref_lines = $this->unfold( $reference );

		// Decode the reference photo and rebuild with it; the only expected differences are the
		// escaped comma in ORG (D11), the normalized TEL values (D22) and the property order.
		$photo = '';
		foreach ( $ref_lines as $line ) {
			if ( 0 === strpos( $line, 'PHOTO;ENCODING=b;TYPE=JPEG:' ) ) {
				$photo = base64_decode( substr( $line, strlen( 'PHOTO;ENCODING=b;TYPE=JPEG:' ) ), true );
			}
		}
		$this->assertNotSame( '', $photo );

		$ours = $this->unfold( DBCP_VCard_Builder::build( $this->sample(), $photo ) );

		$normalize = static function ( array $lines ): array {
			$out = array();
			foreach ( $lines as $line ) {
				$line = str_replace( 'Baitulmaal, Inc.', 'Baitulmaal\\, Inc.', $line );
				$line = preg_replace( '/^(TEL;[^:]+:)\+1-(\d{3})-(\d{3})-(\d{4})$/', '$1+1$2$3$4', $line );
				$out[] = $line;
			}
			sort( $out );
			return $out;
		};

		$this->assertSame( $normalize( $ref_lines ), $normalize( $ours ) );
	}
}

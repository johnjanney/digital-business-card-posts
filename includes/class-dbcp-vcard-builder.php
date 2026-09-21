<?php
/**
 * Pure vCard 3.0 builder. No WordPress calls, so it can be unit tested standalone.
 *
 * @package DigitalBusinessCardPosts
 */

declare( strict_types=1 );

/**
 * Builds a vCard 3.0 (RFC 2426) string from an array of fields.
 *
 * Escaping and folding follow RFC 2426: backslash, comma, semicolon and newline are escaped in
 * text values; lines end with CRLF and are folded at 75 octets with a single leading space on
 * each continuation line. UTF-8 sequences are never split by a fold.
 */
class DBCP_VCard_Builder {

	/**
	 * Maximum octets per physical line, excluding CRLF.
	 */
	const LINE_LENGTH = 75;

	/**
	 * Line terminator.
	 */
	const CRLF = "\r\n";

	/**
	 * Escape a text value for a vCard property (RFC 2426 §2.4.2 / §5).
	 *
	 * Order matters: backslashes first, then the separators, then newlines.
	 *
	 * @param string $value Raw text.
	 * @return string
	 */
	public static function escape( string $value ): string {
		$value = str_replace( "\r\n", "\n", $value );
		$value = str_replace( "\r", "\n", $value );
		$value = str_replace( '\\', '\\\\', $value );
		$value = str_replace( ',', '\\,', $value );
		$value = str_replace( ';', '\;', $value );
		$value = str_replace( "\n", '\\n', $value );
		return $value;
	}

	/**
	 * Fold one logical line into physical lines of at most 75 octets, each terminated by CRLF.
	 *
	 * @param string $line Logical line without terminator.
	 * @return string One or more physical lines, each ending in CRLF.
	 */
	public static function fold( string $line ): string {
		if ( strlen( $line ) <= self::LINE_LENGTH ) {
			return $line . self::CRLF;
		}

		$out    = '';
		$offset = 0;
		$length = strlen( $line );
		$first  = true;

		while ( $offset < $length ) {
			// The continuation space counts toward the 75-octet limit.
			$chunk_max = $first ? self::LINE_LENGTH : self::LINE_LENGTH - 1;
			$chunk_len = min( $chunk_max, $length - $offset );

			// Do not split a multi-byte UTF-8 sequence: back off while the next byte is a continuation byte.
			while ( $chunk_len > 1 && ( $offset + $chunk_len ) < $length && ( ord( $line[ $offset + $chunk_len ] ) & 0xC0 ) === 0x80 ) {
				--$chunk_len;
			}

			$out    .= ( $first ? '' : ' ' ) . substr( $line, $offset, $chunk_len ) . self::CRLF;
			$offset += $chunk_len;
			$first   = false;
		}

		return $out;
	}

	/**
	 * Normalize a phone number for dialing: keep digits and a single leading plus sign.
	 *
	 * @param string $phone Phone number as typed.
	 * @return string
	 */
	public static function normalize_phone( string $phone ): string {
		$phone  = trim( $phone );
		$plus   = 0 === strpos( $phone, '+' ) ? '+' : '';
		$digits = preg_replace( '/\D+/', '', $phone );
		return '' === $digits ? '' : $plus . $digits;
	}

	/**
	 * Build the vCard.
	 *
	 * Recognised keys in $fields (all optional strings unless noted): first_name, last_name,
	 * full_name, job_title, company, phone_work, phone_mobile, email, website, address_street,
	 * address_suite, address_city, address_state, address_postal, address_country, and revision
	 * (Unix timestamp, emitted as REV in UTC).
	 *
	 * Property order follows reference/john-janney.vcf: PHOTO comes before ADR, and REV is last, so the
	 * folded base64 block is never the final property before END:VCARD (D30).
	 *
	 * @param array<string, string|int> $fields     Card fields, raw (unescaped) text.
	 * @param string                    $photo      Binary image data to embed, or empty string for none.
	 * @param string                    $photo_type Image type token for the PHOTO property (JPEG, PNG).
	 * @return string The vCard with CRLF line endings.
	 */
	public static function build( array $fields, string $photo = '', string $photo_type = 'JPEG' ): string {
		$get = static function ( string $key ) use ( $fields ): string {
			return isset( $fields[ $key ] ) ? trim( (string) $fields[ $key ] ) : '';
		};

		$first = $get( 'first_name' );
		$last  = $get( 'last_name' );
		$full  = $get( 'full_name' );
		if ( '' === $full ) {
			$full = trim( $first . ' ' . $last );
		}

		$lines   = array();
		$lines[] = 'BEGIN:VCARD';
		$lines[] = 'VERSION:3.0';
		$lines[] = 'N:' . self::escape( $last ) . ';' . self::escape( $first ) . ';;;';
		$lines[] = 'FN:' . self::escape( $full );

		if ( '' !== $get( 'company' ) ) {
			$lines[] = 'ORG:' . self::escape( $get( 'company' ) );
		}
		if ( '' !== $get( 'job_title' ) ) {
			$lines[] = 'TITLE:' . self::escape( $get( 'job_title' ) );
		}

		$work = self::normalize_phone( $get( 'phone_work' ) );
		if ( '' !== $work ) {
			$lines[] = 'TEL;TYPE=WORK,VOICE:' . $work;
		}
		$mobile = self::normalize_phone( $get( 'phone_mobile' ) );
		if ( '' !== $mobile ) {
			$lines[] = 'TEL;TYPE=CELL,VOICE:' . $mobile;
		}

		if ( '' !== $get( 'email' ) ) {
			$lines[] = 'EMAIL;TYPE=WORK,INTERNET:' . self::escape( $get( 'email' ) );
		}
		if ( '' !== $get( 'website' ) ) {
			$lines[] = 'URL:' . self::escape( $get( 'website' ) );
		}

		// The photo goes before ADR and REV so that a short text property always follows the
		// folded base64 block; some Android vCard parsers lose the photo when it is the last
		// property before END:VCARD (D30). Order otherwise matches reference/john-janney.vcf.
		if ( '' !== $photo ) {
			$type    = strtoupper( preg_replace( '/[^A-Za-z]/', '', $photo_type ) );
			$lines[] = 'PHOTO;ENCODING=b;TYPE=' . ( '' === $type ? 'JPEG' : $type ) . ':' . base64_encode( $photo ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- vCard inline photo encoding (RFC 2426).
		}

		$address_parts = array(
			$get( 'address_street' ),
			$get( 'address_suite' ),
			$get( 'address_city' ),
			$get( 'address_state' ),
			$get( 'address_postal' ),
			$get( 'address_country' ),
		);
		if ( '' !== implode( '', $address_parts ) ) {
			// ADR components: PO box; extended (suite); street; locality; region; postal code; country.
			$lines[] = 'ADR;TYPE=WORK:;' . self::escape( $get( 'address_suite' ) )
				. ';' . self::escape( $get( 'address_street' ) )
				. ';' . self::escape( $get( 'address_city' ) )
				. ';' . self::escape( $get( 'address_state' ) )
				. ';' . self::escape( $get( 'address_postal' ) )
				. ';' . self::escape( $get( 'address_country' ) );
		}

		$revision = isset( $fields['revision'] ) && is_numeric( $fields['revision'] ) ? (int) $fields['revision'] : 0;
		if ( $revision > 0 ) {
			$lines[] = 'REV:' . gmdate( 'Ymd\THis\Z', $revision );
		}

		$lines[] = 'END:VCARD';

		$out = '';
		foreach ( $lines as $line ) {
			$out .= self::fold( $line );
		}
		return $out;
	}
}

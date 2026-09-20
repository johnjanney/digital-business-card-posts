/* global jQuery, wp */
/**
 * Admin behavior: media picker for the logo and the color picker for accent colors.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		// Color pickers.
		if ( $.fn.wpColorPicker ) {
			$( '.dbcp-color-field' ).wpColorPicker();
		}

		// Logo media picker.
		$( '.dbcp-media' ).each( function () {
			var $wrap = $( this ),
				$id = $wrap.find( '.dbcp-media-id' ),
				$preview = $wrap.find( '.dbcp-media-preview' ),
				$remove = $wrap.find( '.dbcp-media-remove' ),
				frame;

			$wrap.on( 'click', '.dbcp-media-select', function ( e ) {
				e.preventDefault();
				if ( ! wp || ! wp.media ) {
					return;
				}
				if ( ! frame ) {
					frame = wp.media( {
						title: $wrap.data( 'title' ),
						button: { text: $wrap.data( 'button' ) },
						library: { type: 'image' },
						multiple: false
					} );
					frame.on( 'select', function () {
						var attachment = frame.state().get( 'selection' ).first().toJSON(),
							size = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium : attachment,
							url = size.url || attachment.url;
						$id.val( attachment.id );
						$preview.empty().append( $( '<img>', { src: url, alt: '' } ) ).prop( 'hidden', false );
						$remove.prop( 'hidden', false );
					} );
				}
				frame.open();
			} );

			$remove.on( 'click', function ( e ) {
				e.preventDefault();
				$id.val( '0' );
				$preview.empty().prop( 'hidden', true );
				$remove.prop( 'hidden', true );
			} );
		} );
	} );
}( jQuery ) );

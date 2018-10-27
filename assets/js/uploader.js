( function( $ ) {
	"use strict";

	$( document ).ready( function() {

		var _custom_media = true,
			_orig_send_attachment = wp.media.editor.send.attachment;

		$( '.oceanwp-media-upload-button' ).click(function(e) {

			var send_attachment_bkp	= wp.media.editor.send.attachment,
				button = $(this),
				id = button.prev();
				_custom_media = true;

			wp.media.editor.send.attachment = function( props, attachment ) {
				if ( _custom_media ) {
					$( id ).val( attachment.url );
					var $preview = button.parent().find( '.oceanwp-media-live-preview img' ),
						$remove  = button.parent().find( '.oceanwp-media-remove' );
					if ( $remove.length ) {
						$remove.show();
					}
					if ( $preview.length ) {
						$preview.attr( 'src', attachment.url );
					} else {
						$preview = button.parent().find('.oceanwp-media-live-preview');
						var $imgSize = $preview.data( 'image-size' ) ? $preview.data( 'image-size' ) : 'auto';
						$preview.show().append( '<img src="'+ attachment.url +'" style="height:'+ $imgSize +'px;width:'+ $imgSize +'px;" />' );
					}
				} else {
					return _orig_send_attachment.apply( this, [props, attachment] );
				};
			}

			wp.media.editor.open( button );
			return false;

		} );

		$( '.add_media').on('click', function() {
			_custom_media = false;
		} );

		$( '.oceanwp-media-live-preview' ).each( function( index ) {
			var $this     = $( this ),
				$input    = $this.parent().find( '.oceanwp-media-input' ),
				$inputVal = $input.val();
			if ( $inputVal ) {
				$this.show();
			}
		} );

		$( '.oceanwp-media-remove' ).each( function( index ) {
			var $this     = $( this ),
				$input    = $this.parent().find( '.oceanwp-media-input' ),
				$inputVal = $input.val(),
				$preview  = $this.parent().find('.oceanwp-media-live-preview');
			if ( $inputVal ) {
				$this.show();
			}
			$this.on('click', function() {
				$input.val( '' );
				$preview.find( 'img' ).remove();
				$this.hide();
				return false;
			} );
		} );

	} );

} ) ( jQuery );
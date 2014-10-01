(function($) {
$(function() {

$('body')
	.delegate('.wordlets-widget-image-set', 'click', function(e) {
		e.preventDefault();
		var $this = $(this);
		var _custom_media = true;
		var _orig_send_attachment = wp.media.editor.send.attachment;
		var button_id = '#' + this.id;
		var send_attachment_bkp = wp.media.editor.send.attachment;
		var target = $this.data('target');
		var image = $this.data('image');

		_custom_media = true;

		wp.media.editor.send.attachment = function(props, attachment) {
			var updates = {
				alt: {
					value: attachment.alt
				},
				width: {
					value: attachment.width
				},
				height: {
					value: attachment.height
				},
				size: {
					value: props.size
				},
				align: {
					value: props.align
				}
			};

			if ( _custom_media ) {
				var ups = [];

				for ( var update in updates ) {
					var p = updates[update];
					var $input = $($this.data(update));
					if ( $input.length && $input.val() !== '' ) {
						ups.push(update);
					}
				}

				/*var $d = $('<div/>');

				for ( var update in updates ) {
					var p = updates[update];
					var $input = $($this.data(update));
					if ( $input.length && $input.val() !== '' ) {
						var id = 'imginput' + update;
						var $check = $(
							'<label for="' + id + '" class="wordlet-dialog-label">\
								<input type="checkbox" id="' + id + '" checked>\
								<p class="wordlet-dialog-name">' + update + ', Current Value:</p>\
								<p class="wordlet-dialog-current">' + $input.val() + '</p>\
							</label>');
						$d.append($check);
					}
				}

				$d.dialog({
					title: 'Update inputs?',
					modal: true,
					closeOnEscape: false,
					buttons: [
						{
							text: 'Ok',
							click: function() {
								$( this ).dialog( 'close' );
							}
						}
					],
					open: function() {

					},
					close: function() {

					}
				});*/

				if ( !ups.length || (ups.length && confirm('Update ' + ups.join(', ') + '?')) ) {
					for ( var update in updates ) {
						var p = updates[update];
						var $input = $($this.data(update));
						$input.val(p.value).trigger('change');
					}
				}

				$(target).val(attachment.id).trigger('change');
				$(image).attr({src: attachment.url});

			} else {
				return _orig_send_attachment.apply( button_id, [props, attachment] );
			}
		}

		wp.media.editor.open($this);
	});

});
})(window.jQuery || window.Zepto || window.$);

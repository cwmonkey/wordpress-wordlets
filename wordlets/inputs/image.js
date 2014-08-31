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
		var alt = $this.data('alt');
		var width = $this.data('width');
		var height = $this.data('height');
		var image = $this.data('image');
		_custom_media = true;

		wp.media.editor.send.attachment = function(props, attachment){
			if ( _custom_media ) {
				$(target).val(attachment.url).trigger('change');
				$(width).val(attachment.width).trigger('change');
				$(height).val(attachment.height).trigger('change');
				$(image).attr({src: attachment.url});
				if ( !$(alt).val() ) $(alt).val(attachment.alt).trigger('change');
				//$('.custom_media_url').val(attachment.url);
			} else {
				return _orig_send_attachment.apply( button_id, [props, attachment] );
			}
		}

		wp.media.editor.open($this);
	});

});
})(window.jQuery || window.Zepto || window.$);

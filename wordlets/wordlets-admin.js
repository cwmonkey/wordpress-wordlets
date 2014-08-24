(function($, undefined) {

$(function() {
	// Open the widget
	var hash = null;
	try {
		hash = document.location.toString().split('?')[1];
		//document.location = document.location.toString().split('#')[0];
	} catch (err) {}

	var $widget;

	var scroll_to = function() {
		var top = $widget.offset().top;

		if ( !top ) {
			setTimeout(scroll_top, 100);
		} else {
			$(window).scrollTop(top);
		}
	};

	var open_widget = function() {
		$widget = $('[id$="' + hash + '"]');
		if ( !$widget.length ) {
			setTimeout(open_widget, 100);
		} else {
			$widget.find('.widget-inside').show().closest('.widgets-holder-wrap.closed').removeClass('closed'); //.find('.sidebar-name').click();

			setTimeout(scroll_to, 100);

			//document.location.hash = 'widget-36_wordlets_widget-2';

			//setTimeout(scroll_to, 100);
		}
	}

	if ( hash && hash.match(/wordlets_widget/) ) {
		setTimeout(open_widget, 100);
	}

	$('body')
		// show/hide inputs for template
		.delegate('.wordlets-widget-template', 'change', function() {
			var $this = $(this);
			var val = $this.val();
			var $parent = $this.closest('.wordlets-widget-wrapper');
			var $active = $parent.find('.wordlet-widget-set.active');
			var $title = $parent.find('.wordlet-widget-title');

			// Change the title
			if ( !$title.val() || $title.val() == 'New Widget' || $title.val() == $this.find('option[value="' + $active.data('template') + '"]').html() ) {
				$title.val($this.find('option[value="' + $this.val() + '"]').html());
			}

			// Show/hide the nodes
			$active = $parent.find('.wordlet-widget-set[data-template="' + val + '"]').addClass('active');
			$active.siblings('.wordlet-widget-set').removeClass('active')
		})
		// Fill in the title if it's blank
		.delegate('.widget[id*="wordlets_widget"]', 'click', function() {
			var $this = $(this);
			var $template = $this.find('.wordlets-widget-template');
			var $title = $this.find('.wordlet-widget-title');
			if ( !$title.val() || $title.val() == 'New Widget' ) {
				$title.val($template.find('option[value="' + $template.val() + '"]').html());
			}
		})
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
		})
		.floatLabels({
			parent: '.wordlet-widget-set .wordlet-float-label',
			filledClass: 'wordlet-filled'
		})
		.delegate('.wordlet-array', 'mouseover', function() {
			var $this = $(this);
			if ( $this.is('.ui-sortable') ) return;
			$this.sortable({
				forcePlaceholderSize: true,
				//handle: '.wordlet-array-item',
				start: function(e, ui) {
					ui.placeholder.height(ui.helper.height());
				}
			})
		})
		;

});
})(jQuery);

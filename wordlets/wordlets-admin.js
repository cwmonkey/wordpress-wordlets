(function($, undefined) {

$(function() {
	// Open the widget
	var hash = null;
	try {
		hash = document.location.hash.split('#')[1];
	} catch (err) {}

	var open_widget = function() {
		$widget = $('[id$="' + hash + '"]');
		if ( !$widget.length ) {
			setTimeout(open_widget, 100);
		} else {
			$widget.find('.widget-title').click();
			document.location.hash = $widget[0].id;
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
			if ( !$title.val() || $title.val() == $this.find('option[value="' + $active.data('template') + '"]').html() ) {
				$title.val($this.find('option[value="' + $this.val() + '"]').html());
			}

			// Show/hide the nodes
			$active = $parent.find('.wordlet-widget-set[data-template="' + val + '"]').addClass('active');
			$active.siblings('.wordlet-widget-set').removeClass('active')
		})
		// Fill in the title if it's blank
		.delegate('.widget[id*="wordlets_widget"]', 'click', function() {
			console.log(1);
			var $this = $(this);
			var $template = $this.find('.wordlets-widget-template');
			var $title = $this.find('.wordlet-widget-title');
			if ( !$title.val() ) {
				$title.val($template.find('option[value="' + $template.val() + '"]').html());
			}
		})
		;
});
})(jQuery);
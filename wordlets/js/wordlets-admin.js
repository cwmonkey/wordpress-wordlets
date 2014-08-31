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
			$(window).scrollTop(top - 38);
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
			if ( !$title.val() || $title.val() == 'New Widget' || $title.val() == $this.find('option[value="' + $active.data('template') + '"]').text() ) {
				$title.val($this.find('option[value="' + $this.val() + '"]').text());
			}

			// Show/hide the nodes
			$active = $parent.find('.wordlet-widget-set[data-template="' + val + '"]').addClass('active');
			$active.siblings('.wordlet-widget-set').removeClass('active');
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
		// showhide setup options
		.delegate('.wordlets-widget-showhide input', 'change keypress', function(e) {
			var $this = $(this);
			var checked = $this.prop('checked');
			var $target = $this.closest('.wordlets-widget-showhide').prev('.wordlets-widget-setup');

			if ( !checked ) {
				$target.slideDown(500);
			} else {
				$target.slideUp(500);
			}
		})
		// showhide pages
		.delegate('.wordlets-widget-pages-showhide input', 'change keypress', function(e) {
			var $this = $(this);
			var checked = $this.prop('checked');
			var $target = $this.closest('.wordlets-widget-pages-showhide').next('.wordlets-widget-pages');

			if ( !checked ) {
				$target.slideUp(500);
			} else {
				$target.slideDown(500);
			}
		})
		;

});
})(window.jQuery || window.Zepto || window.$);

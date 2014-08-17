(function($, undefined) {

$(function() {
	$('body')
		.delegate('.wordlets-widget-template', 'change', function() {
			var $this = $(this);
			var val = $this.val();
			var $parent = $this.closest('.wordlets-widget-wrapper ');
			var $active = $parent.find('.wordlet-widget-set[data-template="' + val + '"]').show();
			$active.siblings('.wordlet-widget-set').hide();
		})
		;
});
})(jQuery);
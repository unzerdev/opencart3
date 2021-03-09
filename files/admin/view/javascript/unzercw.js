(function($) {
	
	
	var disableElements = function() {

		// Enable all
		$('.unzercw-control-box').each(function () {
			$(this).find('input').prop('disabled', false);
			$(this).find('select').prop('disabled', false);
			$(this).find('textarea').prop('disabled', false);
		});
		
		// Disable selected
		$('.unzercw-use-default .unzercw-control-box').each(function () {
			$(this).find('input').prop('disabled', true);
			$(this).find('select').prop('disabled', true);
			$(this).find('textarea').prop('disabled', true);
		});
	};

	$(document).ready(function() {
		$(".unzercw-default-box input").click(function() {
			$(this).parents(".control-box-wrapper").toggleClass('unzercw-use-default');
			disableElements();
		});
		disableElements();
	});

})(jQuery);

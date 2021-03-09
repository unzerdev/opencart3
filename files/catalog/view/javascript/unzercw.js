(function ($) {
	
	var attachEventHandlers = function() {
		if (typeof unzercw_ajax_submit_callback != 'undefined') {
			$('.unzercw-confirmation-buttons input').each(function () {
				$(this).click(function() {
					UnzerCwHandleAjaxSubmit();
				});
			});
		}
	};
	
	var getFieldsDataArray = function () {
		var fields = {};
		
		var data = $('#unzercw-confirmation-ajax-authorization-form').serializeArray();
		$(data).each(function(index, value) {
			fields[value.name] = value.value;
		});
		
		return fields;
	};
	
	var UnzerCwHandleAjaxSubmit = function() {
		
		if (typeof cwValidateFields != 'undefined') {
			cwValidateFields(UnzerCwHandleAjaxSubmitValidationSuccess, function(errors, valid){alert(errors[Object.keys(errors)[0]]);});
			return false;
		}
		UnzerCwHandleAjaxSubmitValidationSuccess(new Array());
		
	};
	
	var UnzerCwHandleAjaxSubmitValidationSuccess = function(valid) {
		
		if (typeof unzercw_ajax_submit_callback != 'undefined') {
			unzercw_ajax_submit_callback(getFieldsDataArray());

		}
		else {
			alert("No JavaScript callback function defined.");
		}
	}
		
	$( document ).ready(function() {
		attachEventHandlers();
		
		$('#unzercw_alias').change(function() {
			$('#unzercw-checkout-form-pane').css({
				opacity: 0.5,
			});
			$.ajax({
				type: 		'POST',
				url: 		'index.php?route=checkout/confirm',
				data: 		'unzercw_alias=' + $('#unzercw_alias').val(),
				success: 	function( response ) {
					var htmlCode = '';
					try {
						var jsonObject = jQuery.parseJSON(response);
						htmlCode = jsonObject.output;
					}
					catch (err){
						htmlCode = response;
					}
					
					var newPane = $("#unzercw-checkout-form-pane", $(htmlCode));
					if (newPane.length > 0) {
						var newContent = newPane.html();
						$('#unzercw-checkout-form-pane').html(newContent);
						attachEventHandlers();
					}
					
					$('#unzercw-checkout-form-pane').animate({
						opacity : 1,
						duration: 100, 
					});
				},
			});
		});
		
	});
	
}(jQuery));
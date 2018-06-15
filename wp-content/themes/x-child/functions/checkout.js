var $ = jQuery

	$( document ).ready(function() {
	    $( document ).ajaxComplete(function( event, xhr, settings ) {
        	// Referenced https://apppresser.com/woocommerce-checkout-customization-guide/
        	$('dt.variation-Providers').text('Counsellor:');
        	
        	var session_type = $('dd.variation-SessionTypespanclasswoocommerce-Price-amountamountspanclasswoocommerce-Price-currencySymbol36span1000span p').html();
			if( session_type != 'Group Session' && session_type != 'Group Sessions')
			{
				$('div.extra-fields').hide();
			}

			for(var count = 5; count >0; count--)
			{
				$('#email_field_'+(count)+'_field').hide()
			}
			$('#members_attending').on('input',function(e){
				var number = $('#members_attending').val();
				for (var count = 0; count < number; count++) {
					$('#email_field_'+(count+1)+'_field').show()
				}
				for(var count = 5; count >number; count--)
				{
					$('#email_field_'+(count)+'_field').hide();
				}
			})

			$( "input#place_order" ).click(function( event ) {
				if( session_type == 'Group Session' || session_type == 'Group Sessions')
				{
					if($('#members_attending').val().length === 0){
						event.preventDefault();
						$('.woocommerce-NoticeGroup-checkout').remove();
						$("form.woocommerce-checkout").prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error x-alert x-alert-danger x-alert-block"><li><strong>Enter number of other attendees is a required field</strong>.</li></ul></div>');
					}
					else {
						var flag = false;
						
						$('.group_sesh_field').each(function() {
							//if input text is not hidden and is not a valid email field
							if(  !($(this).find('input.input-text ').is(":hidden")) &&  !isEmail($(this).find('input.input-text ').val()) ) {
								$('.woocommerce-NoticeGroup-checkout').remove();
								$("form.woocommerce-checkout").prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error x-alert x-alert-danger x-alert-block"><li><strong>Please enter a valid email</strong>.</li></ul></div>');
								flag = true;
							}
						});
						if(flag) {
							event.preventDefault();
						}
						
					}
					
				}
			  	
			});

		});
	    
	})

function isEmail(email) {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}

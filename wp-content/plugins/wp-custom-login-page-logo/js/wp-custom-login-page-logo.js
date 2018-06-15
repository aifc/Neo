/*
*	jQuery Scripts for WP Custom Login Page Logo Plugin
*/

jQuery(document).ready(function($){

	// open thickbox w/ media upload
	$('.wpclpl-logo-upload-btn').click(function() {
		tb_show('Select an image file for custom admin logo. (Click "Insert into Post" to select it.)', 'media-upload.php?referer=wpclpl-settings&type=image&TB_iframe=true&post_id=0', false);
		return false;
	});
	
		
	// update the css...
	function updateCustomCss(){
		var backgroundImage = 'background-image:url('+$('.wpclpl-logo-url').val()+');';
		var cssPreview = backgroundImage+"\n"+$('.wpclpl-custom-css').val();
		$('#wpclpl-preview-css').html( cssPreview );
	}
		
	
	// send data to editor...
	window.send_to_editor = function(html) {
		
		var uploadedLogoUrl = $(html).attr('src');
		
		$('.wpclpl-logo-url').val(uploadedLogoUrl);
		tb_remove();
		$('.wpclpl-currentlogo, .wpclpl-default-logo').fadeOut(300);		
		$('<img class="wpclpl-logo-preview" style="display:none; "src="'+uploadedLogoUrl+'" />  ')
			.insertAfter('.wpclpl-currentlogo')
			.delay(500)
			.fadeIn(300);
			
		$('#wpclpl-logo-preview').attr('src', $('.wpclpl-logo-url').val() );
		$('#wpclpl-logo-preview-wrap a.thickbox').attr('href', $('.wpclpl-logo-url').val() );
		
		updateCustomCss();
		
	}
	
	
	function buildPreviewCss(){
	
		if( $('.wpclpl-logo-url').val() !="" ){
			var cssPreview = 'background-image:url("'+$('.wpclpl-logo-url').val()+'");';
			cssPreview += "\n";
			cssPreview += $('.wpclpl-custom-css').val();
			$('#wpclpl-preview-css').html( cssPreview );
		}
		
	}
	
	buildPreviewCss();
	
	
	// modal window
	function wpclplShowModal(ID){
		
		$('<div class="wpclpl-modal-box-wrap"></div>').insertBefore('#wpwrap');
			// console.log(ID);
		$('.'+ID).fadeIn(300,function(){
		
			// yes, reset...
			$('.wpclpl-reset-confirmed').click(function(){
				$('.wpclpl-logo-url, .wpclpl-custom-css, .wpclpl-additional-text').val('');
				$('.wpclpl-logo-preview').attr('src', '').css();
				
				$('#wpclpl-preview-css').html('');
				//$('.wpclpl-modal-box').fadeOut(300);
				$('.wpclpl-modal-box-wrap').fadeOut(300);
			});
			
			// no, cancel
			$('.wpclpl-reset-cancel').click(function(){	
				//$('.wpclpl-modal-box').fadeOut(300);
				$('.wpclpl-modal-box-wrap').fadeOut(300);
			});		
			
		});
		
	}
	
	// click on reset buttons: reset image / reset all settings
	$('.wpclpl-reset-btn').click(function(e){
		e.preventDefault();
		wpclplShowModal( $(this).attr('id') );
		
	});
	
	$('.wpclpl-logo-remove-img-btn').click(function(){
		
		$('.wpclpl-logo-url').val('');
		$('.wpclpl-logo-size').text('');
		$('.wpclpl-logo-preview-wrap').html('<div class="wpclpl-currentlogo" style="background-image: url(\'/wp-admin/images/wordpress-logo.svg?ver=20131107\')"></div>');
				
	});


	
	// update the preview while typing... just for control	
	$('.wpclpl-custom-css').keyup(function(){
		updateCustomCss();
	});
	
});
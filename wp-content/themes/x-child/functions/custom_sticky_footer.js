var $ = jQuery
$(document).ready(function()
{	

	var docHeight = $(window).height();
   	var footerHeightTop = $('footer.top').height();
   	var footerHeightBottom = $('footer.bottom').height();
   	
   	var footerTop = $('footer.top').position().top + footerHeightTop + footerHeightBottom;
   	
   	if (footerTop < docHeight) {
    	$('footer.top').css('margin-top', 38+ (docHeight - footerTop) + 'px');
   	}
});

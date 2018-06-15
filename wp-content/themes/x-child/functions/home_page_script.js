var $ = jQuery
var isMac = isMacintosh();
var isPC = isWindows();

$(document).ready(function()
{
	if(isMac)
	{
		$('#zoom_download_link').prop("href", "https://zoom.us/client/4.0.29656.0413/zoomusInstaller.pkg");
	}
	else if(isPC)
	{
		$('#zoom_download_link').prop("href", "https://zoom.us/client/latest/ZoomInstaller.exe");
		
	}
});
function isMacintosh() {
  return navigator.platform.indexOf('Mac') > -1
}

function isWindows() {
  return navigator.platform.indexOf('Win') > -1
}

$(document).ready(function() {

	

	$( ".three-step-process" ).wrap( "<a class='threecolslink' href='wp-login.php'></a>" );
	$( ".three-step-process" ).last().wrap( "<a href='/members'></a>" );
	$('.three-step-process').css('margin-right','2.6%');
	$('.three-step-process').parent().css("color", "rgb(80,80,80)");

	if ($(window).width() >= 753) {  
	    adjustThreeColsHeight();
   	}   

   	//ids
   	//supervision-homepage
   	//group-session-homepage
   	//online-counselling-homepage
   	$( "#supervision-homepage" ).wrap( "<a href='/wp-counselor-login.php'></a>" );
   	$('#supervision-homepage').css("color", "rgb(80,80,80)");

   	$( "#group-session-homepage" ).wrap( "<a href='#'></a>" );
   	$('#group-session-homepage').css("color", "rgb(80,80,80)");

   	$( "#online-counselling-homepage" ).wrap( "<a href='#'></a>" );
   	$('#online-counselling-homepage').css("color", "rgb(80,80,80)");

});

function adjustThreeColsHeight() {
	var maxHeight = 0;

	$('.threecolslink').each(function() {
    	
    	 if($(this).find('.three-step-process').height() > maxHeight) {
         	maxHeight = $(this).find('.three-step-process').height();  
        }
	});
	$('.threecolslink').each(function() {
    	$(this).find('.three-step-process').height(maxHeight)
	});
}
/* 
	@Author: Alsacréations
	@Date : Oct. 2011
*/

(function ($) {
	
	$('html').removeClass('no-js');
	
	
	// footer slide
	var clicked = false;
	
	$('.more_infos').hide();
	$('footer li.more:not(".current") a').live("click", function() {
		$('.more_infos').stop(true,true).slideDown(400);
		$(this).closest('li').addClass('current');
		$(this).find('.show_more').hide();
		$(this).find('.show_less').show();
		clicked = false;
		return false;
	});
	$('footer li.more.current a').live("click", function() {
		$('.more_infos').stop(true,true).slideUp(400);
		$(this).closest('li').removeClass('current');
		$(this).find('.show_more').show();
		$(this).find('.show_less').hide();
		clicked = true;
		return false;
	});
	
	// dropdown menu - menu déroulant
	
	if($('#nav_plus').length == 1) {
	
		var $dd = $('#nav_plus');
		
		$dd.hover(function(){
			$("ul", $dd).slideDown(400);
		}, function(){
			$("ul", $dd).slideUp(400);
		});
		
		$('a', $dd).focus(function(){
			$dd.trigger("mouseenter");
		});
		$('ul li:last-child a', $dd).blur(function(){
			$dd.trigger("mouseleave");
		});
	
	}
	
})(jQuery)

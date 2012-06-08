/* 
	@Author: Alsacréations
	@Date: Oct. 2011

    @Autor: Clément Dietschy
    @Author: Seizam Sàrl.
    @Date: May 2012
*/

    
(function ($) {
    
    // home slideshow
	if ( $('.slideshow').length > 0 ) {
		
		var speedy = 500; // vitesse de transition
		var duration = 9000; // délais entre chaque slide
		var auto_direction = 'next'; // sens de défilement auto
		
		var $slider = $('.slideshow');
		var nb_slides = $('li', $slider).length;
		
		var auto_slide= false;
		
		// ajout des liens next et prev
		$slider.prepend('<a href="#prev" class="prev"></a> <a class="next" href="#next"></a>');
		
		// initialisation
		$('li', $slider).hide();
		$('li:eq(0)', $slider).show().addClass('current');
		
		
		// functions
		function next() {
			
			clearInterval(auto_slide);
			auto_slide = false;
			
			var the_next = $('li.current', $slider).next('li');
			var nb_next = the_next.size();
			if ( nb_next == 0 ) {
				$('li.current', $slider).fadeOut(speedy).removeClass('current');
				$('li:eq(0)', $slider).fadeIn(speedy, function(){ 
					auto_slide = setInterval(slide_auto, duration); 
				}).addClass('current');
			}
			else {
				$('li.current', $slider).fadeOut(speedy).removeClass('current');
				the_next.fadeIn(speedy, function(){
					auto_slide = setInterval(slide_auto, duration);
				}).addClass('current');
			}
			
		}
		
		function prev() {
			
			clearInterval(auto_slide);
			auto_slide = false;
			
			var the_prev = $('li.current', $slider).prev('li');
			var nb_prev = the_prev.size();
			if ( nb_prev == 0 ) {
				$('li.current', $slider).fadeOut(speedy).removeClass('current');
				$('li:eq(' + (nb_slides-1) + ')', $slider).fadeIn(speedy, function(){
					auto_slide = setInterval(slide_auto, duration);
				}).addClass('current');
			}
			else {
				$('li.current', $slider).fadeOut(speedy).removeClass('current');
				the_prev.fadeIn(speedy, function(){
					auto_slide = setInterval(slide_auto, duration);
				}).addClass('current');
			}
			
		}
		
		function slide_auto() {
			if ( auto_direction == 'next' ) $('.next', $slider).trigger('click');
			else $('.prev', $slider).trigger('click');
		}
		
		
		// actions
		$('.next', $slider).click(function(){ next(); return false; });
		$('.prev', $slider).click(function(){ prev(); return false; });
		
		auto_slide = setInterval(slide_auto, duration);

	}
    
    // figure fade
	
	if ( $('figure').length > 0) {
		$('.third_parts figure').each(function(){
			$(this).append('<span class="watermark"></span>');
			$('.watermark', $(this)).hide();
			
			$(this).hover(function(){
				$(this).find('.watermark').stop().fadeTo(350, 0.5);
			}, function(){
				$(this).find('.watermark').stop().fadeTo(350, 0);
			});
			$(this).closest('a').focus(function(){ $(this).find('figure').trigger('mouseenter');});
			$(this).closest('a').blur(function(){ $(this).find('figure').trigger('mouseleave');});
		});
		
	}
    
	
})(jQuery)
/* 
	@Author: Alsacréations
	@Date: Oct. 2011

    @Autor: Clément Dietschy
    @Author: Seizam Sàrl.
    @Date: July 2012
*/

    
(function ($) {
    
    // slideshow / carrousel artist
	
	if($('#carrousel').length) {
				
		var $car = $('#carrousel');
		var $car_p = $car.find('#car_photos');
		var $car_s = $car.find('#car_slider');
		var nb_visible = 6;
		var nb_items =  $("li", $car_s).length;
		var item_width = $("li", $car_s).outerWidth(true);
		var nb_hidden = nb_items - nb_visible;
		
		
		
		// init
		$car_s.find('ul').css('width', nb_items * item_width + 'px');
		$car_p.find('li:not(:first)').hide();
		$car_s.find('li:first').addClass('current');
		$car_s.find('li:not(:first)').stop(true,true).fadeTo(300, 0.40);
		
		// DOM
		if(nb_items > nb_visible) {
			$car_s.append('<a href="#prev" class="prev"></a> <a href="#next" class="next"></a>');
		}
		
		
		// action d'affichage
		$car_s.find('li a').click(function() {
		
			var item_index = $(this).closest('li').index();
			$car_s.find('li.current').stop(true,true).fadeTo(300, 0.40).removeClass('current');
			$(this).closest('li').stop(true,true).fadeTo(300, 1).addClass('current');
			
			$car_p.find('li:visible').stop(true,true).fadeOut(300);
			$car_p.find('li:eq('+item_index+')').stop(true,true).fadeIn(500);
			
			return false;
		});
		
		// functions for scrolling
		var car_i = nb_visible - 1;
		var car_h = 0;
		
		function car_next() {
			$car_next = $car_p.find('li:eq('+car_i+')').next('li');
			
			if($car_next.length == 1) {
				$('ul', $car_s).stop(true,true).animate({'left':'-='+ item_width +'px'});
				car_i++;
				car_h++;
			}
			else {
				$('ul', $car_s).stop(true,true).animate({'left':'0px'});
				car_i = nb_visible - 1;
				car_h = 0;
			}
		}
		
		function car_prev() {
			if(car_h > 0) {
				$('ul', $car_s).stop(true,true).animate({'left':'+='+ item_width +'px'});
				car_i--;
				car_h--;
			}
			else {
				$('ul', $car_s).stop(true,true).animate({'left': '-'+ nb_hidden * item_width +'px'});
				car_i = nb_visible - 1 + nb_hidden;
				car_h = nb_hidden;
			}
		}
		
		// actions on prev/next click
		$('.next', $car_s).live('click', function() {
			car_next();
			return false;
		});
		$('.prev', $car_s).live('click', function() {
			car_prev();
			return false;
		});
		
	}
    
	
})(jQuery)
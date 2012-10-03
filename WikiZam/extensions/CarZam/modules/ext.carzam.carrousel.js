/* 
	@Author: Alsacréations
	@Date: Oct. 2011

    @Autor: Clément Dietschy
    @Author: Seizam Sàrl.
    @Date: July 2012
*/

    
(function ($) {
    
    // Carrousel
	
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
                $('ul', $car_s).stop(true,true).animate({
                    'left':'-='+ item_width +'px'
                });
                car_i++;
                car_h++;
            }
            else {
                $('ul', $car_s).stop(true,true).animate({
                    'left':'0px'
                });
                car_i = nb_visible - 1;
                car_h = 0;
            }
        }
		
        function car_prev() {
            if(car_h > 0) {
                $('ul', $car_s).stop(true,true).animate({
                    'left':'+='+ item_width +'px'
                });
                car_i--;
                car_h--;
            }
            else {
                $('ul', $car_s).stop(true,true).animate({
                    'left': '-'+ nb_hidden * item_width +'px'
                });
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
    
    // Slideshow
    if ( $('.slideshow').length > 0 ) {
		
        var $slider = $('.slideshow');
        
        var speedy = parseInt($slider.attr("data-speed")); // vitesse de transition
        if (isNaN(speedy))
            speedy = 500;
        var duration = parseInt($slider.attr("data-duration")); // délais entre chaque slide
        if (isNaN(duration))
            duration = 10000;
        var auto_direction = $slider.attr("data-direction"); // sens de défilement auto
        
        var nb_slides = $('li', $slider).length;
		
        var auto_slide= false;
        
        var img_height = $('div.wrapper', $slider).outerHeight(true);
        var arrow_pos = 10;
        if (img_height > 0)
            arrow_pos = img_height/2-20;
		
        // ajout des liens next et prev
        $slider.prepend('<a href="#prev" class="prev fade" style="top:'+arrow_pos+'px"></a> <a href="#next" class="next fade" style="top:'+arrow_pos+'px"></a>');
		
        // initialisation
        $('li', $slider).hide();
        $('li:eq(0)', $slider).show().addClass('current');
		
		
        // functions
        function next() {
            var the_next = $('li.current', $slider).next('li');
            if (the_next.size() == 0)
                the_next = $('li:eq(0)',$slider);
            
            slide(the_next);
        }
		
        function prev() {
            var the_prev = $('li.current', $slider).prev('li');
            if (the_prev.size() == 0)
                the_prev = $('li:eq(' + (nb_slides-1) + ')', $slider);
            
            slide(the_prev);
        }
        
        function slide(the_next) {
            clearInterval(auto_slide);
            auto_slide = false;
            $('li.current', $slider).fadeOut(speedy).removeClass('current');
            the_next.fadeIn(speedy).addClass('current');
            auto_slide = setInterval(slide_auto, duration);
        }
		
        function slide_auto() {
            if ( auto_direction == 'prev' ) prev();
            else next();
        }
		
		
        // actions
        $('.next', $slider).click(function(){
            next();
            return false;
        });
        $('.prev', $slider).click(function(){
            prev();
            return false;
        });
		
        auto_slide = setInterval(slide_auto, duration);

    }
    
	
})(jQuery)
/* 
	@Author: Alsacréations
	@Date : Oct. 2011
*/

(function ($) {
	
	$('html').removeClass('no-js');
	
	// home slideshow
	if ( $('.slideshow').length > 0 ) {
		
		var speedy = 500; // vitesse de transition
		var duration = 6000; // délais entre chaque slide
		var auto_direction = 'next'; // sens de défilement auto
		
		var $slider = $('.slideshow');
		var nb_slides = $('li', $slider).length;
		
		var auto_slide= false;
		
		// ajout des liens next et prev
		$slider.prepend('<a href="#prev" class="prev">Liberté précédente</a> <a class="next" href="#next">Liberté suivante</a>');
		
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
	
	
	// slideshow / carrousel artist
	
	if($('#carrousel').length) {
				
		var $car = $('#carrousel');
		var $car_p = $car.find('#car_photos');
		var $car_s = $car.find('#car_slider');
		var nb_visible = 5;
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
			$car_s.append('<a href="#prev" class="prev">Photo précédente</a> <a href="#next" class="next">Photo précédente</a>');
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
	
	// placeholder
	var place_val = [];
	
	$("input:text[placeholder], input[type=search][placeholder], input:password[placeholder]").each(function(i) {
		
		place_val[i] = $(this).attr('placeholder');
		$(this).val(place_val[i]);
		
		$(this).focus(function(){
			if ($(this).val() == place_val[i]) {
				$(this).val("");
			}
		}).blur(function(){
			if ($.trim($(this).val()) == "") {
				$(this).val(place_val[i]);
			}
		});
	});
	
	
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
	
	$('footer').mouseleave(function(){
		
		if( $('.more_infos:visible').length == 1 && clicked == false) {
			$('footer .more a').trigger('click');
		}
	});
	
	
	// footer auto-suggest
	if($('#search').length) {
	
		var ac_opened = false;
		
		function clear_as() {
			$('#auto_suggest').fadeOut(500, function(){
				$('#auto_suggest').remove();
			});
			ac_opened = false;
		}
		$('#search').keyup(function(e){
			if($("#search").val().length > 2) {
				
				if($('#auto_suggest').length == 0) {
					$('footer .content').prepend('<div id="auto_suggest"><a href="#search" class="ac_close" title="Fermer les suggestions">x</a><h4>Suggestions</h4><p>Aucune suggestion</p></div>');
					$('#auto_suggest').fadeIn(400);
					ac_opened = true;
				}
				
				$.get('js/ajax/auto_suggest_.html', function(data) {
					if(data) {
						$('#auto_suggest p').replaceWith('<ul>' + data + '</ul>');
					}
					
				}, 'html');
			}
			else {
				clear_as();
			}
		});
		
		$("#auto_suggest li a").live('click', function(){
			$('#search').val($(this).text());
			clear_as();
		});
		$('#auto_suggest a').live('click', function(e){ 
			clear_as();
		});
		
		
		// navigation clavier dans l'auto_suggest
		$('html').keydown(function(e){
			if(ac_opened) {
				if(e.keyCode == 40) {
					var afind = $('#auto_suggest li:first a');
					var afocus = $('#auto_suggest li a.focused');
					
					if (afocus.length == 1) {
						afocus.removeClass("focused");
						anext = afocus.closest('li').next('li');
						
						if(anext.length == 1 ) {
							anext.find('a').addClass("focused").focus();
						}
						else
							// afind.addClass("focused").focus();
							$("#search").focus();
					}
					else
						afind.addClass("focused").focus();
					
					return false;
				}
				if(e.keyCode == 38) {
					var afind = $('#auto_suggest li:last a');
					var afocus = $('#auto_suggest li a.focused');
					
					if (afocus.length == 1) {
						afocus.removeClass("focused");
						aprev = afocus.closest('li').prev('li');
						
						if(aprev.length == 1 ) {
							aprev.find('a').addClass("focused").focus();
						}
						else 
							// afind.addClass("focused").focus();
							$("#search").focus();
					}
					else
						afind.addClass("focused").focus();
					
					return false;
				}
				else if(e.keyCode == 9) {
					var alast = $('#auto_suggest li:last a:focus');
					if(alast.length == 1) {
						$("#search").focus();
						$('#auto_suggest li a').removeClass('focused');
						return false;
					}
					$('#auto_suggest li a').removeClass('focused');
				}
			}
		});

	}
	
	// avatar

	$('.edit_avatar input').after('<a href="#" class="button">Parcourir</a>');
	$('.edit_avatar input').hide();
	$('.edit_avatar .button').live('click', function(){
		$('.edit_avatar input').trigger('click');
		return false;
	});
	
	
	// help dans le formulaire
	if ( $('#help_zone').length == 1 ) {
		
		var init_pos = $('#help_zone').offset();
		var init_top = init_pos.top;
		
		$('.help').mouseenter(function(){
			
			var help_parent = $(this).closest('.radio_line');
			
			if(help_parent.length>0) { // cas des labels multiples sur radio
				var help_title = $(this).siblings('.label_like').text();
			}
			else { // autres cas
				var help_title = $(this).siblings('label').text();
				if (!help_title) help_title = $(this).siblings('.label_like').text();
			}
			var help_msg = $(this).text();
			var help_pos = $(this).offset();
			var help_top = help_pos.top;
			
			$('#help_zone').stop().animate({
				'margin-top': (help_top-init_top-32)+'px'
			}, 500);
			$('#help_zone p').text(help_msg);
			$('#help_zone h4').text(help_title);
		});
	}
	
	
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

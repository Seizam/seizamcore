/* 
	@Author: Alsacréations
	@Date : Oct. 2011
*/

(function ($) {
	
    $('html').removeClass('no-js');
	
	
    // footer slide
    var clicked = false;
	
    $('.more_infos').hide();
    $('#footer li.more:not(".current") a').live("click", function() {
        $('.more_infos').stop(true,true).slideDown(400);
        $(this).closest('li').addClass('current');
        $(this).find('.show_more').hide();
        $(this).find('.show_less').show();
        clicked = false;
        return false;
    });
    $('#footer li.more.current a').live("click", function() {
        $('.more_infos').stop(true,true).slideUp(250);
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
            $("ul", $dd).stop(false, true).slideDown(400);
        }, function(){
            $("ul", $dd).stop(false, true).slideUp(250);
        });
		
        $('a', $dd).focus(function(){
            $dd.trigger("mouseenter");
        });
        $('ul li:last-child a', $dd).blur(function(){
            $dd.trigger("mouseleave");
        });
	
    }
        
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
            var help_msg = $(this).html();
            var help_pos = $(this).offset();
            var help_top = help_pos.top;
			
            $('#help_zone').stop().animate({
                'margin-top': (help_top-init_top-32)+'px'
            }, 500);
            $('#help_zone p').html(help_msg);
            $('#help_zone h4').text(help_title);
        });
    }
    
    
    //Language Selector Dropdown Menu autosubmit    
    $( function() {
        var i = 1;
        while ( true ) {
            var btn = document.getElementById("languageselector-commit-"+i);
            var sel = document.getElementById("languageselector-select-"+i);
            var idx = i;

            if (!btn) break;

            btn.style.display = "none";
            sel.onchange = function() {
                node = this.parentNode;
                while( true ) {
                    if( node.tagName.toLowerCase() == "form" ) {
                        node.submit();
                        break;
                    }
                    node = node.parentNode;
                }
            };

            i++;
        }
    });
	
})(jQuery)

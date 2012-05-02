/* 
	@Author: Alsacréations
	@Date : Oct. 2011
*/

(function ($) {
    
    // footer margin
    $('#content').wrap('<div id="heightwrapper" />');
    $('#heightwrapper').css('min-height',$(window).height()-55);
	
	
    // footer slide
    $('#absoluteFooter li.more').show();
    var intElemScrollTop = 0;
	
    $('#absoluteFooter li.more:not(.current, .inactive) a').live("click", function() {
        intElemScrollTop = $('html').scrollTop();
        $('html').scrollTo('div#footer',1000);
        $(this).closest('li').addClass('inactive');
        setTimeout(function() {
            $('#absoluteFooter li.more').closest('li').removeClass('inactive');
            $('#absoluteFooter li.more').closest('li').addClass('current');
            $('#absoluteFooter li.more').find('.show_footer').hide();
            $('#absoluteFooter li.more').find('.show_back').show();;
        }, 1000);
        return false;
    });
    
    $('#absoluteFooter li.more.current a').live("click", function() {
        $('html').scrollTo({
            top:intElemScrollTop, 
            left:0
        },1000);
        $(this).closest('li').addClass('inactive');
        setTimeout(function() {
            $('#absoluteFooter li.more').closest('li').removeClass('inactive');
            $('#absoluteFooter li.more').closest('li').removeClass('current');
            $('#absoluteFooter li.more').find('.show_footer').show();
            $('#absoluteFooter li.more').find('.show_back').hide();
        },1000);
        return false;
    });
	
    // dropdown menu - menu déroulant
    
	$('#nav .nav_actions').show();
    
    if($('#nav .nav_actions').length == 1) {
	
        var $dd = $('#nav .nav_actions');
		
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

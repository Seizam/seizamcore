/* 
	@Author: Alsacréations
	@Date: Oct. 2011

    @Autor: Clément Dietschy
    @Author: Seizam Sàrl.
    @Date: May 2012
*/

    
(function ($) {
    
    
    // footer margin
    $('div#heightwrapper').css('min-height',$(window).height()-55);
	
	
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
        
        $dd.click(function () {
            if ($("ul", $dd).is(":hidden")) {
                $("ul", $dd).slideDown(300);
            } else {
                $("ul", $dd).slideUp(300);
            }
        });

	
    }
    
    
    // ContentFooter déroulant
    
    if ( $('#contentFooterWrapper').length == 1) {
        
        var $df = $('#contentFooterWrapper');
        
        $("div.controlled", $df).slideUp(300);
        $("a.control", $df).show();
        
        $df.click(function(event){
            if ($("div.controlled", $df).is(":hidden")) {
                event.preventDefault();
                $("div.controlled", $df).slideDown(300);
                $("a.control", $df).hide();
            } else {
                $("div.controlled", $df).slideUp(300);
                $("a.control", $df).show();
            }
        });
    }
    
    // help dans le formulaire
    
    if ( $('#help_zone').length == 1 ) {
		
        var init_pos = $('#help_zone').offset();
        var init_top = init_pos.top;
		
        $('.help').mouseenter(function(){
			
            var help_parent = $(this).closest('.radio_line');
            var help_title;
			
            if(help_parent.length>0) { // cas des labels multiples sur radio
                help_title = $(this).siblings('.label_like').text();
            } else { // autres cas
                help_title = $(this).siblings('label').text();
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
	
})(jQuery)
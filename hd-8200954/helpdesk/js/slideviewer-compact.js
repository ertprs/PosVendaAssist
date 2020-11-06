// Compacted by ScriptingMagic.com
/*
	slideViewer customised to slide divs instead of images. Original slideViewer by Gian Carlo Mingati can be found here: 
	http://www.gcmingati.net/wordpress/wp-content/lab/jquery/imagestrip/imageslide-plugin.html
	
	Requires jQuery and the jQuery easing plugin, both available via http://www.jquery.com
	Also dependant on CSS included in index.html
*/
jQuery(function(){jQuery("div.svw").prepend("<img src='/js/loading.gif' class='ldrgif' alt='Carregando...'/ >")});var j=0;jQuery.fn.slideView=function(a){a=jQuery.extend({easeFunc:"expoinout",easeTime:750,toolTip:false},a);return this.each(function(){var b=jQuery(this);b.find("img.ldrgif").remove();b.removeClass("svw").addClass("stripViewer");var c=b.find("div.panel").width();var d=b.find("div.panel").size();var e=c*d;b.find("div.panelContainer").css("width",e);var f=1;var g=d*2;b.each(function(i){jQuery(this).before("<div class='stripNavL' id='stripNavL"+j+"'><a href='#'>Left</a></div>");jQuery(this).after("<div class='stripNavR' id='stripNavR"+j+"'><a href='#'>Right</a></div>");jQuery(this).before("<div class='stripNav' id='stripNav"+j+"'><ul></ul></div>");jQuery(this).find("div.panel").each(function(n){jQuery("div#stripNav"+j+" ul").append("<li><a href='#'>"+jQuery(this).attr("title")+"</a></li>")});jQuery("div#stripNav"+j+" a").each(function(z){g+=jQuery(this).parent().width();jQuery(this).bind("click",function(){jQuery(this).addClass("current").parent().parent().find("a").not(jQuery(this)).removeClass("current");var h=-(c*z);f=z+1;jQuery(this).parent().parent().parent().next().find("div.panelContainer").animate({left:h},a.easeTime,a.easeFunc);return false})});jQuery("div#stripNavL"+j+" a").click(function(){if(f==1){var h=-(c*(d-1));f=d;jQuery(this).parent().parent().find("div.stripNav a.current").removeClass("current").parent().parent().find("li:last a").addClass("current")}else{f-=1;var h=-(c*(f-1));jQuery(this).parent().parent().find("div.stripNav a.current").removeClass("current").parent().prev().find("a").addClass("current")}jQuery(this).parent().parent().find("div.panelContainer").animate({left:h},a.easeTime,a.easeFunc);return false});jQuery("div#stripNavR"+j+" a").click(function(){if(f==d){var h=0;f=1;jQuery(this).parent().parent().find("div.stripNav a.current").removeClass("current").parent().parent().find("a:eq(0)").addClass("current")}else{var h=-(c*f);f+=1;jQuery(this).parent().parent().find("div.stripNav a.current").removeClass("current").parent().next().find("a").addClass("current")}jQuery(this).parent().parent().find("div.panelContainer").animate({left:h},a.easeTime,a.easeFunc);return false});jQuery("div#stripNav"+j).css("width",g);jQuery("div#stripNav"+j+" a:eq(0)").addClass("current")});j++})}
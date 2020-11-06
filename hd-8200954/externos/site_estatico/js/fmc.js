$('.menu a, .menu-fade').on('click touchstart', function(e) {
$('html').toggleClass('menu-active');
e.preventDefault();
});
$('.menu-tab h1 a').on('click',function(hide) {
$('html').removeClass('menu-active');
});

$(function() {
$('a[href*=#]:not([href=#])').click(function() {
if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
  var target = $(this.hash);
  target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
  if (target.length) {
    $('html,body').animate({
      scrollTop: target.offset().top
    }, 700, "swing");
    return false;
  }
}
});
});


if(!($mobile)) {
$(function(){
$(window).resize(function(){
var elem = $(this);
$('#fullpage').css({height:elem.height()});
});
$(window).resize();
})
};

if($mobile) {
$(function(){
$(window).resize(function(){
var elem = $(this);
$('.m-fullpage').css({height:elem.height()});
});
$(window).resize();
})
};

if(!($mobile)) {
$(document).scroll(function () {
  var x = $(this).scrollTop();
    var height = $(window).height()/2;
    if(x>=height) {
    $('.menu').addClass('coloured');
    }
    else {
    $('.menu').removeClass('coloured');
    }
})
};


/* Saiba mais */
$(document).scroll(function () {
var y = $(this).scrollTop();
if(y<50) {
$('.saibamais').removeClass('hide');
}
else {
$('.saibamais').addClass('hide');
}
});
if($mobile) {
$('.mod-sp .table.h-img').after('<div id="saibamais"></div>');
} else {
$('.mod-sp .saibamais').after('<div id="saibamais"></div>')
}

/* mobile */

if($mobile) {
$(document).scroll(function () {
  var x = $(this).scrollTop();
    var height = $( window ).height();
    if(x>=height) {
    $('.menu').addClass('coloured');
    }
    else {
    $('.menu').removeClass('coloured');
    }
})
};

$(function(){
$(window).resize(function(){
var elem = $(this);
$('.fullpage').css({height:elem.height()});
});
$(window).resize();
});

if($mobile) {
  $('body').addClass('mobile');
  $('.modulos ul').addClass('mobile');
  $( ".mod-sp .table.h-img" ).wrap( "<div id='fullmobile'></div>" );
  $(function(){
  $(window).resize(function(){
  var elem = $(this);
  $('#fullmobile').css({height:elem.height()});
  });
  $(window).resize();
  })
};

$('a.maplink').on('click touchstart', function(map){
$('html').toggleClass('map-active');
  map.preventDefault();
});
$('.map-close').on('click touchstart', function(map){
$('html').removeClass('map-active');
  map.preventDefault();
});
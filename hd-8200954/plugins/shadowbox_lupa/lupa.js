$(function () {

	var height = $(window.parent.document.getElementById("sb-wrapper-inner")).height();
	$("#container_lupa").css({ "height": height+"px" });

	$(window.parent.document.getElementById("sb-wrapper-inner")).resize(function (e) {
		var height = $(window.parent.document.getElementById("sb-wrapper-inner")).height();
		$("#container_lupa").css({ "height": height+"px" });
	});

});
(function($) {
	"use strict";

	// highlight current nav item
	var url = window.location.pathname, items = $(".main-menu").find("li");
	items.removeClass("current");
	items.each(function(){
		var a = $(this).find("a");
		if (a.attr("href") == url) {
			$(this).addClass("current");
		}
	});

	// full screen css
	function fullScreen() {
		$(".full-screen").css("height", $(window).height());
	}

	fullScreen()

	// scroll to a specific div
	if ($(".scroll-to-target").length){
		$(".scroll-to-target").on("click", function() {
			var target = $(this).attr("data-target");
		    $("html, body").animate({
			   scrollTop: $(target).offset().top
			}, 1000);
	
		});
	}

	// when document is resized, do
	$(window).on("resize", function() {
		fullScreen();		
	});


	// when document is scrolling, do
	$(window).on("scroll", function() {
		fullScreen()
	});

	// cookie stuff
	if (Cookies.get("cookieaccept") === undefined) {
		$("#cookie-box").show();
	}
	else {
		$("#cookie-box").hide();
	}

	window.set_cookie = function() {
		Cookies.set("cookieaccept", "1");
		$("#cookie-box").hide();
	};
	
})(window.jQuery);
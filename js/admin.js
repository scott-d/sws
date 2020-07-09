// admin script

var panel = {};
var tabs = {};
var editor = {};
var site = {};

function _splitter() {
	var md, s = $("#separator");
	const first = $("#admin-panel");
	const second = $("#site-wrapper");
	s.on("mousedown", onMouseDown);

	function onMouseDown(e) {
		md = {
			e,
			offsetLeft: s[0].offsetLeft,
			offsetTop: s[0].offsetTop,
			firstWidth: panel[0].offsetWidth,
			secondWidth: site[0].offsetWidth
		};
		$(document).on("mousemove", onMouseMove);
		$(document).on("mouseup", function() {
			$(document).off("mousemove");
			$(document).off("mouseup");
		});
	}

	function onMouseMove(e) {
		var delta = {
			x: e.clientX - md.e.clientX,
			y: e.clientY - md.e.clientY
		};
		delta.x = Math.min(Math.max(delta.x, -md.firstWidth), md.secondWidth);
		s.css({left: md.offsetLeft + delta.x});
		first.width(md.firstWidth + delta.x);
		second.width(md.secondWidth - delta.x);
	}
}

function _tabs() {
	panel.append("<ul id=\"admin-tabs\"></ul>");
	tabs = $("#admin-tabs");
	tabs.append("<li><i class=\"fas fa-home\"></i>");
	tabs.append("<li><i class=\"fas fa-edit\"></i>");
	tabs.append("<li><i class=\"fas fa-file-upload\"></i>");
}

//window.addEventListener('load', function() {

$(function() {
	$("#scroll-top").remove();
	$("#cookie-box").remove();
	$("body").addClass("splitter");
	panel = $("#admin-panel");
	tabs = $("#admin-tabs");
	editor = $("#admin-editor");
	site = $("#site-wrapper");
	_splitter();
	_tabs();
});

/* EOF */
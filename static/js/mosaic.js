
var root = "http://"+document.location.host;
var base = root+document.location.pathname;
var moretoload = true;
var cururl = base; 
var start = 11;
var cursor = 0;
var special;
var lasturl = "/";

function popdown(el, content, position, list, classes){
	var cls = position == "over" ? "over" : "down";
	classes = classes ? classes : "";
	var pop = $("<div class='pop"+cls+" "+classes+" "+el[0].id+(list ? " list" : "")+"'><img src='static/images/arrow_"+cls+".png' class='arrow'></div>");
	pop.append(content);
	$(".popdown, .popover").detach();
	pop.appendTo($("body"));
	var elPos = el.offset();
	var oLeft = elPos.left - pop.width()/2 +10;
	var oTop = elPos.top +5;
	oTop += cls == "over" ? -el.height() : el.height();
	oTop += cls == "over" ? -pop.height() : 0;
	if(!pop.is(".preview, .submitter_data")){
		pop.css("opacity", 0.95);
	}
	pop.css("left", oLeft);
	pop.css("top", oTop);
	/*pop.delay(100).position({
		of: el,
		at: cls == "over" ? "top" : "bottom",
		my: cls == "over" ? "bottom" : "top",
		offset: cls == "over"? "0 -8" : "0 5"
	});*/
	if(!pop.is(".search, .embed, .submitter_data, .preview, .site_mosaic, .site_instant, .link")){
		pop.delay(1000).fadeOut(1000, function(){
			pop.detach();
		});
		pop.mouseover(function(){
			pop.stop();
			pop.css("opacity", 0.95);
		});
	}
	$("body").click(function(ev){
		if(!$(ev.target).closest(".popdown, .popover").length && !$(".sort_cat").length){
			pop.detach();
			$(".overlay").fadeOut(function(){$(this).detach()});
		}
	});
	return pop;
}

function markActive(el){
	if(el.length){
		el.attr("src", el.attr("src").replace("checkbox.png", "checkbox_active.png"));
	}
}

function unmarkActive(el){
	el.attr("src", el.attr("src").replace("checkbox_active.png", "checkbox.png"));
}

function previewCrop(img, selection) {
	var scaleX = 300 / ((selection.x2 - selection.x1) || 1);
	var scaleY = 250 / ((selection.y2 - selection.y1) || 1);
	$('#overlay > div > img, #img_container img').css({
		position: 'absolute',
		width: Math.round(scaleX * $(img).width()) + 'px',
		height: Math.round(scaleY * $(img).height()) + 'px',
		left: '-' + Math.round(scaleX * selection.x1) + 'px',
		top: '-' + Math.round(scaleY * selection.y1) + 'px'
	});
	$("form input[name='selection']").val(selection.x1+","+selection.y1+","+selection.x2+","+selection.y2);
}

function crypt(text, decrypt) {
    var temp="";
    for (var i=0; i<text.length; i++) {
        var chr = text.charCodeAt(i);
        var code = decrypt ? chr+1 : chr-1;
        temp += String.fromCharCode(code);
    }
    return temp;
}

function getCats(){
    var link = "";
	$(".popdown.sort_cat a:gt(1)").each(function(){
		if($(this).children("img[src*='active']").length){
			if(link != ""){
				link += "_";
			}
			link += slugify($(this).text());
		}
	});
	return link;
}

function catSelect(){
	var base = $("a#sort_cat").attr("href");
	link = getCats();
	$(".popdown.sort_cat a:gt(1)").each(function(){
		var text = slugify($(this).text());
		var cats = "";
		if(!$(this).children("img[src*='active']").length){
			cats = link.replace(new RegExp("_*"+text+"_*"), "");
		}else{
			cats = (link!="" ? link+"_" : "")+text;
		}
		if(cats == ""){
			cats = "none";
		}
		$(this).attr("href", base.replace("/all", "/"+cats));
	});
}

function markLast(){
	$("#content li").removeClass("last").filter(":last").addClass("last");
}
function ident(el){
	return $(el).find(".title, .info").text();
}

function ajaxLoad(url, callb){
	start = 11;
	cursor = start;
	base = url.substring(0, url.lastIndexOf('/')+1);
	countClick(url);
	$.ajax({
		dataType: "json",
		url: url,
		success: function(data){
			lasturl = cururl;
			cururl = url;
			$("#mosaics>img, #list>img").detach();
			$("#new").html(data.data[0].mosaics);
			$("#nav").replaceWith(data.data[0].nav);
			if(data.data[0].mosaics.length > 320){
				callback = function(){
					if(callb){
						callb();
					}
					moretoload = true;
					fillScreen();	
				};
				$("#mosaics ul, #list ul").quicksand($("#new li"), {attribute: ident, useScaling: false}, function(){callback();});
			}else{
				$("#mosaics, #list").find("li").fadeOut(function(){
					$("#mosaics, #list").html("<img src='"+STATIC_URL+"images/noresult.png' alt='Could not find any Mosaic' class='noresult'/><ul></ul>").hide().fadeIn();
				});
			}
		}
	});	
	return false;
}

function validateMosaic(){
	function makeValid (el){el.parent("p").css("background-image", "url("+STATIC_URL+"images/checkbox_valid.png)")};
	function makeInvalid (el){el.parent("p").css("background-image", "url("+STATIC_URL+"images/checkbox.png)")};
	var form = $(".mosaic_admin form");
	var link = form.find("#id_link");
	form.find("select[name='cat'], #id_title").each(function(){
		if($.trim($(this).val()) != ""){
			makeValid($(this));
		}else{
			makeInvalid($(this));
		}
	});
	if(link.val().match(/http:\/\/.*/)){
		makeValid(link);
	}else{
		makeInvalid(link);
	}
	if($("select[name='cat'] option:selected").text().toLowerCase() == "special"){
		$("p.link").hide();
		$("form .special").show();
	}else{
		$("p.link").show();
		$("form .special").hide();
	}
}

var xhr; 
function loadinfinity(){
	start = $("#content li").length-$("#content li a.cat:contains(SPONSORED)").length;
	var url = cururl+(cururl.match(/\?/) ? "&" :"?")+"start="+start+"&more";
	if(!xhr || xhr.readyState==4){
		xhr = $.ajax({
			url: url,
 			dataType: "json",
			success: function(data){
				if(data.data[0].mosaics.length > 10){
					$("#new").html(data.data[0].mosaics);
					$("#new li").hide().appendTo("#mosaics ul, #list ul").fadeIn();
					markLast();
					moretoload = true;
				}else{
					moretoload = false;
					markLast();
				}
			}
		});
	}
}

function fillScreen(){
	while($("#content").height() <= $("body").height()-$("#content").offset().top && moretoload && (!xhr || xhr.readyState==4)){
		loadinfinity();
	}
	markLast();
}

function slugify(text){
	return text.replace(/\s+/g,'-').replace(/[^a-zA-Z0-9\-]/g,'').toLowerCase();
}

$(document).ready(function(){

	//Contact Form Mails
	$("a[href*='mailto:']").each(function(){
		var l = $(this).attr("href");
		$(this).attr("href", l.replace(l.substring(7), crypt(l.substring(7), true)));
		if($(this).text() == l.substring(7)){
			$(this).text($(this).attr("href").substring(7));
		}
	});

	//Hover
	$("#mosaics li").live("mouseover", function(){
		//$(this).stop(true, true).children('.img').fadeTo("fast", 0.8);
		$(this).stop(true, true).children('.info').fadeTo("fast", 1);
	}).live("mouseout", function(){
		//$(this).stop(true, true).children('.img').fadeTo("fast", 1);
		$(this).stop(true, true).children('.info').hide();
	});

	// Popdowns
	$("#site_mosaic").live("mouseover click", function(){
		var pop = popdown($(this), $(this).siblings(".site_mosaic_data").contents().clone(), "down", true);
	});
	$("#site_instant").live("mouseover click", function(){
		var pop = popdown($(this), $(this).siblings(".site_instant_data").contents().clone(), "down", true);
	});
	$("#nav #search").live("mouseover click", function(){
		var pop = popdown($(this), "<form action='.' method='get'><input type='text' name='search'/></form>");
		pop.find("input").focus();
	});
	$("#nav #sort_time, #nav #sort_love, #nav #sort_cat").live("mouseenter click", function(e){
		popdown($(this), $(this).next(".data").contents().clone(), "down", true);
		e.preventDefault();
	});
	$(".popdown.sort_time a, .popdown.sort_love a").live("hover", function(){
		unmarkActive($(this).siblings("a").children("img"));
		markActive($(this).children("img"));
	});
	$(".popdown.sort_cat a").live("click", function(e){
		//Multi Cat Selection
		if($(this).text() == "all"){
			markActive($(".popdown.sort_cat a").find("img"));
		}else if($(this).text() == "none"){
			markActive($(this).find("img"));
			unmarkActive($(".popdown.sort_cat a").find("img"));
		}
		if($(this).children("img[src*='active']").length){
			unmarkActive($(this).children("img"));
		}else{
			markActive($(this).children("img"));
		}
		var activeCats = $(".popdown.sort_cat a img[src*='active']").length;	
		if(activeCats >= 1){
			if(activeCats >= $(".popdown.sort_cat a").length-1){
				markActive($(".popdown.sort_cat a:first img"));
			}else{
				unmarkActive($(".popdown.sort_cat a:first img"));
			}
			unmarkActive($(".popdown.sort_cat a:eq(1) img"));
		}else{
			unmarkActive($(".popdown.sort_cat a:first img"));
			markActive($(".popdown.sort_cat a:eq(1) img"));
		}
		catSelect();
		ajaxLoad($(this).attr("href"));	
		e.preventDefault();
	});
	$(".popdown a, .popover a").live("click", function(e){
		if(!$(this).closest(".sort_cat, .profile, .submitter_data, .site_mosaic, .site_instant").length){
			$(this).parents(".popdown, .popover").detach();
			$(".overlay").fadeOut(function(){$(this).detach()});
			return false;
		}
		if($(this).closest(".sort_cat").length){
			return false;
		}
	});	
	$("#nav #profile").live("mouseenter click", function(){
		var pop = popdown($(this), $(this).siblings(".profile_data").contents().clone(), "down", true);
		if(pop.find("a[href*='login']").length){
			pop.addClass("guest");
		}
		return false;
	});
	$("#list .title").live("mouseenter", function(e){
		var preview = popdown($(this), $(this).siblings(".title_data").contents().clone(), "over", false, "preview");
		preview.css("left", e.pageX-preview.width()/2+"px");
	});

	// Avoid Hover effect on active Mosaics	
	$("#nav a.active").hover(function(){$(this).css("visibility", "visible")});

	//Listview
	$("#list .submitter").live("mouseenter", function(e){
		var submitter = popdown($(this), $(this).children(".data").contents().clone(), "over", true, "submitter_data");
		submitter.css("left", e.pageX-submitter.width()/2+"px");
	});
	
	//Ajax Loading
	$("#nav a:not(#view_list, #view_mosaic, .profile a, #profile, #sort_cat), .sort_time a, .sort_love a, #list .cat a, #list .submitter a, #list .date a, #mosaics .cat, a.back").live("click", function(e){
		var callback;
		if(e.target.id=="shuffle"){
			callback = function(){
				$("#shuffle").children("img").hide().fadeIn("slow");
			}
		}
		ajaxLoad($(this).attr("href"), callback);
		e.preventDefault();
	});
	$(".search form").live("submit", function(e){
		ajaxLoad(base+"?"+$(this).serialize());
		fillScreen();
		e.preventDefault();
	});
	$(".search input").live("keypress", function(e){
		if(e.which == '13') {
			$(".search form").trigger("submit");
			e.preventDefault();
		}
	});
	
	$("body").live("click", function (){
		if($(".sort_cat").length && !$(this).closest(".sort_cat").length){
			$(".sort_cat").detach();
		}
	});
	$("#view_list, #view_mosaic").live("click", function(e){
		var url = $(this).attr("href");
		$.ajax({
			dataType: "json",
			url: url,
			success: function(data){
				var target = url.match(/list\//) != null ? "list" : "mosaics";
				base = root+"/"+target+"/all/";
				$("#nav").replaceWith(data.data[0].nav);
				$("#mosaics, #list").fadeOut("slow", function() {
					$(this).html("<ul>"+data.data[0].mosaics+"</ul>");
					$("body").attr("class", target);
					$(this).attr("id", target).fadeIn();
					fillScreen();
				});
				lasturl = cururl;
				cururl = url;
				moretoload = true;
				start = 11;
			}
		});	
		return false;
	});
	// Keep loading
	$(window).scroll(function(){
		if(moretoload){
			if($(window).scrollTop() >= $(document).height() - $(window).height()){
				loadinfinity();
			}
		}
	});
	fillScreen();

	// Mosaic Actionbar
	// Smoothly load Specials
	function transformSpecial(el){
		special = el.closest("li");		
		return function(){
			$("#mosaics .img:first").fadeOut(function(){
				$(this).hide();
				$(this).html("<a class='back' href='"+lasturl+"'><img src='"+STATIC_URL+"images/back.png'/></a>").fadeIn();
			});	
		}
	}
	$(".title a, .info a.gt(0), .img a").live("click", function(e){
		if($(this).attr("href").match(/special/)){
			var back = transformSpecial($(this));
			ajaxLoad($(this).attr("href"), back);
			e.preventDefault();
		}
	});
	if(base.match(/special-/)){
		$("a[href*='special-']:first").each(function(){
			var back = transformSpecial($(this));
			back();
		});
	}
/*
	$("#mosaics .info").live("mouseover click touchstart", function(){
		if(!$(this).is(":animated") && $(this).next(".actions").children("a").length){
			$(this).stop().animate({bottom: "42px"});
		}
	});
	$("#mosaics li").live("mouseleave", function(){	
		if(!$(".popdown.link").length){
			$(this).find(".info").stop().animate({bottom: "0px"});
		}
	});
*/

	
	//Form Style
	$("input[type=checkbox], input[type=radio]").wrap("<span class='checkbox'/>").change(function(){
		var parent = $(this).parent(".checkbox, .radio");
		if($(this).attr("checked")){
			parent.css("background-image", "url("+STATIC_URL+"images/checkbox_active.png)");
		}else{
			parent.css("background-image", "url("+STATIC_URL+"images/checkbox.png)");
		}
	}).trigger("change");

	$(".checkbox, .radio").live("click", function(){
		var input = $(this).children("input");
		input.attr("checked", !input.attr("checked")).trigger("change");
	});

	$("select").after($("<span class='selectButton'></span>"));


});

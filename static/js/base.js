$(function() { 

	// Ajax activity indicator bound 
	// to ajax start/stop document events
	$(document).ajaxStart(function(){ 
		$('#ajaxBusy').show(); 
		//alert($(window).scrollTop());
		var $pos = $(window).scrollTop() + Math.ceil($(window).height() / 2);
		$('#ajaxBusy').css("top", $pos + "px");
	}).ajaxStop(function(){ 
		$('#ajaxBusy').hide();
	});


	$(window).resize(function() {
		// Load images if no vertical scrollbar present
		if($(document).height() == ($(window).height() + $(window).scrollTop())) {
			nextBatch();
		}
	});

	$('#mosaics > ul').masonry({
		columnWidth: 4,
		itemSelector: '.item_image',
		isAnimated: true,
		animationOptions: {
			duration: 450,
			easing: 'linear',
			queue: false
  		}
	});

	//Scroll Detection
	$(window).scroll(function(){
		var $pos = $(window).scrollTop() + Math.ceil($(window).height() / 2);
		$('#ajaxBusy').css("top", $pos + "px");
        	//if  (($(window).scrollTop() > (($(document).height() - $(window).height()) - 500))){
		if  (($(window).scrollTop() / ($(document).height() - $(window).height())) > 0.75){
			nextBatch();
			var $next_link = $('#page-nav a').attr('href');
			if($next_link != "") nextBatch();
		}
	});

	
	//Loading More content
	function nextBatch()
	{
		var $next_link = $('#page-nav a').attr('href');
		$('#page-nav a').attr('href','');

		if($next_link != "") {

			var new_href = $next_link.replace("&x=1","");
			//alert(new_href);

			// Assign handlers immediately after making the request,
			// and remember the jqxhr object for this request
			var jqxhr = $.get($next_link, function(data) {

				history.pushState(null, null, new_href);

				$('#newBatch').html(data);
				$next_link = $('#newBatch #page-nav a').attr('href');
				$('#page-nav a').attr('href', $next_link);
				//alert($next_link);
				$('#newBatch #page-nav').remove();
				//alert("success");

				// Find duplicates
				$("#newBatch a img").each(function(intIndex){

					var $curImg = $(this).attr('src');
					if($("img[src$='" + $curImg + "']")) 
						$("#newBatch a img[" + intIndex +"]").remove();

				});


				var $newElems = $($('#newBatch').html());
				$('#mosaics > ul').append($newElems).masonry('appended', $newElems, true);
				$('#newBatch').html("");

				//$("#newBatch .item_image").appendTo("#mosaics > ul");
				if($(document).height() == ($(window).height() + $(window).scrollTop())) {
					nextBatch();
				}
  			})

  		}
  		$next_link = "";
 		// Set another completion function for the request above
  		//jqxhr.complete(function(){ alert("second complete"); });
	};

	// Load images if no vertical scrollbar present
	if($(document).height() == ($(window).height() + $(window).scrollTop())) {
		nextBatch();
	}




});

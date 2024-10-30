// Collect scroll events for this viewing, page refresh counts as a new view
var scrollInterval = 100;
var myHeight;
var maxInterval;

var dataId = -1;
var scrollId = -1;
var timeStart = 0;
var timeToClick = 0;
var scrollTimeout = null;
var ajaxTimeout = 3000; // in milliseconds
var intRegex = /^\d+$/;

jQuery(document).ready(function() {
	var d = new Date();
	timeStart = Math.round(d.getTime()/1000);

	myHeight = getWindowHeight();
	maxInterval = Math.floor(myHeight / scrollInterval);

	if (scrollId == -1) {
		jQuery.ajax({
			type: "POST",
			url: MyAjax.ajaxurl,
			data: {
				action    : 'heat_trackr_add_scroll',
				url       : document.URL,
				height    : maxInterval * scrollInterval,
				count     : 1,
				scrollId  : scrollId
			},
			timeout: ajaxTimeout,
			success: function(response) {
				//alert("success: " + response);
				if (intRegex.test(response)) { // only accept the response if it's the new table entry id (integer value)
					scrollId = response;
				}
				return true;
			},
			error: function(response) {
				//alert("error: " + response);
				return true;
			}
		});
	}

	jQuery(window).scroll(function() {
		myHeight = getWindowHeight();
		var interval = Math.floor((myHeight + jQuery(window).scrollTop()) / scrollInterval);
		if (interval > maxInterval) {
			maxInterval = interval;
			if (scrollTimeout) clearTimeout(scrollTimeout);
			scrollTimeout = setTimeout(postScroll, 1000);
		}
	});

	jQuery('body').live('click', function(evt) {
		d = new Date();
		timeToClick = Math.round(d.getTime()/1000) - timeStart;
		timeStart = Math.round(d.getTime()/1000);

		// Get absolute coordinates
		//if (!evt) evt = window.event; // javascript examples use this, different for jQuery?
		if (evt.pageX || evt.pageY) {
			absPosX = evt.pageX;
			absPosY = evt.pageY;
		} else if (evt.clientX || evt.clientY) {
			absPosX = evt.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
			absPosY = evt.clientY + document.body.scrollTop + document.documentElement.scrollTop;
		}

		// Get relative coordinates
		// The offset() method allows us to retrieve the current position of an element relative to the document.
		jQueryTarget = jQuery(evt.target);
		posX = evt.pageX - jQueryTarget.offset().left;
		posY = evt.pageY - jQueryTarget.offset().top;
		
		reference = '';
		var currNode = jQuery(evt.target);

		// begin adapted code from http://stackoverflow.com/questions/2068272/getting-a-jquery-selector-for-an-element/, author http://stackoverflow.com/users/119081/blixt
		while (currNode.length) {
			var node = currNode[0];
			// IE9 and other browsers use localName, IE8 and earlier use tagName or nodeName
			var nodeName = 	node.localName || node.tagName || node.nodeName;

			// don't need this as part of the reference, special case for IE
			if (!nodeName || nodeName == '#document') break;

			nodeName = nodeName.toLowerCase();
			if (node.id) {
				// An id should be unique so no need to loop anymore
				reference = nodeName + '#' + node.id + (reference ? '>' + reference : '');
				break;
			} else if (node.className) {
				nodeName += '.' + node.className.replace(/\s+/g, ".");
			}

			var parent = currNode.parent();
			var siblings = parent.children(nodeName);
			if (siblings.length > 1) { 
				nodeName += ':eq(' + siblings.index(node) + ')';
			}

			reference = nodeName + (reference ? '>' + reference : '');
			currNode = parent;
		}
		// end adapted code
		
		/*window.prompt("new", reference); alert(jQuery(reference).length); // testing */
		if (reference == '') return true;

		if (dataId == -1) {
			jQuery.ajax({
				type: "POST",
				url: MyAjax.ajaxurl,
				data: {
					action      : 'heat_trackr_add_click',
					url         : document.URL,
					referrer    : document.referrer,
					reference   : reference,
					absPosX     : absPosX,
					absPosY     : absPosY,
					posX        : posX,
					posY        : posY,
					timeToClick : timeToClick,
					windowWidth : screen.width,
					dataId      : dataId
				},
				timeout: ajaxTimeout,
				success: function(response) {
					//alert("success: " + response);
					if (intRegex.test(response)) { // only accept the response if it's the new table entry id (integer value)
						dataId = response;
					}
					return true;
				},
				error: function(response) {
					//alert("error: " + response);
					return true;
				}
			});
		} else {
			jQuery.ajax({
				type: "POST",
				url: MyAjax.ajaxurl,
				data: {
					action      : 'heat_trackr_add_click',
					url         : document.URL,
					reference   : reference,
					absPosX     : absPosX,
					absPosY     : absPosY,
					posX        : posX,
					posY        : posY,
					timeToClick : timeToClick,
					windowWidth : screen.width,
					dataId      : dataId
				},
				timeout: ajaxTimeout,
			});
		}
	});
});

function getWindowHeight() {
	if (typeof(window.innerWidth) == 'number') {
		//Non-IE
		myHeight = window.innerHeight;
	} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		//IE 6+ in 'standards compliant mode'
		myHeight = document.documentElement.clientHeight;
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		//IE 4 compatible
		myHeight = document.body.clientHeight;
	}

	return myHeight;
}

function postScroll() {
	jQuery.ajax({
		type: "POST",
		url: MyAjax.ajaxurl,
		data: {
			action    : 'heat_trackr_add_scroll',
			url       : document.URL,
			height    : maxInterval * scrollInterval,
			count     : 1,
			scrollId  : scrollId
		},
		timeout: ajaxTimeout
	});
}

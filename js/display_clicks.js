// Keep all these variables global to save having to refetch the data
var currentMapType = 0;       // map type currently selected
var rawSpots = [];            // initial data set of clicks that comes back from the database
var imageHTML = '';           // name of the heatmap image generated (mapType = 1)
var summarySpots = [];        // set of summary spots for the clickmap (mapType = 2, summary checked)
var clickSpots = [];          // set of individual spots for the clickmap (mapType = 2)
var breakSpots = [];          // set of spots for the breakmap (mapType = 3)
var pieData = [];             // pie chart data
var lastPieCriteria = '';     // used to check if pie chart should be removed or redrawn

var defCriteria = 'Referrer'; // default criteria for scattermap
var lastCriteria = '';        // last chosen criteria for scattermap
var criteriaArray = [];       // array of criteria to select from
var idPrefix = 'hm_';         // try and avoid potential id conflicts with other html elements

var lastCircle = null;        // last chosen circle statistics

var dotSize = 12;             // dot size for scattermap, clickmap dot can be different (see heat-trackr.php)
var halfSize = dotSize / 2;   // half of dot size

var maxValues = 15;           // maximum number of different criteria to be shown

jQuery(document).ready(function() {
	jQuery("#heatswitch").bind("ajaxSend", function() {
		if (currentMapType == 1) jQuery(this).show();
	}).bind("ajaxStop", function() {
		if (currentMapType == 1) jQuery(this).hide();
	}).bind("ajaxError", function() {
		if (currentMapType == 1) jQuery(this).hide();
	});

	jQuery("#clickswitch").bind("ajaxSend", function() {
		if (currentMapType == 2) jQuery(this).show();
	}).bind("ajaxStop", function() {
		if (currentMapType == 2) jQuery(this).hide();
	}).bind("ajaxError", function() {
		if (currentMapType == 2) jQuery(this).hide();
	});

	jQuery("#breakswitch").bind("ajaxSend", function() {
		if (currentMapType == 3) jQuery(this).show();
	}).bind("ajaxStop", function() {
		if (currentMapType == 3) jQuery(this).hide();
	}).bind("ajaxError", function() {
		if (currentMapType == 3) jQuery(this).hide();
	});

	jQuery("#scrollswitch").bind("ajaxSend", function() {
		if (currentMapType == 4) jQuery(this).show();
	}).bind("ajaxStop", function() {
		if (currentMapType == 4) jQuery(this).hide();
	}).bind("ajaxError", function() {
		if (currentMapType == 4) jQuery(this).hide();
	});

	jQuery('.ht-heatmap-on').live('click', function() {
		jQuery('.ht-image').remove();
		
		jQuery('.ht-heatmap-on').removeClass('ht-heatmap-on').addClass('ht-heatmap-off');
		jQuery('.ht-heatmap-off span').addClass('heatmap-bar-span').html('Heatmap On');
		jQuery('.ht-clickmap-off').show();
		jQuery('.ht-breakmap-off').show();
		jQuery('.ht-scrollmap-off').show();
	});

	jQuery('.ht-heatmap-off').live('click', function(evt) {
		if (jQuery('.ht-clickmap-on').length || jQuery('.ht-breakmap-on').length || jQuery('.ht-scrollmap-on').length) {
			alert("Cannot generate a heatmap when another map is on, turn current map off first");
			return false;
		}
		jQuery('.ht-heatmap-off').removeClass('ht-heatmap-off').addClass('ht-heatmap-on');
		jQuery('.ht-heatmap-on span').addClass('heatmap-bar-span').html('Heatmap Off');

		postAndReceiveClicks(1);
		jQuery('.ht-clickmap-off').hide();
		jQuery('.ht-breakmap-off').hide();
		jQuery('.ht-scrollmap-off').hide();
		return false;
	});

	jQuery('.ht-clickmap-on').live('click', function() {
		jQuery('.ht-spots').remove();
		jQuery('.ht-overlay').remove();

		jQuery('.ht-clickmap-on').removeClass('ht-clickmap-on').addClass('ht-clickmap-off');
		jQuery('.ht-clickmap-off span').addClass('clickmap-bar-span').html('Clickmap On');
		jQuery('.ht-heatmap-off').show();
		jQuery('.ht-breakmap-off').show();
		jQuery('.ht-scrollmap-off').show();

		if (jQuery("#ht-data-chooser-dialog").dialog("isOpen") === true) {
			jQuery("#ht-data-chooser-dialog").remove();
		}
	});

	jQuery('.ht-clickmap-off').live('click', function(evt) {
		if (jQuery('.ht-heatmap-on').length || jQuery('.ht-breakmap-on').length || jQuery('.ht-scrollmap-on').length) {
			alert("Cannot generate a clickmap when another map is on, turn current map off first");
			return false;
		}
		jQuery('.ht-clickmap-off').removeClass('ht-clickmap-off').addClass('ht-clickmap-on');
		jQuery('.ht-clickmap-on span').addClass('clickmap-bar-span').html('Clickmap Off');

		postAndReceiveClicks(2);
		jQuery('.ht-heatmap-off').hide();
		jQuery('.ht-breakmap-off').hide();
		jQuery('.ht-scrollmap-off').hide();
		return false;
	});

	jQuery('.ht-breakmap-on').live('click', function() {
		jQuery('.ht-spots').remove();
		jQuery('.ht-overlay').remove();
		jQuery('#piegraph').remove();
		jQuery('body').removeClass('ht-breakmap-piechart');
		lastPieCriteria = '';

		jQuery('.ht-breakmap-on').removeClass('ht-breakmap-on').addClass('ht-breakmap-off');
		jQuery('.ht-breakmap-off span').addClass('breakmap-bar-span').html('Scattermap On');
		jQuery('.ht-heatmap-off').show();
		jQuery('.ht-clickmap-off').show();
		jQuery('.ht-scrollmap-off').show();
		
		if (jQuery("#ht-data-chooser-dialog").dialog("isOpen") === true) {
			jQuery("#ht-data-chooser-dialog").remove();
		}
	});

	jQuery('.ht-breakmap-off').live('click', function(evt) {
		if (jQuery('.ht-heatmap-on').length || jQuery('.ht-clickmap-on').length || jQuery('.ht-scrollmap-on').length) {
			alert("Cannot generate a breakmap when another map is on, turn current map off first");
			return false;
		}
		jQuery('.ht-breakmap-off').removeClass('ht-breakmap-off').addClass('ht-breakmap-on');
		jQuery('.ht-breakmap-on span').addClass('breakmap-bar-span').html('Scattermap Off');

		postAndReceiveClicks(3);
		jQuery('.ht-heatmap-off').hide();
		jQuery('.ht-clickmap-off').hide();
		jQuery('.ht-scrollmap-off').hide();
		return false;
	});
	
	jQuery('.ht-scrollmap-on').live('click', function() {
		jQuery('.ht-spots').remove();
		jQuery('.ht-overlay').remove();

		jQuery('.ht-scrollmap-on').removeClass('ht-scrollmap-on').addClass('ht-scrollmap-off');
		jQuery('.ht-scrollmap-off span').addClass('ht-scrollmap-bar-span').html('Scrollmap On');
		jQuery('.ht-heatmap-off').show();
		jQuery('.ht-clickmap-off').show();
		jQuery('.ht-breakmap-off').show();
	});

	jQuery('.ht-scrollmap-off').live('click', function(evt) {
		if (jQuery('.ht-heatmap-on').length || jQuery('.ht-clickmap-on').length || jQuery('.ht-breakmap-on').length) {
			alert("Cannot generate a scrollmap when another map is on, turn current map off first");
			return false;
		}
		jQuery('.ht-scrollmap-off').removeClass('ht-scrollmap-off').addClass('ht-scrollmap-on');
		jQuery('.ht-scrollmap-on span').addClass('ht-scrollmap-bar-span').html('Scrollmap Off');

		postAndReceiveScrolls();
		jQuery('.ht-heatmap-off').hide();
		jQuery('.ht-clickmap-off').hide();
		jQuery('.ht-breakmap-off').hide();
		return false;
	});

	jQuery('#criteria-choices').live('change', function(evt) {
		var criteria = jQuery("#criteria-choices").val();
		if (currentMapType == 2) {
			setupCriteria(criteria);
			setupCriteriaChooser(criteriaArray.length, criteriaArray, 1);
		} else {
			setupBreakSpots(criteria);
		}
		return false;
	});

	jQuery('.criteria-checkbox').live('click', function(evt) {
		if (this.checked == true) {
			showCircleByIdAndClass(this.id, 'ht-smallcircle');
			this.prop('checked', true);
		} else {
			hideCircleByIdAndClass(this.id, 'ht-smallcircle');
			this.prop('checked', false);
		}

		event.stopPropagation();
		return false;
	});
});

function postAndReceiveClicks(mapType) {
	currentMapType = mapType;
	if (mapType == 1 && imageHTML != '') {
		jQuery('body').append(imageHTML);
		return;
	}
	/* Uncomment if summary becomes only option, otherwise rawSpots and clickSpots may be wrong if user changes settings
	if (mapType == 2 && clickSpots.length > 0) {
		rawSpots = summarySpots; // reset to previous values
		displayClicks();
		return;
	}*/
	if (mapType == 3 && breakSpots.length > 0) {
		rawSpots = breakSpots; // reset to previous values
		setupBreakSpots(lastCriteria);
		return;
	}
	
	jQuery.post(
		MyAjax.ajaxurl,
		{
			action  : 'heat_trackr_get_clicks',
			url     : document.URL,
			mapType : mapType
		},
		function (response) {
			//alert(response); // for debugging
			rawSpots = jQuery.parseJSON(response);
			len = rawSpots.length;

			for (var i=0; i<len; i++) {
				var value = rawSpots[i];
				posX = parseInt(value.posX, 10);
				posY = parseInt(value.posY, 10);
				reference = value.reference;
				
				if (jQuery(reference).length != 0) {
					adjPosX = posX + parseInt( jQuery(reference).offset().left, 10 );
					adjPosY = posY + parseInt( jQuery(reference).offset().top, 10 );
					
					value.posX = adjPosX;
					value.posY = adjPosY;
				} else {
					// Use absolute values instead
					value.posX = parseInt(value.absPosX, 10);
					value.posY = parseInt(value.absPosY, 10);
					//rawSpots.splice(i, 1); // Delete the spot
				}
			}

			var myWidth;
			var myHeight;

			if (typeof(window.innerWidth) == 'number') { 
				//Non-IE
				myWidth = window.innerWidth;
				myHeight = window.innerHeight; 
			} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
				//IE 6+ in 'standards compliant mode'
				myWidth = document.documentElement.clientWidth; 
				myHeight = document.documentElement.clientHeight; 
			} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
				//IE 4 compatible
				myWidth = document.body.clientWidth; 
				myHeight = document.body.clientHeight; 
			}

			if (mapType == 3) {
				breakSpots = rawSpots; // save for reuse
				if (lastCriteria == '') {
					lastCriteria = defCriteria;
				}
				if (rawSpots.length == 0) {
					alert("No data yet");
					return;
				}
				setupBreakSpots(lastCriteria);
			} else {
				if (rawSpots.length == 0) {
					alert("No data yet");
					return;
				}
				send_spots = JSON.stringify(rawSpots);
				jQuery.post(
					MyAjax.ajaxurl,
					{
						action      : 'heat_trackr_display_heatmap',
						mapType     : mapType,
						innerWidth  : myWidth,
						innerHeight : myHeight,
						send_spots  : send_spots
					},
					function (response) {
						//alert(response); // for debugging
						if (mapType == 1) {
							imageHTML = '<img class="ht-image" src="' + response + '" style="position: absolute; top: 0px; left: 0px; z-index:10000; -moz-opacity: 0.7; opacity: 0.7; filter: alpha(opacity=70)">';
							jQuery('body').append(imageHTML);
						} else if (mapType == 2) {
							rawSpots = jQuery.parseJSON(response);
							summarySpots = rawSpots; // save for reuse
							len = rawSpots.length;
							clickSpots = [];
							for (var i=0; i<len; i++) {
								clickSpots[i] = createAndDisplaySummarySpot(i, rawSpots[i].color, rawSpots[i].x, rawSpots[i].y, rawSpots[i].zindex, rawSpots[i].count);
							}
							displayClicks();
						}
						"html"
					}
				);
			}
		}
	);
}

function displayClicks() {
	jQuery('body').append('<div class="ht-overlay"></div>');
	jQuery('body').append('<div class="ht-spots"></div>');
	len = clickSpots.length;
	for (var i=0; i<len; i++) {
		jQuery('.ht-spots').append(clickSpots[i]);
	}
}

function revealDetails(circle) {
	if (circle == lastCircle)
	{
		if (jQuery("#ht-data-chooser-dialog").dialog("isOpen") === true) {
			jQuery("#ht-data-chooser-dialog").remove();
		}
		lastCircle = null;
		return;
	}

	lastCircle = circle;
	summarySpotsIndex = circle.id.substr(idPrefix.length);
	jQuery.post(
		MyAjax.ajaxurl,
		{
			action   : 'heat_trackr_get_summary',
			url      : document.URL,
			idValues : JSON.stringify(summarySpots[summarySpotsIndex].idValues)
		},
		function (response) {
			rawSpots = jQuery.parseJSON(response);
			setupCriteria(lastCriteria);
			setupCriteriaChooser(criteriaArray.length, criteriaArray, 1);
		}
	);
}

function hideDetails(circle) {
}

function postAndReceiveScrolls() {
	currentMapType = 4;
	if (typeof(window.innerWidth) == 'number') { 
		//Non-IE 
		myWidth = window.innerWidth;
		myHeight = window.innerHeight; 
	} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		//IE 6+ in 'standards compliant mode' 
		myWidth = document.documentElement.clientWidth; 
		myHeight = document.documentElement.clientHeight; 
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		//IE 4 compatible 
		myWidth = document.body.clientWidth; 
		myHeight = document.body.clientHeight; 
	}

	jQuery.post(
		MyAjax.ajaxurl,
		{
			action     : 'heat_trackr_get_scrolls',
			url        : document.URL,
			innerWidth : myWidth
		},
		function (response) {
			if (response == "") {
				alert("No data yet");
				return;
			}
			jQuery('body').append('<div class="ht-overlay"></div>');
			jQuery('body').append('<div class="ht-spots"></div>');
			jQuery('.ht-spots').append(response);
		}
	);
}

function setupCriteria(criteria) {
	if (criteria == '') {
		criteria = defCriteria;
	}
	//if (criteria == lastCriteria) return; // already done this criteria
	lastCriteria = criteria;

	criteriaArray = [];
	len = rawSpots.length;

	// Get all the different values for this criteria, use separate functions to avoid extra looping
	if (criteria == "Return Visitor") {
		criteriaArray = visitorValues(criteriaArray, len);
	} else if (criteria == "Referrer") {
		criteriaArray = referrerValues(criteriaArray, len);
	} else if (criteria == "Search Terms") {
		criteriaArray = searchTermsValues(criteriaArray, len);
	} else if (criteria == "Search Engine") {
		criteriaArray = searchEngineValues(criteriaArray, len);
	} else if (criteria == "Country") {
		criteriaArray = countryValues(criteriaArray, len);
	} else if (criteria == "Os") {
		criteriaArray = osValues(criteriaArray, len);
	} else if (criteria == "Browser") {
		criteriaArray = browserValues(criteriaArray, len);
	} else if (criteria == "Day Of Week") {
		criteriaArray = dayOfWeekValues(criteriaArray, len);
	} else if (criteria == "Time Of Day") {
		criteriaArray = timeOfDayValues(criteriaArray, len);
	} else if (criteria == "Time To Click") {
		criteriaArray = timeToClickValues(criteriaArray, len);
	} else if (criteria == "Window Width") {
		criteriaArray = windowWidthValues(criteriaArray, len);
	}

	// Sort by descending count order
	criteriaArray.sort( function (a,b) { return b[1] - a[1]; } );

	if (criteriaArray.length > maxValues) {
		setupOther(criteriaArray);
	}
}

function setupCriteriaChooser(len, criteriaArray, simple) {
	var chooser = '<select class="criteria-choices" id="criteria-choices">' +
		'<option value="Return Visitor">Return Visitor</option>' +
		'<option value="Referrer">Referrer</option>' +
		'<option value="Search Terms">Search Terms</option>' +
		'<option value="Search Engine">Search Engine</option>' +
		'<option value="Country">Country</option>' +
		'<option value="Os">Os</option>' +
		'<option value="Browser">Browser</option>' +
		'<option value="Day Of Week">Day Of Week</option>' +
		'<option value="Time Of Day">Time Of Day</option>' +
		'<option value="Time To Click">Time To Click</option>' +
		'<option value="Window Width">Window Width</option>' +
		'</select>';
	chooser = chooser.replace(lastCriteria + '">', lastCriteria + '" selected>');
	
	var criteriaSelectHTML = '<div id="ht-data-chooser-dialog" title="Data Chooser">' + chooser + '</div>';

	var criteriaChoicesHTML = '<table>';
	if (len > maxValues) len = maxValues;

	pieData = []; // pie chart data
	var criteriaDescription = '';
	for (var i=0; i<len; i++) {
		if (i == (maxValues-1) && criteriaArray.length > maxValues)
			criteriaDescription = 'Other';
		else
			criteriaDescription = criteriaArray[i][0];
			
		//bar = '<div class="ht-solidbar" style="width:' + Math.round(criteriaArray[i][1]/rawSpots.length * 100) + '%; background-color:#' + criteriaArray[i][2] + '"></div>';
		bar = '';

		if (simple == 1) {
			criteriaChoicesHTML += '<tr><td> ' + criteriaDescription + '</td><td> &nbsp;&nbsp; count=' + criteriaArray[i][1] + bar + '</td>' + '</tr>';
		} else {
			criteriaChoicesHTML += '<tr><td><input type="checkbox" checked="checked" class="criteria-checkbox" id="' + criteriaArray[i][2] + '"></td>' +
				'<td> ' + criteriaDescription + '</td><td> &nbsp;&nbsp;<font color="#' + criteriaArray[i][2] + '"> count=' + criteriaArray[i][1] + '</font>' + bar + '</td>' + '</tr>';
		}

		pieData[i] = { label: criteriaDescription, data: criteriaArray[i][1] }
	}

	if (simple == 1)
		criteriaChoicesHTML += '<tr><td align="right"><font color="red"><b>Total</b></font></td><td> &nbsp;&nbsp; ' + rawSpots.length + '</td></tr>';
	else
		criteriaChoicesHTML += '<tr><td></td><td align="right"><font color="red"><b>Total</b></font></td><td> &nbsp;&nbsp; ' + rawSpots.length + '</td></tr>';

	criteriaChoicesHTML += '</table>';
	if (jQuery("#ht-data-chooser-dialog").dialog("isOpen") === true) {
		jQuery("#ht-data-chooser-dialog > table").remove();
		jQuery("#ht-data-chooser-dialog").append(criteriaChoicesHTML);
		jQuery("#ht-data-chooser-dialog").show();
		jQuery("#ht-data-chooser-dialog").dialog( "option", "position", {my:'left top', at:'left top+80'} );
		if (jQuery('#piegraph').length == 1) {
			setupPieChart();
		}
		return;
	}

	jQuery(criteriaSelectHTML).dialog({
		height: 440, width: 320, modal: false, resizable: true, closeOnEscape: false,
		zIndex: 14000, position: {my:'left top', at:'left top+80'}, dialogClass: "ht-data-chooser-dialog", 
		buttons: [{ text: "Pie Chart", click: function() { setupPieChart(); } }]
		});
	jQuery("#ht-data-chooser-dialog").append(criteriaChoicesHTML);
	jQuery("#ht-data-chooser-dialog").css({ 'font-size': '12px', 'background-color': 'white', 'border': '3px solid #e65f44',
		'-moz-opacity': '0.8', 'opacity': '0.8', 'filter': 'alpha(opacity=80)' });
	//jQuery("#ht-data-chooser-dialog").parent().find('a.ui-dialog-titlebar-close').remove();
	jQuery("#ht-data-chooser-dialog").bind('dialogbeforeclose', false);	
}

function setupPieChart() {
	if (jQuery('#piegraph').length == 1) {
		jQuery('#piegraph').remove();
		jQuery('body').removeClass('ht-breakmap-piechart');

		if (lastCriteria == lastPieCriteria) {
			lastPieCriteria = '';
			return;
		}
	}
	
	lastPieCriteria = lastCriteria;
	len = pieData.length;

	jQuery('body').addClass('ht-breakmap-piechart');
	//jQuery('.ht-breakmap-piechart').append('<div id="piegraph" class="graph" style="position:absolute;z-index:13999;top:30px;left:0px;width:700px;height:540px;border:1px dashed gainsboro;"></div>');
	var placementStr = '<div id="piegraph" class="graph" style="position:absolute;z-index:13999;top:30px;left:0px;width:700px;height:540px;border:1px dashed gainsboro;"></div>';
	var screenTop = jQuery(document).scrollTop() + 30;
	placementStr = placementStr.replace('30px', screenTop + 'px');
	jQuery('.ht-breakmap-piechart').append(placementStr);
	jQuery.plot(jQuery('#piegraph'), pieData, 
	{
		series: {
			pie: { 
				show: true,
				radius: 1,
				label: {
					show: true,
					radius: 3/4,
					formatter: function(label, len){
						return '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">'+label+'<br/>'+Math.round(len.percent)+'%</div>';
					},
					background: { opacity: 0.5 },
					threshold: 0.05
				}
			}
		},
        grid: {
             clickable: true
        },
		legend: {
			show: true
		}
	});
	jQuery("#piegraph").bind("plotclick", pieClick);
}

function pieClick(event, pos, obj) 
{
	if (!obj) return;
	percent = parseFloat(obj.series.percent).toFixed(2);
	alert(''+obj.series.label+': '+percent+'%');
}

function setupBreakSpots(criteria) {
	setupCriteria(criteria);
	var count = criteriaArray.length;
	if (criteriaArray.length > maxValues) {
		count = maxValues;
	}
	
	jQuery('.ht-spots').remove();
	jQuery('.ht-overlay').remove();

	// Need 'count' number of colors use getColorArray($numColors) via Ajax
	jQuery.post(
		MyAjax.ajaxurl,
		{
			action : 'heat_trackr_get_colors',
			count  : count
		},
		function (response) {
			//alert(response); // for debugging
			heatArray = jQuery.parseJSON(response);
			
			// For criteriaArray with highest count assign hottest color
			zindex = 11000; // Make the number big enough so that the circles will stay on top of any other overlays
			len = criteriaArray.length;
			for (var i=0; i<len; i++) {
				if (i > (maxValues-1)) {
					criteriaArray[i][2] = heatArray[maxValues-1];
					criteriaArray[i][3] = zindex;
				} else {
					criteriaArray[i][2] = heatArray[i];
					criteriaArray[i][3] = zindex--;
				}
			}

			jQuery('body').append('<div class="ht-overlay"></div>');
			jQuery('body').append('<div class="ht-spots"></div>');
			setupCriteriaChooser(len, criteriaArray, 0);

			len = rawSpots.length;
			len2 = criteriaArray.length;

			// Set up the spot colors based on the different values for this criteria, use separate functions to avoid extra looping
			if (criteria == "Return Visitor") {
				visitorSpots(len, len2, criteriaArray);
			} else if (criteria == "Referrer") {
				referrerSpots(len, len2, criteriaArray);
			} else if (criteria == "Search Terms") {
				searchTermsSpots(len, len2, criteriaArray);
			} else if (criteria == "Search Engine") {
				searchEngineSpots(len, len2, criteriaArray);
			} else if (criteria == "Country") {
				countrySpots(len, len2, criteriaArray);
			} else if (criteria == "Os") {
				osSpots(len, len2, criteriaArray);
			} else if (criteria == "Browser") {
				browserSpots(len, len2, criteriaArray);
			} else if (criteria == "Day Of Week") {
				dayOfWeekSpots(len, len2, criteriaArray);
			} else if (criteria == "Time Of Day") {
				timeOfDaySpots(len, len2, criteriaArray);
			} else if (criteria == "Time To Click") {
				timeToClickSpots(len, len2, criteriaArray);
			} else if (criteria == "Window Width") {
				windowWidthSpots(len, len2, criteriaArray);
			}
		}
	);
}

function hideCircleByIdAndClass(id, className) {
	// example hideCircleByIdAndClass('ff0f0f', 'ht-smallcircle');
	jQuery('#' + idPrefix + id + "." + className).hide();
}

function showCircleByIdAndClass(id, className) {
	// example showCircleByIdAndClass('ff0f0f', 'ht-smallcircle');
	jQuery('#' + idPrefix + id + "." + className).show();
}

// Specialized function for sorting of multi dimensional array
Array.prototype.indexOf0 = function(a){for(i=0;i<this.length;i++)if(a==this[i][0])return i;return -1;};

function visitorValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].visitor);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].visitor,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function referrerValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].referrer);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].referrer,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function searchTermsValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].searchTerms);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].searchTerms,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function searchEngineValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].searchEngine);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].searchEngine,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function countryValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].country);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].country,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function osValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].os);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].os,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function browserValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].browser);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].browser,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function dayOfWeekValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].dayOfWeek);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].dayOfWeek,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function timeOfDayValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].timeOfDay);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].timeOfDay,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function timeToClickValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		//timeCategory = makeTimeCategory(rawSpots[i].timeToClick);
		index = criteriaArray.indexOf0(rawSpots[i].timeToClick);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].timeToClick,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function windowWidthValues(criteriaArray, len) {
	var index;
	var count = 0;

	// Get all the different values for this criteria
	for (var i=0; i<len; i++) {
		index = criteriaArray.indexOf0(rawSpots[i].windowWidth);
		if (index == -1) {
			criteriaArray[count] = [rawSpots[i].windowWidth,1];
			count++;
		} else {
			criteriaArray[index][1] += 1;
		}
	}

	return criteriaArray;
}

function setupOther(criteriaArray) {
	len = criteriaArray.length;

	var otherCount = 0;
	for (var i=maxValues-1; i<len; i++) {
		otherCount += criteriaArray[i][1];
	}
	criteriaArray[maxValues-1][1] = otherCount;
	//array.splice(maxValues, len-maxValues);

	return criteriaArray;
}

function visitorSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].visitor == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function referrerSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].referrer == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function searchTermsSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].searchTerms == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function searchEngineSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].searchEngine == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function countrySpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].country == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function osSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].os == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function browserSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].browser == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function dayOfWeekSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].dayOfWeek == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function timeOfDaySpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].timeOfDay == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function timeToClickSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].timeToClick == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function windowWidthSpots(len, len2, criteriaArray) {
	for (var i=0; i<len; i++) {
		for (var j=0; j<len2; j++) {
			if (rawSpots[i].windowWidth == criteriaArray[j][0]) {
				color = criteriaArray[j][2];
				zindex = criteriaArray[j][3];
				break;
			}
		}
		spot = createAndDisplaySpot(color, rawSpots[i].posX, rawSpots[i].posY, zindex);
	}
}

function createAndDisplaySpot(color, x, y, zindex) {
	x = x - halfSize + "px";
	y = y - halfSize + "px";
	spot = "<div class='ht-smallcircle' id='" + idPrefix + color + "' style='left:" + x + "; top:" + y + "; z-index: " + zindex + "; background-color:#" + color + "'></div>";
	jQuery('.ht-spots').append(spot);
	return spot;
}

function createAndDisplaySummarySpot(i, color, x, y, zindex, count) {
	spot = "<div class='ht-circle' id='" + idPrefix + i + "' style='left:" + x + "px; top:" + y + "px; z-index:" + zindex +
		"; background-color:#" + color + ";' onClick='revealDetails(this)' onmouseout='hideDetails(this)'>" + count + "</div>";
	return spot;
}

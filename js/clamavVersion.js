/**
 * @file plugins/generic/clamav/js/clamavVersion.js
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @brief Attach some handlers to the two input boxes and use them to make
 * some AJAX calls back to the plugin to enable version detection. The goal is
 * to give users some immediate feedback on whether the path they've provided
 * is valid or not.
 */
 (function($) {
	
	clamScan = {
		thisName: "clamScan",
		inputId: $('[id*="clamavPath-"]').attr('id'),
		type: "executable",
		versionDisplayId: "clamExecutableVersion",
		lastCheckedValue: "",
		container: "clamavExecutableArea",
	};
	
	clamDaemon = {
		thisName: "clamDaemon",
		inputId: $('[id*="clamavSocketPath-"').attr('id'),
		type: "socket",
		versionDisplayId: "clamSocketVersion",
		lastCheckedValue: "",
		container: "clamdSocketArea",
	};
	
	//	bind an event handler to the input event on each of our input elements
	$("#"+clamScan['inputId']).on('input', function() {
		clamScan['lastCheckedValue'] = $(this).val();
		setTimeout(conditionalVersionTest.bind(null, $(this), $(this).val(), clamScan), 500);
	});
	$("#"+clamDaemon['inputId']).on('input', function() {
		clamDaemon['lastCheckedValue'] = $(this).val();
		setTimeout(conditionalVersionTest.bind(null, $(this), $(this).val(), clamDaemon), 500);
	});
	
	// Rebuild clam AV version information
	$("#"+clamScan['inputId']).trigger("input");
	$("#"+clamDaemon['inputId']).trigger("input");
	
	/*
	 * This builds a css rule using a URL calculated by the backend PHP and
	 * appends it to the form to set a spinner as a background image. We're
	 * doing things this way because:
	 * 
	 * 1. we can't dynamically push a path into un-compiled CSS,
	 * 2. adding a css rule is better and less messy than directly appending a
	 *		style to an element and needing to remove it later, and
	 * 3. by appending it to the form element instead of the head element, it
	 *		cleans up after itself when the modal is closed.
	 */
	$(	'<style>\n' +
			'#clamscanSettingsFormArea #clamavExecutableArea.loading::before,' +
			' #clamdSettingsFormArea #clamdSocketArea.loading::before {\n' +
				'background-image: url(\"'+$("#clamavSettingsForm").data('loading-href')+'\");\n' +
			'}\n' +
			'#clamdSocketAdvancedSettings > div.pkp_helpers_quarter.inline::after {\n' +
				'content: "' + $("#clamavSettingsForm").data('timeout-units') + '";\n' +
			'}\n' +
		'</style>').appendTo('#clamavSettingsForm'); 

	/*
	 * We're using this to test and see if the field has been changed since the
	 * timeout was set. If not, we know our user has stopped typing.
	 */
	function conditionalVersionTest(element, lastValue, clamAv) {
		if(lastValue === element.val() && clamAv['lastCheckedValue'] === lastValue) {
			getClamVersion(element.val(), clamAv);
		}
	}
	
	/*
	 * Prepares a request for a version number and executes an AJAX call to
	 * set that version number in the appropriate place as determined by the
	 * clamAv object passed to the function.
	 * 
	 * In the event that another request has been made since then, this function
	 * will check the path it was given (upon completion of the request). If
	 * this path does not match the path of the most recent request (i.e. another
	 * request was made on the same path) then instead of updating the version
	 * information, we don't take any action--this lets the latest request be
	 * the one to remove the loading indicator and write the version.
	 */
	function getClamVersion(clamavPath, clamAv) {
		// no data would mean we have a problem
		if (!$("#clamavSettingsForm").data('json-href')) {
			return true;
		}
		
		// This builds the request URL
		var url = $("#clamavSettingsForm").data('ajax-href') + "/get?path=" + encodeURIComponent(clamavPath) + "&type=" + clamAv['type'];
		// Indicates to the user that we are starting to load data
		$("#"+clamAv['container']).removeClass("valid invalid").addClass("loading");

		$.ajax({url: url, dataType: 'json'})
			.done(function(r) {
				// did not find a version
				if(r.content == "") {
					// if this is the last request (i.e. we haven't made any
					// more recent requests that are still loading)
					if(clamAv['lastCheckedValue'] === clamavPath) {
						$("#"+clamAv['versionDisplayId']).html($("#clamavSettingsForm").data('not-found'));
						$("#"+clamAv['container']).removeClass("valid loading").addClass("invalid");
					}
				// found a version
				} else {
					// if this is the last request (i.e. we haven't made any
					// more recent requests that are still loading)
					if(clamAv['lastCheckedValue'] === clamavPath) {
						$("#"+clamAv['versionDisplayId']).html(r.content);
						$("#"+clamAv['container']).removeClass("invalid loading").addClass("valid");
					}
				}
			})
			.fail(function(r) {
				// if this is the last request (i.e. we haven't made any
				// more recent requests that are still loading)
				if(clamAv['lastCheckedValue'] === clamavPath) {
					$("#"+clamAv['versionDisplayId']).target.html($("#clamavSettingsForm").data('network-problem'));
					$("#"+clamAv['container']).removeClass("valid loading").addClass("invalid");
				}
			});
		
	}

 })(jQuery);

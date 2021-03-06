var referenceInit = function(){
	var config = {
		at: '$',
		data: [],
		show_the_at: true,
		limit: 10,
		callbacks: {
			remote_filter: function (query, callback) {
				if (query.length < 3)
					return;

				$.ajax({
					url: smf_scripturl + '?action=suggest;' + smf_sessvar + '=' + smf_sessid + ';xml',
					method: 'GET',
					data: {
						search: query,
						suggest_type: 'member'
					},
					success: function (data) {
						var members = $(data).find('smf > items > item');
						var callbackArray = [];
						$.each(members, function (index, item) {
							callbackArray[callbackArray.length] = {
								name: $(item).text()
							};
						});
						callback(callbackArray);
					}
				});
			}
		}
	};

	if (typeof $.fn.atwho == 'undefined' && typeof jQuery.fn.atwho != 'undefined'){
		var iframe = jQuery('#html_message');
		if (typeof iframe[0] != 'undefined')
			jQuery(iframe[0].contentDocument.body).atwho(config);
		jQuery('textarea[name=message]').atwho(config);
	} else {
		var iframe = $('#html_message');
		if (typeof iframe[0] != 'undefined')
			$(iframe[0].contentDocument.body).atwho(config);
		$('textarea[name=message]').atwho(config);
	}
};

var atWhoElementReferences = document.createElement('script');
atWhoElementReferences.src = atwho_url;
atWhoElementReferences.type = 'text/javascript';
atWhoElementReferences.onload = referenceInit;

if (typeof $ == 'undefined' || (parseInt($.fn.jquery.substr(0, 1)) == 1 && parseInt($.fn.jquery.substr(2, 3)) < 8) || jQuery.fn.jquery != $.fn.jquery){
	var scriptElement = document.createElement('script');
	scriptElement.src = jquery_url;
	scriptElement.type = 'text/javascript';

	scriptElement.onload = function () {
		document.body.appendChild(atWhoElementReferences);
	};

	document.body.appendChild(scriptElement);
} else {
	document.body.appendChild(atWhoElementReferences);
}
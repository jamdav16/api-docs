/*
api-docs
A template for creating API documentation, inspired by Stripe
Copyright (c)2015 Aaron Collegeman
MIT Licensed
*/
!function($) {

	if (document.location.hash) {
		$('#content').scrollTop( $(document.location.hash).offset().top );
	}

	function createWaypoints() {
		var context = $('#content').get(0);
		$('#content section[id]').each(function() {
			var id = $(this).attr('id');
			new Waypoint({
				'element': this,
				'handler': function(direction) {
					try {
						history.pushState(null, null, '#' + id);
					} catch (e) {
						window.console && console.error(e);
					}
				},
				'context': context
			});
		});
	};

	function destroyWaypoints() {
		Waypoint.destroyAll();
	};

	$('#sidebar a').click(function() {
		destroyWaypoints();
		setTimeout(function() {
			createWaypoints();
		}, 300);	
	});

	setTimeout(function() {
		createWaypoints();
	}, 1000);

}(jQuery);
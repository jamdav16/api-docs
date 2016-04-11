/*
api-docs
A template for creating API documentation, inspired by Stripe
Copyright (c)2015 Aaron Collegeman
MIT Licensed
*/
!function($) {

	var Router = Backbone.Router.extend({
		routes: {
			':id': 'go'
		},

		go: function(id) {
			console.log('go', id);
			/*
			setTimeout(function() {
				var top = $('#'+id).offset().top;
				if (top > 0) {
					$('#content').scrollTop(top);
				}
				initWaypoints();
			}, 0);
			*/
		}
	});

	$('#sidebar a').click(function() {
		router.navigate($(this).attr('href'), { trigger: true });
		return false;
	});

	var context = $('#content').get(0);

	var hasInitWaypoints = false;

	function initWaypoints() {
		if (!hasInitWaypoints) {
			hasInitWaypoints = true
			$('#content section[id]').each(function() {
				var id = $(this).attr('id');
				new Waypoint({
					'element': this,
					'handler': function(direction) {
						router.navigate(id, { trigger: true });
					},
					'context': context
				});
			});
		}
	};

	var router = new Router();

	Backbone.history.start({
		pushState: false
	});

}(jQuery);
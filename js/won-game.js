$(document).ready(function(){
	$('form').submit(function(e){
		var submit = $(this).find('input[type="submit"]');
		$(submit).fadeOut('fast').delay(3000).fadeIn('fast');
	});
	(function gameRefresh() {
		setTimeout(function() {
			$.ajax({
				url: "index.php?page=ajax&type=1",
				cache: false
			}).done(function() {
				gameRefresh();
			});
		}, 170000);
	})();
});
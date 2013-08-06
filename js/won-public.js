$(document).ready(function(){
	$('a.tooltip').each(function(i){
		var tooltip = '<span>'+$(this).attr('title')+'</span>';
		$(this).append(tooltip);
	});
	$('form').submit(function(e){
		var submit = $(this).find('input[type="submit"]');
		$(submit).fadeOut('fast').delay(3000).fadeIn('fast');
	});
});
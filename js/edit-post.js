jQuery(document).ready(function($){

	$( '#cahnrs-category-tabs span' ).on( 'click', function(e) {
		$(this).parent( 'li' ).toggleClass( 'open' ).children( 'ul' ).toggle();
	});

});
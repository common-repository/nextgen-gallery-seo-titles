jQuery(function() {
	jQuery(".sortable").sortable({
		placeholder: 'placeholder',
		update: function(event, ui) {
			jQuery('#pagetitle_order').val( jQuery(this).sortable('toArray') );
		}
	});
	
	jQuery("#permalink-structure").sortable({
		items: 'li.sortable'
	});
});

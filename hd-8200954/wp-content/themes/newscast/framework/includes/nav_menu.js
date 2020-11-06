jQuery.noConflict();


jQuery(document).ready(function(){

	var $target = jQuery('#description-hide')
	
	if(($target).filter(':checked').length == 0)
	{
		$target.trigger('click');
	}
});

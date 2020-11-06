$(document).ready(function(){
	//$('.tags').tagsInput();

	$('#checkall:checkbox').change(function () { console.log('aa');
		if($(this).attr('checked'))
	        $('.itens input:checkbox').attr('checked',true);
	     else 
	        $('.itens  input:checkbox').attr('checked',false);
	    
    });
	
	$('.btn-client').live('click',function(){
		var data = $(this).parents('tr').find('input.data').val();
		var line = $(this).parents('tr');
		
		$.ajax({
			  type: "POST",
			  url: window.location,
			  cache: false,
			  data: { data: data, ajax : true },
			  success: function(data) {
				  alert(data);
				  line.find('td').css('background','#C3D8C4');
			  }
			});
	});
	
});
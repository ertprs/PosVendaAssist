

$().ready(function(){

$('.btn_excluir_modulo').each(function(){
		$(this).click(function(){
			var decisao = confirm('Deseja excluir esse módulo');
			if (decisao){
			$.ajax({
				type: 'GET',  
				url: 'informativo_modulo_consulta_ajax.php',  
				data: 'tipo=excluir&informativo_modulo=' + $(this).attr('id_informativo_modulo'),
				dataType: 'text/html',  
				success: function(excluir){
					if(excluir){
						alert('Módulo excluido com sucesso!');
						//faz o refresh para atualizar a pagina de consulta
						window.location.reload();
						}
						else{
							alert('Erro na exclusão');
						}
				}  
			})
		}
		
		});
	})
	


	$('.btn_editar_modulo').fancybox({
		'width'				: '100%',
		'height'			: '100%',
		'autoScale'			: false,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'type'				: 'iframe',
		'onStart' 			: function(){setTimeout('$.fancybox.showActivity();', 100);},
		'onComplete' 		: function(){},
		
	});
	
	$('.btn_novo_modulo').fancybox({
		'width'				: '100%',
		'height'			: '100%',
		'autoScale'			: false,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'type'				: 'iframe',
		'onStart' 			: function(){setTimeout('$.fancybox.showActivity();', 100);},
		'onComplete' 		: function(){},
		'onClosed'			: function(){window.location.reload();}
		
	});
	
	$('#btn_visualizar').fancybox({
		'width'				: '100%',
		'height'			: '100%',
		'autoScale'			: false,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'type'				: 'iframe',
		'onComplete' 		: function(){}
	});	
	
	
});
	
 
	
	
	
	
	
	
	
	
	
	
	

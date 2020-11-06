$().ready(function(){
	$(".btn_excluir_reportagem").each(function(){
		$(this).click(function(){
			var decisao = confirm("Deseja excluir essa reportagem");
			if (decisao){
			$.ajax({
				type: "GET",  
				url: "reportagem_consulta_ajax.php",  
				data: "tipo=excluir&reportagem=" + $(this).attr("id_reportagem"),
				dataType: "text/html",  
				success: function(excluir){
					if(excluir){
						alert("Reportagem excluido com sucesso!");
						//faz o refresh para atualizar a pagina de consulta
						window.location.reload();
						}
					else{
						alert("Erro na exclusão da reportagem");
					}
				}  
			})
		}
		
		});
	})
	
});

function gravar_informativo() {
	$("#btn_acao").val("gravar");
	$("#frm_informativo").submit();
	}
	
$().ready(function(){
	$(".btn_testar_informativo").each(function(){
		$(this).click(function(){
		do{
			var validaEmail = 0;
			var email = prompt("Digite um endereço de e-mail para fazer o teste.", "");
				if ((email.length != 0) && ((email.indexOf("@") < 1) || (email.indexOf('.') < 7))){
					alert ( "O e-mail " + email + " está incorreto!");	
				}
				else{
					$.ajax({
					type: "GET",  
					url: "informativo_enviar.php",  
					data: "informativo=" + $(this).attr("id_informativo")+"&ajax=1&destinatario=" + email,
					dataType: "text/html",  
					success: function(testar){
						if(testar=="ok") 
							alert("Teste enviado com sucesso!");
						else
							alert("Informativo publicado com sucesso!");
						}  
					})
					validaEmail = 1;
				}
		}while(validaEmail < 1);
			
		});
	})
		
	$(".btn_publicar_informativo").each(function(){
		$(this).click(function(){
		var decisao = confirm("Publicar este informativo?");
		if (decisao){
			$.ajax({
				type: "GET",  
				url: "informativo_ajax.php",  
				data: "tipo=publicar&informativo=" + $(this).attr("id_informativo"),
				dataType: "text/html",  
				success: function(publicar){
					if(publicar==0)
						alert("Informativo já publicado!");
					else{
					parts = publicar.split("|");
					$("#publicar" + parts[0]).html(parts[1]);
						alert("Informativo publicado com sucesso!");
						}	
					}  
				})
				
			}
			
		});
	})
	
	$(".btn_desativar_informativo").each(function(){
		$(this).click(function(){
		var decisao = confirm("Desativar este informativo?");
		if (decisao){
			$.ajax({
				type: "GET",  
				url: "informativo_ajax.php",  
				data: "tipo=desativar&informativo=" + $(this).attr("id_informativo"),
				dataType: "text/html",  
				success: function(desativar){
				if(desativar){
					parts = desativar.split("|");
					$("#publicar" + parts[0]).html(parts[1]);
					alert("Informativo desativado com sucesso!");
					}
					
				else {
					alert("Erro ao desativar informativo!");
						}
					}  
				})		
			}
			
		});
	})
	
	$(".btn_enviar_informativo").each(function(){
		$(this).click(function(){
		var decisao = confirm("Deseja enviar este informativo para todos os destinatários cadastrados, postos e admins?");
		if (decisao){
		var id_informativo = $(this).attr("id_informativo");
		window.open("informativo_enviar.php?informativo="+id_informativo);
			/* codigo que recebera a confirmação de envio
			$.ajax({
				type: "GET",  
				url: "informativo_ajax.php",  
				data: "tipo=enviar&informativo=" + $(this).attr("id_informativo"),
				dataType: "text/html",  
				success: function(enviar){
				if(enviar==1){
					alert("Falha na atualização do envio !");
				}
				else{
					parts = enviar.split("|");
					$("#enviar" + parts[0]).html(parts[1]);
					$("#admin_enviar" + parts[0]).html(parts[2]);
						}
					}  
				})
				*/
			}
		});
	})
	
	$(".btn_excluir_informativo").each(function(){
		$(this).click(function(){
			var informativo = $(this).attr("id_informativo");
			if (confirm("Deseja excluir esse informativo")){
				$.ajax({
					type: "GET",  
					url: "informativo_ajax.php",  
					data: "tipo=excluir&informativo=" + $(this).attr("id_informativo"),
					dataType: "text/html",  
					success: function(excluir){
							if(excluir>0){
							$('#table_' + informativo).css('display', 'none');
							alert("Registro excluído com sucesso!");
							//faz o refresh para atualizar a pagina de consulta
							//window.location.reload();
							}
							else{
								alert("Erro na exclusão!");
							}
						
					}
				})
			}		
		});
	})
	
});
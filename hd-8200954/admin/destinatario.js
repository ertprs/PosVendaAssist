function pesquisar_destinatario() {
	$("#btn_pesquisar").val("pesquisar");
	$("#frm_destinatario").submit();
	}
	
function gravar_destinatario() {
	$("#btn_acao").val("gravar");
	$("#frm_destinatario").submit();
	}
	
$().ready(function(){
	
	$(".btn_ativar_desativar_destinatario").each(function(){
		$(this).click(function(){
		var value = $(this).attr("value");
		if(value=="Desativar"){
			var decisao = confirm("Deseja desativar esse destinatário?");
			var situacao="f";
		}
		if(value=="Ativar"){
			var decisao = confirm("Deseja ativar esse destinatário?");
			var situacao="t";
		}
			if (decisao){
			$.ajax({
				type: "GET",  
				url: "destinatario_ajax.php",  
				data: "tipo=atualizar&situacao="+ situacao +"&destinatario=" + $(this).attr("id_ativar_desativar_destinatario"),
				dataType: "text/html",  
				success: function(situacao){
				parts = situacao.split("|");
				$("#situacao" + parts[0]).html(parts[1]);
				$("#btn_ativar_desativar_destinatario" + parts[0]).val(parts[2]);
				alert("Situação atualiazada com sucesso!");
						
					}  
				})
			}
		
		});
	})
	
});

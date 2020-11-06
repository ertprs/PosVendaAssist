function imagens_adicionar() {
	$.ajax({
		type: "GET",
		url: "reportagem_ajax.php",
		data: "tipo=adicionar_foto&reportagem=" + $("#reportagem").val(),
		dataType: "text/html",
		success: function(result){
			parts = result.split('|');
			switch(parts[0]) {
				case "ok":
					var sequencia = parseInt($("#n_imagens").val());
					var item = '';
					
					item += $('<div>').append($("#div_reportagem_foto__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
					item += $('<div>').append($("#div_posicao__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
					item += $('<div>').append($("#div_legenda__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
					item += $('<div>').append($("#div_imagem__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
					
					$("#itens_imagens").append(item);
					$("#reportagem_foto"+sequencia).val(parts[1]);
					ajax_upload_ativar();
					$("#n_imagens").val(sequencia+1);
				break;
				
				case "falha":
					alert('Falha ao incluir foto: '+parts[1]);
				break;
			}
		}
	});
}

function ajax_upload_ativar() {
	$(".ajax_upload").each(function(){
		var id_campo = $(this).attr("id");
		var seq = $(this).attr("seq");

		new qq.FileUploader({
			element: document.getElementById(id_campo),
			action: 'reportagem_ajax.php',
			params: {
				tipo: 'ajax_upload',
				id: $("#reportagem_foto"+seq).val(),
				file_path: 'reportagem_imagens/',
				file_suffix: "imagem"
			},
			multiple: false,
			onSubmit: function(id, fileName){
				//Mostra progresso do upload
				$("#"+id_campo+" .qq-upload-list").css("display", "block");
			},
			onComplete: function(id, fileName, responseJSON){
				timestamp = new Date().getTime();
				$.ajax({
					type: "GET",
					url: "reportagem_ajax.php",
					data: "tipo=atualizar_imagem&campo=imagem&valor=" + responseJSON.file_path + responseJSON.file_name + '&reportagem_foto=' + $("#reportagem_foto"+seq).val(),
					dataType: "text/html",
					success: function(result){
						parts = result.split('|');
						switch(parts[0]) {
							case "ok":
								//Mostra miniatura
								$("#div_"+id_campo+" .div_ajax_upload_miniatura").css("background-image", "url(" + responseJSON.file_path + responseJSON.file_name + "?" + timestamp + ")");
								//Seta valor para o campo
								$("input[type='hidden'][id='"+id_campo+"']").val(responseJSON.file_path + responseJSON.file_name);
							break;
							
							case "falha":
								alert('Falha ao gravar no banco de dados: '+parts[1]);
								$("#div_"+id_campo+" .div_ajax_upload_miniatura").css("background-image", "none");
							break;
						}
						//Oculta progresso do upload
						$("#"+id_campo+" .qq-upload-list").css("display", "none");
					}
				});
			},
			allowedExtensions: ['jpg', 'png', 'gif'],
			messages: {
				typeError: "São permitidos apenas os seguintes tipos de arquivos: {extensions}",
				sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
				minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
				emptyError: "{file} is empty, please select files again without it.",
				onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
			},
			template: '<div class="qq-uploader">' +
			'<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' +
			'<div class="qq-upload-button"><input type="button" value="Selecionar arquivo..." style="width: 100%;" /></div>' +
			'<ul class="qq-upload-list"></ul>' +
			'</div>'
		});
		
		$("#limpar_imagem_" + id_campo).click(function() {
			var imagem = $("input[type='hidden'][id='"+id_campo+"']").val();
			
			if (imagem.length > 0) {
				$.ajax({
					type: "GET",
					url: "reportagem_ajax.php",
					data: "tipo=limpar_imagem&campo=imagem&valor=&reportagem_foto=" + $("#reportagem_foto"+seq).val(),
					dataType: "text/html",
					success: function(result){
						parts = result.split('|');
						switch(parts[0]) {
							case "ok":
								//Mostra miniatura
								$("#div_"+id_campo+" .div_ajax_upload_miniatura").css("background-image", "none");
								//Seta valor para o campo
								$("input[type='hidden'][id='"+id_campo+"']").val("");
							break;
							
							case "falha":
								alert('Falha ao gravar no banco de dados: '+parts[1]);
								$("#div_"+id_campo+" .div_ajax_upload_miniatura").css("background-image", "none");
							break;
						}
					}
				});
			}
		});
	});
}

$().ready(function () {
	$("#imagens_adicionar").click(function () {
		imagens_adicionar();
	});
	
	ajax_upload_ativar();
	
	CKEDITOR.replace( 'texto',
	{
		toolbar : [ ['Font', 'FontSize','TextColor', 'Bold','Italic','Underline','RemoveFormat'], ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'], ['NumberedList','BulletedList', 'Outdent','Indent'] ,['Link','Unlink'], [ 'Cut','Copy','Paste', 'PasteText','PasteFromWord'], ['Undo','Redo'], [ 'Find','Replace','-','SelectAll'], ['Maximize'] ]
	});

	$("#btn_gravar_reportagem").click(function() {
		$("#btn_acao").val("gravar");
		$("#frm_reportagem").submit();
	});
});
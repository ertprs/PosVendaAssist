function textos_adicionar() {
	var sequencia = parseInt($("#n_linhas_texto").val());
	var item = '';
	
	item += $('<div>').append($("#div_fonte__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_tamanho__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_cor__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_alinhamento__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_ordem__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_texto__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	
	$("#itens_os_corpo").append(item);
	
	$("#n_linhas_texto").val(sequencia+1);
}

$().ready(function(){
	
	if ($("#fancybox").val() == "1") {
		window.parent.$.fancybox.hideActivity();
	}

	$("#textos_adicionar_texto").click(function () {
		textos_adicionar();
	});

	$(".ajax_upload").each(function(){
		var id_campo = $(this).attr("id");

		new qq.FileUploader({
			element: document.getElementById(id_campo),
			action: 'informativo_modulo_ajax.php',
			params: {
				tipo: 'ajax_upload',
				id: $("#informativo_modulo").val(),
				file_path: 'informativo_imagens/',
				file_suffix: id_campo
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
					url: "informativo_modulo_ajax.php",
					data: "tipo=atualizar_campo&campo=" + id_campo + "&valor=" + responseJSON.file_path + responseJSON.file_name + '&informativo_modulo=' + $("#informativo_modulo").val(),
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
					url: "informativo_modulo_ajax.php",
					data: "tipo=limpar_imagem&campo=" + id_campo + "&valor=&informativo_modulo=" + $("#informativo_modulo").val(),
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
	
	$("#btn_gravar_informativo").click(function() {
		$("#btn_acao").val("gravar");
		$("#frm_informativo_modulo").submit();
	});
});     


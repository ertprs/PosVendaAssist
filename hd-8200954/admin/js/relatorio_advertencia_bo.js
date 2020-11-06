    function retorna_posto(retorno){
		$("#descricao_posto").val(retorno.nome);
		$("#codigo_posto").val(retorno.codigo);
	 }

	$(function() {

		Shadowbox.init();

		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});


	function verifyDates(reverse) {

		var date1_d = $("#data_inicial").datepicker('getDate');
		var date2_d = $("#data_final").datepicker('getDate');

		date1_d = new Date(date1_d);
		date2_d = new Date(date2_d);

		date1_d.setHours(0);
		date2_d.setHours(23);
		date2_d.setMinutes(59);
		date2_d.setSeconds(59);

		if((date1_d > date2_d || date1_d.toString() == "Invalid Date" || date2_d.toString() == "Invalid Date") && ($("#data_inicial").val() != "" && $("#data_final").val() != "")) {

			if(reverse) {
				$("#data_final").datepicker('setDate', $("#data_inicial").datepicker('getDate'));
			} else {
				$("#data_inicial").datepicker('setDate', $("#data_final").datepicker('getDate'));
			}
		}
	}

	$("#data_inicial").datepicker({
		onSelect: function() {
			verifyDates(true);
		},
		startDate:'01/01/2000'
	});

	$("#data_final").datepicker({
		onSelect: function() {
			verifyDates(false);
		},
		startDate:'01/01/2000'
	});

	$("#consultar").click(function() {
		if(($("#data_inicial").val().length > 0 && $("#data_final").val().length > 0)
			|| jQuery.trim($("#advertencia").val()) != "") {

			$("#data_inicial").removeAttr("required");
			$("#data_final").removeAttr("required");
		} else {
			   $("#data_inicial").attr("required", "true");
			   $("#data_final").attr("required", "true");
			}
	});

	$('form[name=consultar]').bind('submit', function(){

		$.post(
			"relatorio_advertencia_bo_ajax.php",
			{
				data_inicial: 	       $("#data_inicial").val(),
				data_final: 	       $("#data_final").val(),
				codigo_posto: 	       $("#codigo_posto").val(),
				tipo_ocorrencia:       $("#tipo_ocorrencia").val(),
				atendente: 		       $("#atendente").val(),
				statuss: 		       $("#statuss").val(),
				tipo_relatorio:	       $("input[name=tipo_relatorio]:checked").val(),
				advertencia: 	       $("#advertencia").val(),
				admin_sap: 		       $("#admin_sap").val(),
				posto_sap: 		       $("#posto_sap").val(),
				nivel_falha: 	       $("#nivel_falha").val(),
				tipo_falha: 	       $("#tipo_falha").val(),
				tratativa_atendimento: $("#tratativa_atendimento").val()

			},
			function(data) {
				$("#resultado").html(data);
				if(data == "false"){
					$("#erro_result").html("<div class='container'><div class='alert'><h4>Nenhum registro encontrado</h4></div></div>");
					$("#resultado").html("");
					$("#gerar_excel").hide();
					$("#tbl_result").hide();
					$("#legenda").hide();
				}else{
					$("#erro_result").html("");
					$("#resultado").html(data);
					$("#gerar_excel").show();
					$("#tbl_result").show();
					$("#legenda").show();
				}
			}
		);

		return false;
	});


	$("#admin_sap").change(function(){
		var admin_sap = $("#admin_sap").val();
		$.ajax({
			url:"relatorio_advertencia_bo_ajax.php",
			type:"POST",
			dataType:"JSON",
			data: {id:admin_sap, acao:"procura_posto"},
			complete: function(data){
				var resposta = data.responseText;
				$("#posto_sap").html(resposta);
			}
		});
	});

	$(document).on("click", "a[name=acao]", function() {

		var acao 	    = $(this).html();
		var advertencia = $(this).closest("tr").find("td[name=advertencia]").html();
		var finalizado  = !jQuery.trim($(this).closest("tr").find("td[name=data_fechamento]").html()) == "";
		var params 	    = "acao=" + acao + "&advertencia=" + advertencia + "&finalizado="+finalizado;

		Shadowbox.open({
			content:	"relatorio_advertencia_bo_acao.php?" + params,
			player:	    "iframe",
			title:		acao,
			width:	    800,
			height:	    500
		});
	});
});

	function simulaClick() {
		Shadowbox.close()
		$("#consultar").click()
	}

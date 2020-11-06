<style>
#callcenter_melibre {
	margin:5px 0;
}

#callcenter_melibre:hover {
	cursor:pointer;
}

#callcenter_melibre img {
    width: 45px;
    border: 0px;
}

#callcenter_melibre .rounder {
    background-color: #e82412;
    display:block;
    width:20px;
    height:20px;
    border-radius:100%;
    position:absolute;
    right:11px;
}

#callcenter_melibre .countml {
    position:absolute;
    right:14px;
    font-size:14px;
    font-family: "Arial", "sans-serif";
    font-weight:bold;
    color:#FFF;
}

#pendencia_atendimentos_ml_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_ml_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_ml_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#pendencia_atendimentos_ml_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#pendencia_atendimentos_ml_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#pendencia_atendimentos_ml_list table tr.loading {
    display: none;
}

#pendencia_atendimentos_ml_list table tr.loading th {
    background-color: #FFF;
}

#pendencia_atendimentos_ml_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#pendencia_atendimentos_ml_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#pendencia_atendimentos_ml_list h6 {
    margin-top: 5px;
    font-size: 11.9px !important;
}

.atendimento_alert_ml img {
    width: 12px;
    height: 12px;
}

.atendimento_link_ml a {
    text-decoration: none;
    color: #596D9B;
}

#pendencia_atendimentos_ml_list table tr:hover {
    background-color: #D0D0D0;
}

#pendencia_atendimentos_ml_list {
    display: none;
    text-align: center;
    width: 300px;
    max-height: 288px;
    border: 1px solid #000;
    border-radius: 8px;
    background-color: #FFF;
    z-index: 9999;
    padding: 5px;
    margin-left: -250px;
    margin-top: -23px;
    position: absolute;
}
</style>
<div id="callcenter_melibre">
	<span class="rounder"></span>
	<span class="countml"></span>
	<center><img src="imagens/botoes/ml.ico"/></center>
</div>
<div id="pendencia_atendimentos_ml_list">
	<span id="pendencia_atendimentos_ml_refresh" title="Atualizar lista de atendimentos pendentes" >
		<img src="imagens/icon/refresh.png" />
	</span>
	<span id="pendencia_atendimentos_ml_close" title="Fechar" >
		<img src="imagens/icon/close.png" />
	</span>
	<h6>Atendimentos abertos através do Mercado Livre</h6>
	<div style="width:100%;max-height:232px;overflow-y:auto;" >
		<table border="0">
			<tbody>
				<tr class="sem_pendencias_ml">
					<th colspan="3" >Chamados</th>
				</tr>
				<tr class="loading_ml">
					<th colspan="3" ><img src="imagens/loading_img.gif" /></th>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<script>
function atendimentosMlRefresh() {
	var list = $("#pendencia_atendimentos_ml_list").find("table > tbody");

	$.ajax({
		url: "ajax_pendencia_atendimentos_melibre.php",
		type: "POST",
		data: { atuliza_atendimentos: true },
		beforeSend: function () {
			$(list).find("tr.loading_ml").show();
			$(list).find("tr[class!=sem_pendencias_ml][class!=loading]").remove();
		},
		complete: function (data) {
			data = $.parseJSON(data.responseText);

			if (data.qtde > 9) {
				$("#callcenter_melibre .countml").text("9+");
			} else {
				$("#callcenter_melibre .countml").text(data.qtde);
				$("#callcenter_melibre .countml").css('right', '17px');
				$("#callcenter_melibre .countml").css('font-size', '15px');
			}

			$(list).append("<tr style='text-align:center'>\
				<td class='atendimento_link_ml'>HD</td>\
				<td>Abertura</td>\
			</tr>");

			if (data.qtde > 0) {
				$.each(data.atendimentos, function (key, atendimento) {
					$(list).append("<tr style='text-align:center'>\
						<td class='atendimento_link_ml'><a href='callcenter_interativo_new.php?callcenter=" + atendimento.atendimento + "' target='_blank' >" + atendimento.atendimento + "</a></td>\
						<td>" + atendimento.data + "</td>\
					</tr>");

				});
			}

			$(list).find("tr.loading_ml").hide();
		}
	});
}

$(function () {
	atendimentosMlRefresh();
	
	$("#callcenter_melibre").click(function () {
		$("#pendencia_atendimentos_ml_list").show();
		$("#menu_sidebar").css("z-index", "666");
	});

	$("html").on('click','#pendencia_atendimentos_ml_close', function () {        
		$("#pendencia_atendimentos_ml_list").hide();
		$("#menu_sidebar").css("z-index", "40000");
	});

	$("#pendencia_atendimentos_ml_refresh").click(function () {
		atendimentosMlRefresh();
	});
});
</script>
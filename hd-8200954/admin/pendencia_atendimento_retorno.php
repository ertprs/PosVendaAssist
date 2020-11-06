<style>
#pendencia_atendimentos_icon {
    text-align: center;
    background-image: url(imagens/pendencia_atendimento3.jpg);
    background-position: center;
    height: 45px;
    background-repeat: no-repeat;
    cursor: pointer;
}

#pendencia_atendimentos_icon {
    background-image: url(imagens/pendencia_atendimento_azul.jpg) !important;
}

#pendencia_atendimentos_icon > span {
    display: inline-block;
    font-weight: bold;
    color: #FFF;
    font-size: 14px;
    margin-top: 1px;
    margin-left: 28px;
}

#pendencia_atendimentos_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#pendencia_atendimentos_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#pendencia_atendimentos_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#pendencia_atendimentos_list table tr.loading {
    display: none;
}

#pendencia_atendimentos_list table tr.loading th {
    background-color: #FFF;
}

#pendencia_atendimentos_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#pendencia_atendimentos_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#pendencia_atendimentos_list h6 {
    margin-top: 5px;
    font-size: 11.9px !important;
}

.atendimento_alert img {
    width: 12px;
    height: 12px;
}

.atendimento_link a {
    text-decoration: none;
    color: #596D9B;
}

#pendencia_atendimentos_list table tr:hover {
    background-color: #D0D0D0;
}

#pendencia_atendimentos_list {
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
    position: relative;
}
</style>
<div id="pendencia_atendimentos_icon">
    <span></span><br>
</div> 
<div id="pendencia_atendimentos_list">
	<span id="pendencia_atendimentos_refresh" title="Atualizar lista de atendimentos para retorno" >
		<img src="imagens/icon/refresh.png" />
	</span>

	<span id="pendencia_atendimentos_close" title="Fechar" >
		<img src="imagens/icon/close.png" />
	</span>

	<h6>Atendimentos para retorno</h6>
	<div style="width: 100%; max-height: 232px; overflow-y: auto;" >
		<table border="0">
			<tbody>
				<tr class="sem_pendencias">
					<th colspan="3" class="sem_pendencias" >Sem retornos</th>
				</tr>
				<tr class="loading">
					<th colspan="3" ><img src="imagens/loading_img.gif" /></th>

				</tr>
			</tbody>
		</table>
	</div>
</div>


<script>
function pendenciaAtendimentosRefresh() {

	var list = $("#pendencia_atendimentos_list").find("table > tbody");

	$.ajax({
		url: "ajax_pendencia_atendimento_retorno.php",
		type: "POST",
		data: { atuliza_atendimentos: true },
		beforeSend: function () {
			$(list).find("tr.loading").show();
			$(list).find("tr[class!=sem_pendencias][class!=loading]").hide();
		},
		complete: function (data) {
			data = $.parseJSON(data.responseText);
			console.log(data);
			$("#pendencia_atendimentos_icon > span").text(data.qtde);	

			if (data.qtde > 9) {
				$("#pendencia_atendimentos_icon > span").css({ left: "41px" });
			} else {
				$("#pendencia_atendimentos_icon > span").css({ left: "43px" });
			}

			if (data.qtde > 0) {
				$.each(data.atendimentos, function (key, atendimento) {
					var data1 = [];

					data1 = atendimento.data_retorno.split("-");

					$(list).append("<tr>\
						<td class='atendimento_link'><a href='callcenter_interativo_new.php?callcenter="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
						<td>"+atendimento.data_retorno+"</td>\
					</tr>");
				});
			} else {
				$(list).find("tr.sem_pendencias").show();
			}

			$(list).find("tr.loading").hide();
		}
	});
}

$(function () {
	pendenciaAtendimentosRefresh();
	
	$("#pendencia_atendimentos_icon").click(function () {
		$("#pendencia_atendimentos_list").show();
		$("#menu_sidebar").css("z-index", "666");
	});

	$("#pendencia_atendimentos_close").click(function () {
		$("#pendencia_atendimentos_list").hide();
		$("#menu_sidebar").css("z-index", "40000");
	});

	$("#pendencia_atendimentos_refresh").click(function () {
		pendenciaAtendimentosRefresh();
	});
});

</script>

<style>
#pendencia_visitas_icon {
    text-align: center;
    background-image: url(imagens/pendencia_atendimento3.jpg);
    background-position: center;
    height: 45px;
    background-repeat: no-repeat;
    cursor: pointer;
}

#pendencia_visitas_icon {
    background-image: url(imagens/pendencia_atendimento3.jpg) !important;
}

#pendencia_visitas_icon > span {
    display: inline-block;
    font-weight: bold;
    color: #FFF;
    font-size: 14px;
    margin-top: 1px;
    margin-left: 28px;
}

#pendencia_visitas_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_visitas_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_visitas_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#pendencia_visitas_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#pendencia_visitas_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#pendencia_visitas_list table tr.loading {
    display: none;
}

#pendencia_visitas_list table tr.loading th {
    background-color: #FFF;
}

#pendencia_visitas_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#pendencia_visitas_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#pendencia_visitas_list h6 {
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

#pendencia_visitas_list table tr:hover {
    background-color: #D0D0D0;
}

#pendencia_visitas_list {
    display: none;
    text-align: center;
    width: 400px;
    max-height: 288px;
    border: 1px solid #000;
    border-radius: 8px;
    background-color: #FFF;
    z-index: 9999;
    padding: 5px;
    margin-left: -350px;
    margin-top: -23px;
    position: relative;
}
</style>

<div id="pendencia_visitas_icon">
	<span></span>
	<div id="pendencia_visitas_list">
		<span id="pendencia_visitas_refresh" title="Atualizar lista de visitas pendentes" >
			<img src="imagens/icon/refresh.png" />
		</span>

		<span id="pendencia_visitas_close" title="Fechar" >
			<img src="imagens/icon/close.png" />
		</span>

		<h6>Visitas pendentes</h6>

		<div style="width: 100%; max-height: 232px; overflow-y: auto;" >
			<table border="0">
				<tbody>
					<tr class="sem_pendencias">
						<th colspan="3" >Sem pendências</th>
					</tr>
					<tr class="loading">
						<th colspan="3" ><img src="imagens/loading_img.gif" /></th>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script type="text/javascript">
	function visitasRefresh () {
		var list = $("#pendencia_visitas_list").find("table > tbody");

		$.ajax({
			url: "ajax_pendencia_visitas.php",
			type: "POST",
			dataType:"JSON",
			data: { atuliza_visitas: true },
			beforeSend: function () {
				$(list).find("tr.loading").show();
				$(list).find("tr[class!=sem_pendencias][class!=loading]").remove();
			}
	    })
	    .done(function(data) {

	        $("#pendencia_visitas_icon > span").text(data.qtde);

	        if (data.qtde > 9) {
	            $("#pendencia_visitas_icon > span").css({ left: "41px" });
	        } else {
	            $("#pendencia_visitas_icon > span").css({ left: "43px" });
	        }

	        $(list).append("<tr>\
	            <td class='atendimento_link'><b>Data Visita</b></td>\
	            <td><b>Tipo Contato</b></td>\
	            <td><b>Contato</b></td>\
	        </tr>");


	        if (data.qtde > 0) {

	            $.each(data.visitas, function (key, visita) {
	                var data = [];
	                var data_providencia = visita.data_providencia;


	                $(list).append("<tr>\
	                    <td class='atendimento_link'><a href='realizar_visita.php?visita="+visita.roteiro_posto+"' target='_blank' >"+visita.data_visita+"</a></td>\
	                    <td>"+visita.tipo_contato+"</td>\
	                    <td>"+visita.contato+"</td>\
	                </tr>");
	            });
	        } else {
	            $(list).find("tr.sem_pendencias").show();
	        }

	        $(list).append("<tr>\
	            <td class='atendimento_alert' colspan='100%'><center> <a href='listagem_roteiros.php' target='_blank'>Mais visitas</a> </center></td>\
	        </tr>");

	        $(list).find("tr.loading").hide();
		});
	}

	$(function () {
		visitasRefresh();

		$("#pendencia_visitas_icon").click(function () {
			$("#pendencia_visitas_list").show();
			$("#menu_sidebar").css("z-index", "666");

		});

		$("html").on('click','#pendencia_visitas_close',function () {
	//         console.log($(this));
			$("#pendencia_visitas_list").hide();
			$("#menu_sidebar").css("z-index", "40000");
		});

		$("#pendencia_visitas_refresh").click(function () {
			visitasRefresh();
		});
	});
</script>


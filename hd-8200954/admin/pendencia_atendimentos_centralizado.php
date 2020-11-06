<style>
#pendencia_centralizado_icon {
    text-align: center;
    background-image: url(imagens/pendencia_atendimento3.jpg);
    background-position: center;
    height: 45px;
    background-repeat: no-repeat;
    cursor: pointer;
}

#pendencia_centralizado_icon {
    background-image: url(imagens/pendencia_atendimento_azul.jpg) !important;
}

#pendencia_centralizado_icon > span {
    display: inline-block;
    font-weight: bold;
    color: #FFF;
    font-size: 14px;
    margin-top: 1px;
    margin-left: 28px;
}

#pendencia_centralizado_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_centralizado_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_centralizado_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#pendencia_centralizado_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#pendencia_centralizado_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#pendencia_centralizado_list table tr.loading {
    display: none;
}

#pendencia_centralizado_list table tr.loading th {
    background-color: #FFF;
}

#pendencia_centralizado_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#pendencia_centralizado_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#pendencia_centralizado_list h6 {
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

#pendencia_centralizado_list table tr:hover {
    background-color: #D0D0D0;
}

#pendencia_centralizado_list {
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
<div id="pendencia_centralizado_icon">
	<span></span>
	<div id="pendencia_centralizado_list">
		<span id="pendencia_centralizado_refresh" title="Atualizar lista de atendimentos pendentes" >
			<img src="imagens/icon/refresh.png" />
		</span>

		<span id="pendencia_centralizado_close" title="Fechar" >
			<img src="imagens/icon/close.png" />
		</span>

		<h6>Atendimentos pendentes</h6>

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
function atendimentosRefresh (manual) {
	var list = $("#pendencia_centralizado_list").find("table > tbody");
	var atualiza = 'false';

	if (manual == 'manual') {
		var atualiza = 'true';
	}

	$.ajax({
		url: "ajax_pendencia_atendimentos_centralizado.php",
		type: "POST",
		dataType:"JSON",
		data: { atualiza_atendimentos: atualiza },
		beforeSend: function () {
			$(list).find("tr.loading").show();
			$(list).find("tr[class!=sem_pendencias][class!=loading]").remove();
		}
    })
    .done(function(data) {

        $("#pendencia_centralizado_icon > span").text(data.qtde);

        if (data.qtde > 9) {
            $("#pendencia_centralizado_icon > span").css({ left: "41px" });
        } else {
            $("#pendencia_centralizado_icon > span").css({ left: "43px" });
        }

        $(list).append("<tr>\
            <td class='atendimento_alert'></td>\
            <td class='atendimento_link'>Chamado</td>\
            <td>Data Providência</td>\
            <td>Data Limite</td>\
        </tr>");


        if (data.qtde > 0) {

            $.each(data.atendimentos, function (key, atendimento) {
                var data = [];
                var data_providencia = atendimento.data_providencia;

                if(data_providencia != null){
                    data = data_providencia.split("-");

	                var data_a = new Date(data[0], (data[1] - 1), data[2]);
	                data_a = data_a.getTime();

	                var data_b = new Date();
	                data_b = data_b.getTime();

	                if (data_a <= data_b) {
	                    var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução atrasada' />";
	                } else {
	                    if(atendimento.perto_prazo == 'prazo72'){
	                        var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução perto de 72 horas' />";
	                    }else if(atendimento.perto_prazo == 'prazo48'){
	                        var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução perto de 48' />";
	                    }else if(atendimento.perto_prazo == 'prazo24'){
	                        var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução perto de 24' />";
	                    }else{
	                        var img_alert = "&nbsp;";
	                    }
	                }

	                data_providencia = data[2]+"/"+data[1]+"/"+data[0];
                } else {

                    var data_programada = atendimento.data_programada;

                	var data_c = new Date();
                	data_c = data_c.getTime();

                	data = data_programada.split("-");

	                var data_prog = new Date(data[0], (data[1] - 1), data[2]);
	               	data_prog = data_prog.getTime();

                	if(data_prog <= data_c){
                		var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução atrasada' />";
                	}else{
                		var img_alert = "";
                	}

                	data_providencia = "";

                }

                if(atendimento.data_programada == null || atendimento.data_programada.length == 0){
                	data_programada = "";
                }else{

                	data_programada = atendimento.data_programada.trim();

                	data_prog = data_programada.split("-");

                	dia = data_prog[2];
                	if(dia.length > 0){
                		var data_prog_a = new Date(data_prog[0], (data_prog[1] - 1), data_prog[2]);
	                	data_programada =  data_prog[2]+"/"+data_prog[1]+"/"+data_prog[0];
                	}else{
                		data_programada = "";
                	}

                }

                $(list).append("<tr>\
                    <td class='atendimento_alert'>"+img_alert+"</td>\
                    <td class='atendimento_link'><a href='callcenter_interativo_new.php?callcenter="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
                    <td>"+data_providencia+"</td>\
                    <td>"+data_programada+"</td>\
                </tr>");
            });
        } else {
            $(list).find("tr.sem_pendencias").show();
        }

        $(list).append("<tr>\
            <td class='atendimento_alert' colspan='3'><center> <a href='callcenter_pendente_interativo.php?maisAtendimento=true' target='_blank'>Mais Atendimentos</a> </center></td>\
        </tr>");

        $(list).find("tr.loading").hide();
	});
}

$(function () {
	atendimentosRefresh();

	$("#pendencia_centralizado_icon").click(function () {
		$("#pendencia_centralizado_list").show();
        $("#menu_sidebar").css("z-index", "666");
	});

	$("html").on('click','#pendencia_centralizado_close',function () {
//         console.log($(this));
		$("#pendencia_centralizado_list").hide();
        $("#menu_sidebar").css("z-index", "40000");
	});

	$("#pendencia_centralizado_refresh").click(function () {
		atendimentosRefresh('manual');
	});
});
</script>

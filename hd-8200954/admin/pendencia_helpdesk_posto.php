<style>

#pendencia_helpdesk_posto_icon {
	text-align: center;
	background-image: url(imagens/pendencia_atendimento4.jpg);
	background-position: center;
	height: 45px;
	background-repeat: no-repeat;
	cursor: pointer;
}

#pendencia_helpdesk_posto_list {
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

#pendencia_helpdesk_posto_icon > span {
	display: inline-block;
	font-weight: bold;
	color: #FFF;
	font-size: 14px;
	margin-top: 1px;
	margin-left: 28px;
}

#pendencia_helpdesk_posto_refresh {
	float: left;
	margin-left: 3px;
	margin-top: 3px;
	cursor: pointer;
}

#pendencia_helpdesk_posto_close {
	float: right;
	margin-right: 3px;
	margin-top: 3px;
	cursor: pointer;
}

#pendencia_helpdesk_posto_list table {
	margin: 0 auto;
	width: 100%;
	border-collapse: collapse;
}

#pendencia_helpdesk_posto_list table tr th {
	background-color: #596D9B;
	color: #FFF;
}

#pendencia_helpdesk_posto_list table tr.sem_pendencias th {
	background-color: #FFF;
	color: #F00;
	display: none;
}

#pendencia_helpdesk_posto_list table tr.loading {
	display: none;
}

#pendencia_helpdesk_posto_list table tr.loading th {
	background-color: #FFF;
}

#pendencia_helpdesk_posto_list table tr.loading th img {
	width: 32px;
	height: 32px;
}

#pendencia_helpdesk_posto_list table tr td {
	border-bottom: 1px solid #A9A9A9;
}

#pendencia_helpdesk_posto_list h6 {
	margin-top: 5px;
	font-size: 11.9px !important;
}

#pendencia_helpdesk_posto_list table tr:hover {
	background-color: #D0D0D0;
}

#pendencia_helpdesk_interno {
	text-align: center;
	background-position: center;
	height: 45px;
	background-repeat: no-repeat;
	cursor: pointer;
}

#pendencia_helpdesk_interno_list {
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

#pendencia_helpdesk_interno_list table {
	margin: 0 auto;
	width: 100%;
	border-collapse: collapse;
}

#pendencia_helpdesk_interno_list table tr th {
	background-color: #596D9B;
	color: #FFF;
}

#pendencia_helpdesk_interno_list table tr.sem_pendencias th {
	background-color: #FFF;
	color: #F00;
	display: none;
}

#pendencia_helpdesk_interno_list table tr.loading {
	display: none;
}

#pendencia_helpdesk_interno_list table tr.loading th {
	background-color: #FFF;
}

#pendencia_helpdesk_interno_list table tr.loading th img {
	width: 32px;
	height: 32px;
}

#pendencia_helpdesk_interno_list table tr td {
	border-bottom: 1px solid #A9A9A9;
}

#pendencia_helpdesk_interno_list h6 {
	margin-top: 5px;
	font-size: 11.9px !important;
}

#pendencia_helpdesk_interno_list table tr:hover {
	background-color: #D0D0D0;
}

#pendencia_helpdesk_interno > span {
	display: inline-block;
	line-height: 45px;
	font-weight: bold;
	color: #000;
	font-size: 14px;
	position: absolute;
	margin-top: 35px;
	margin-left: -14px;
}

#pendencia_helpdesk_interno_refresh {
	float: left;
	margin-left: 3px;
	margin-top: 3px;
	cursor: pointer;
}

#pendencia_helpdesk_interno_close {
	float: right;
	margin-right: 3px;
	margin-top: 3px;
	cursor: pointer;
}

</style>
<?php
?>
<div id="pendencia_helpdesk_posto_icon">
	<span></span>
	<div id="pendencia_helpdesk_posto_list">
		<span id="pendencia_helpdesk_posto_refresh" title="Atualizar lista de atendimentos pendentes" >
			<img src="imagens/icon/refresh.png" />
		</span>

		<span id="pendencia_helpdesk_posto_close" title="Fechar" >
			<img src="imagens/icon/close.png" />
		</span>

		<h6>Helpdesk <?= in_array($login_fabrica, [169,170]) ? "admin" : "posto" ?> pendentes</h6>

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
<?php if (in_array($login_fabrica, array(30,72))) { 
		$tamanho_qtde = '';
		$span_posicao = '';
		if ($login_fabrica == 30) {
			$tamanho_qtde = 'font-weight: bold; font-size: 14px;';
			$span_posicao = 'span_posicao';
		}
		 
?>
<div id="pendencia_helpdesk_interno">
	<img src="../imagens/chamado_interno_esmaltec.jpg" style="width: 50px;">
	<span id='<?=$span_posicao?>'><p style="margin-top: <?=$margin_top?> <?=$tamanho_qtde?>">0</p></span>
	<div id="pendencia_helpdesk_interno_list">
		<span id="pendencia_helpdesk_interno_refresh" title="Atualizar lista de atendimentos pendentes" >
			<img src="imagens/icon/refresh.png" />
		</span>

		<span id="pendencia_helpdesk_interno_close" title="Fechar" >
			<img src="imagens/icon/close.png" />
		</span>

		<h6>Helpdesk interno pendentes</h6>

		<div style="width: 100%; max-height: 140px; overflow-y: scroll;" >
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
<?php } ?>
<script>
function helpdeskRefresh (parametro = null) {

	var getTotal = false;
	if (parametro == 'getTotal') {
		getTotal = true;
	}

	$.ajax({
		url: "ajax_pendencia_helpdesk_posto.php",
		type: "POST",
		dataType:"JSON",
		data: { atuliza_atendimentos: true, getCount: getTotal},
		beforeSend: function () {
			$("#pendencia_helpdesk_posto_list").find("tr.loading").show();
			$("#pendencia_helpdesk_posto_list").find("tr[class!=sem_pendencias][class!=loading]").remove();
			$("#pendencia_helpdesk_posto_list").find("tr.sem_pendencias").hide();
		}
    })
    .done(function(data) {

	    $("#pendencia_helpdesk_posto_icon > span").text(data.qtde);

        if (data.qtde > 9) {
            $("#pendencia_helpdesk_posto_icon > span").css({ left: "39px" });
        } else {
            $("#pendencia_helpdesk_posto_icon > span").css({ left: "43px" });
        }

	let atend =  data.atendimentos;

	var list = $("#pendencia_helpdesk_posto_list").find("table > tbody");

        if (data.qtde > 0 && atend.length > 0) {

		$(list).html("");

		$(list).append("<tr>\
		    <td class='atendimento_alert'></td>\
		    <td class='atendimento_link'>Chamado</td>\
			    </tr>");

		    $.each(data.atendimentos, function (key, atendimento) {

			$(list).append("<tr>\
			    <td class='atendimento_alert'>&nbsp;</td>\
			    <td class='atendimento_link'><a href='helpdesk_posto_autorizado_atendimento.php?hd_chamado="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
			</tr>");
		    });
        } else {
		$(list).find("tr.sem_pendencias").show();
		$(list).find("tr.sem_pendencias th").show();
        }

        $(list).find("tr.loading").hide();
	});

    <?php if (in_array($login_fabrica, array(30,72))) { ?>

    	if (!getTotal) {

    		helpdeskInternoRefresh();

		}
	<?php } ?>
}

function helpdeskInternoRefresh() {

	$.ajax({
		url: "ajax_pendencia_helpdesk_posto.php",
		type: "POST",
		dataType:"JSON",
		data: { atualiza_chamado_interno: true },
		beforeSend: function () {
			$("#pendencia_helpdesk_interno_list table tbody").find("tr.loading").show();
			$("#pendencia_helpdesk_interno_list table tbody").find("tr.sem_pendencias th").hide();
			$("#pendencia_helpdesk_interno_list table tbody").find("tr[class!=sem_pendencias][class!=loading]").remove();
		}
	}).done(function(data){
		var list = $("#pendencia_helpdesk_interno_list").find("table > tbody");

		$("#pendencia_helpdesk_interno span p").text(data.qtde);

        if (data.qtde > 0) {
	        $(list).append(
	        "<tr>\
	            <td class='atendimento_link'>Chamado</td>\
	            <td class='atendimento_link'>Data Retorno</td>\
	        </tr>");

            $.each(data.atendimentos, function (key, atendimento) {
                $(list).append(
                "<tr>\
                    <td class='atendimento_link'><a href='helpdesk_posto_autorizado_atendimento.php?hd_chamado="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
                    <td>"+atendimento.data_programada+"</td>\
                </tr>");
            });
        } else {
            $(list).find("tr.sem_pendencias th").show();
        }

        $(list).find("tr.loading").hide();
	});

}

$(function () {
	helpdeskRefresh("getTotal");

	$("#pendencia_helpdesk_posto_icon").click(function () {
		$("#pendencia_helpdesk_posto_list").show();
		$("#menu_sidebar").css("z-index", "666");
		helpdeskRefresh();
	});

	$("html").on('click',"#pendencia_helpdesk_posto_close",function () {
		$("#pendencia_helpdesk_posto_list").hide();
		$("#menu_sidebar").css("z-index", "40000");
	});

	<?php if (in_array($login_fabrica, array(30,72))) { ?>
	$("#pendencia_helpdesk_interno").click(function () {
		$("#pendencia_helpdesk_interno_list").show();
		$("#menu_sidebar").css("z-index", "666");
	});

	$("html").on('click',"#pendencia_helpdesk_interno_close",function () {
		$("#pendencia_helpdesk_interno_list").hide();
		$("#menu_sidebar").css("z-index", "40000");
	});

	$("#pendencia_helpdesk_interno_refresh").click(function () {
		helpdeskInternoRefresh();
	});
	<?php } ?>

	$("#pendencia_helpdesk_posto_refresh").click(function () {
		helpdeskRefresh("getTotal");
	});
});
</script>

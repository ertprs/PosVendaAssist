<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'cabecalho.php';
include 'autentica_usuario.php';

if (strlen ($login_posto) == 0) {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}

$sql = "SELECT tbl_servico_correio.codigo, 
			tbl_servico_correio.servico_correio,
			tbl_servico_correio.chave_servico,
			tbl_servico_correio.descricao || case 
								when tbl_servico_correio_fabrica.fabrica in (11,172) then 
									'(Aulik)' 
								when tbl_servico_correio_fabrica.fabrica = 122 then
									'(Wurth)'
								when tbl_servico_correio_fabrica.fabrica = 123 then
									'(Positec)'
								else case when tbl_servico_correio_fabrica.fabrica = 10 then '(Telecontrol)' else '' end end as descricao,
			tbl_servico_correio_fabrica.fabrica
		FROM tbl_servico_correio 
		JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_servico_correio.servico_correio 
		 JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_servico_correio_fabrica.fabrica AND tbl_fabrica.ativo_fabrica IS TRUE
		WHERE tbl_fabrica.fabrica IN (10,81,11,172,160,122,123) GROUP BY 1,2,3,4,5 ORDER BY tbl_servico_correio.servico_correio ";
$resEtiqueta = pg_query ($con,$sql);

?>
<html>
<head>
	<title>Solicitar Etiqueta</title>
	<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
	<style type="text/css">
		.body {
		font-family : verdana;
		}
	</style>
	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script>
		<?php if(pg_num_rows($resEtiqueta) == 0){ ?>
		$(function() {
			buscaServicoWebservice();
		});
		<?php } ?>

		function loading(status) {
		    if ( status == 1 )
		        $('#loading').fadeIn();
		    else
		        $('#loading').fadeOut();
		}

		function buscaServicoWebservice(){
			loading(1);
			var dataAjax = {
		        funcao: "consultaServicoContrato"
		    };

			$.ajax({
		        url: 'funcao_correio.php',
		        type: 'get',
		        data: dataAjax,
		        success: function(data){
		        	var mensagem;
		        	value = JSON.parse(data);

	        		if(value.resultado == false){
        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.faultstring+'</h4> </div>');
	        		}else {
	        			window.location.reload();
	        		}
	        		loading(0);
				}
			});
		}

		function solicitarEtiqueta(servico, codigo, fabrica){
			var qtde_etiqueta = $("#cbQuantidade"+servico+fabrica).val();
			$("#mensagem").removeClass("alert alert-error");
			$("#mensagem").html("");

			if(qtde_etiqueta == "0"){
				$("#mensagem").addClass("alert alert-error");
				$("#mensagem").html("<h4>Selecione uma quantidade de etiqueta.</h4>");

			}else{
				$("#solicitar_etiqueta_"+servico).button("loading");

				loading(1);
				var dataAjax = {
			        codigo		 : codigo,
			        quantidade 	 : $("#cbQuantidade"+servico+fabrica).val(),
			        chave_servico: $("#chave_servico"+servico+fabrica).val(),
			        servico		 : servico,
			        fabrica		 : fabrica
			    };

				$.ajax({
			        url: 'funcao_correio.php?funcao=solicitaEtiquetas',
			        type: 'get',
			        data: dataAjax,
			        success: function(data){
			        	var mensagem;
			        	data = JSON.parse(data);
			        	$.each(data,function(key, value){

			        		if(value.resultado == "false"){
			        			$.each(value[0],function(key, mensagem){
			        				$("#mensagem").html('<div class="alert alert-error"><h4>'+mensagem.faultstring+'</h4> </div>');
			        			});
			        		}else if(value.resultado == "falseErroBanco"){
			        			loading(0);
			        			$("#solicitar_etiqueta_"+servico).button("reset");
			        			$("#mensagem").html('<div class="alert alert-error"><h4>'+value.faultstring+'</h4> </div>');
			        		}else{
			        			window.location.reload();
			        			loading(0);
			        			$("#solicitar_etiqueta_"+servico).button("reset");
			        		}
			        	});
					}
				});
			}
		}
	</script>
	<script src="../bootstrap/js/bootstrap.js"></script>
</head>
<body>
	<div class=noprint>
		<? include 'menu.php' ?>
	</div>
	<img src="js/loadingAnimation.gif" id="loading" style="width:300px; height: 20px; margin-left: -90px;">
	<center style="padding-top: 16px;"><h1>Solicitar Etiqueta</h1></center>
	<center>
		<form class='form-inline ' method='post' name='frm_solicita_etiqueta' action='<?= $PHP_SELF ?>'>
		    <div id="mensagem"></div>
		    <?php if(pg_num_rows($resEtiqueta) > 0){ ?>
			<table border=1 align='center' class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th nowrap rowspan='2'>Código</th>
						<th nowrap rowspan='2'>Serviço de Envio</th>
						<th nowrap rowspan='2'>Etiquetas Disponíveis</th>
						<th nowrap rowspan='2'></th>
						<th nowrap rowspan='4'></th>
					</tr>
				</thead>
				<tbody>
				<?php 
					while($objeto_etiqueta = pg_fetch_object($resEtiqueta)){
						$sqlq = "select count(1) from tbl_etiqueta_servico  where servico_correio = ".$objeto_etiqueta->servico_correio ."  and fabrica = ".$objeto_etiqueta->fabrica ." and embarque isnull;";
						$resq = pg_query($con, $sqlq);
						$quantidade = pg_fetch_result($resq,0,0);
				?>
						<tr>
							<td nowrap align='center'><?=$objeto_etiqueta->codigo?></td>
							<td nowrap align='center'><?=$objeto_etiqueta->descricao?></td>
							<td nowrap align='center'><?=$quantidade?></td>
							<td nowrap align='center'>
								<select id="cbQuantidade<?=$objeto_etiqueta->servico_correio."".$objeto_etiqueta->fabrica?>">
									<option value="0">Selecione</option>
									<option value="10">10</option>
									<option value="30">30</option>
									<option value="50">50</option>
								</select>
							</td>
							<td nowrap align='center'>
								<input class="btn btn-default" type="button" data-loading-text="Solicitando" name="solicitar_etiqueta_<?=$objeto_etiqueta->servico_correio?>" id="solicitar_etiqueta_<?=$objeto_etiqueta->servico_correio?>" value="Solicitar" onclick="solicitarEtiqueta(<?=$objeto_etiqueta->servico_correio?>,<?=$objeto_etiqueta->codigo?>,<?=$objeto_etiqueta->fabrica?>)" >
							</td>
							<td nowrap align='center' hidden>
								<input type="input" hidden name="chave_servico<?=$objeto_etiqueta->servico_correio."".$objeto_etiqueta->fabrica?>" id="chave_servico<?=$objeto_etiqueta->servico_correio."".$objeto_etiqueta->fabrica?>" value="<?=$objeto_etiqueta->chave_servico?>">
							</td>
						</tr>
						<?php
					}
				?>
				</tbody>
			</table>
			<?php } ?>
		</form>
	</center>
</body>
<?php include'rodape.php'; ?>

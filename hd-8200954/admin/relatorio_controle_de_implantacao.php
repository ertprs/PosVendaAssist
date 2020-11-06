<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Relatório de Implantação";
$layout_menu = "financeiro";
$title = $TITULO;

$btn_acao = $_POST['pesquisar'];
if(isset($_POST['pesquisar'])) {
	$fabrica_busca			= $_POST['fabrica_busca'];
	$aux_mes				= $_POST['mes'];
	$aux_ano				= $_POST['ano'];
	$com_horas_faturadas	= $_POST['com_horas_faturadas'];
	if(empty($aux_mes)){
		$msg_erro = "Selecione o Mês";
	}

	if(empty($aux_ano) && strlen($msg_erro) == 0){
		$msg_erro = "Selecione o Ano";
	}

}

$btn_acao = $_POST['cod_implantacao_para_volta'];
if(isset($_POST['cod_implantacao_para_volta'])) {
	$codigo_implantacao_finaliza	= $_GET["cod_implantacao_finaliza"];
	if($codigo_implantacao_finaliza <> ''){
		$sql_upd = "UPDATE tbl_controle_implantacao	 SET
					 finalizada	= 'f'
					 WHERE controle_implantacao = $codigo_implantacao_finaliza";
			//echo $sql_upd;
			$res_upd = pg_query ($con,$sql_upd);
			if(strlen(pg_errormessage($con)) > 0 ) {
				echo "1";
			}else{
				echo "2";
			}
	}else{
		echo "2";
	}
	exit;
}

$btn_busca = $_POST['busca_dados'];
if(isset($_POST['busca_dados'])) {
	header('Content-Type: text/html; charset=ISO-8859-1');
	$filtro_fabrica			= $_GET['filtro_fabrica'];
	$filtro_dt_inicio		= $_GET['filtro_dt_inicio'];
	$filtro_dt_final		= $_GET['filtro_dt_final'];
	$filtro_ativo			= $_GET['filtro_ativo'];
	$filtro_nt_fiscal		= $_GET['filtro_nt_fiscal'];
	$status					= $_GET['status'];
	$filtro_fabrica			= utf8_decode($filtro_fabrica);
	$filtro_dt_inicio		= utf8_decode($filtro_dt_inicio);
	$filtro_dt_final		= utf8_decode($filtro_dt_final);
	$filtro_ativo			= utf8_decode($filtro_ativo);
	$filtro_nt_fiscal		= utf8_decode($filtro_nt_fiscal);


	/*=============== VALIDA DATA =============== */
	$filtro_dt_inicio	= preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $filtro_dt_inicio);
	$filtro_dt_final	= preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $filtro_dt_final);

	if($filtro_dt_inicio <> '' || $filtro_dt_final <> '') {

		if($filtro_dt_inicio <> '' && $filtro_dt_final <> '') {

			list($yf, $mf, $df) = explode("-", $filtro_dt_inicio);
			if(!checkdate($mf,$df,$yf)) {
				$msg_erro = "Data Inválida";
			}
			list($ye, $me, $de) = explode("-", $filtro_dt_final);
			if(!checkdate($me,$de,$ye)) {
				$msg_erro = "Data Inválida";
			}

			if($filtro_dt_inicio <> '' && $filtro_dt_final <> '') {
				$sqlX = "SELECT '$filtro_dt_inicio'::date  <= '$filtro_dt_final'";
				//echo $sqlX;
				$resX = pg_query($con,$sqlX);
				$periodo_data = pg_fetch_result($resX,0,0);

				if($periodo_data == f){
					$msg_erro = "Data Inválida";
				}
			}
		}else{
			$msg_erro = "Data Inválida";
		}


		if($filtro_dt_inicio == '' || $filtro_dt_final == '') {
			$msg_erro = "Selecione a data incial e final";
		}else{
			$and_data_incial = " AND tbl_controle_implantacao.data_implantacao >= '$filtro_dt_inicio 00:00:00'";
			$and_data_final  = " AND tbl_controle_implantacao.data_fechamento  <= '$filtro_dt_final 23:59:59'";
		}

	}

	// === FABRICA ===
	$and_fabrica = "";
	if($filtro_fabrica <> '') {
		$and_fabrica = " AND tbl_controle_implantacao.fabrica = '$filtro_fabrica'";
	}

	// === NOTA FISCAL ===
	if($filtro_nt_fiscal <> '') {
		$filtro_nt_fiscal = " AND tbl_controle_parcela_implantacao.nf = '$filtro_nt_fiscal'";
	}

	$filtro_status ="";
	if($filtro_ativo == 't') {
		$filtro_status = " AND tbl_controle_implantacao.excluido = 't'";
	}else{
		$filtro_status = " AND tbl_controle_implantacao.excluido = 'f'";
		$and_filtro_ativo = " AND tbl_controle_implantacao.finalizada = 't'";
	}

	if(strlen($msg_erro) ==0) {

				$sql_busca="SELECT
								distinct(tbl_controle_implantacao.controle_implantacao),
								tbl_controle_implantacao.fabrica,
								C.nome AS descricao_fabrica,
								tbl_controle_implantacao.valor_implantacao,
								tbl_controle_implantacao.numero_parcela,
								to_char(tbl_controle_implantacao.data_implantacao,'DD/MM/YYYY') AS data_implantacao,
								to_char(tbl_controle_implantacao.data_finalizacao,'DD/MM/YYYY') AS data_finalizacao,
								tbl_controle_implantacao.finalizada,
								tbl_controle_implantacao.admin,
								tbl_controle_implantacao.data_input,
								tbl_controle_implantacao.valor_entrada,
								tbl_controle_implantacao.excluido
							FROM tbl_controle_implantacao
							LEFT JOIN tbl_controle_parcela_implantacao
							ON tbl_controle_implantacao.controle_implantacao = tbl_controle_parcela_implantacao.controle_implantacao

							JOIN tbl_fabrica C
							 ON	 tbl_controle_implantacao.fabrica = C.fabrica
							 $filtro_nt_fiscal

							WHERE 1 = 1
							 $and_filtro_ativo
							 $and_fabrica
							 $and_data_incial
							 $and_data_final
							 $filtro_status

							GROUP BY
								tbl_controle_implantacao.controle_implantacao,
								tbl_controle_implantacao.fabrica,
								C.nome,
								tbl_controle_implantacao.valor_implantacao,
								tbl_controle_implantacao.numero_parcela,
								tbl_controle_implantacao.data_implantacao,
								tbl_controle_implantacao.data_finalizacao,
								tbl_controle_implantacao.finalizada,
								tbl_controle_implantacao.admin,
								tbl_controle_implantacao.data_input,
								tbl_controle_implantacao.valor_entrada,
								tbl_controle_implantacao.excluido
							order by tbl_controle_implantacao.controle_implantacao desc";
				//echo "<br><br><br>".nl2br($sql_busca)."<br><br><br>";
				$res_busca = @pg_exec ($con,$sql_busca);
				if(pg_numrows($res_busca) > 0) { ?>
						<table class="relatorio" width="900" cellspacing='1' cellpadding='3'  >
							<thead>
								<tr rel='<?php echo $b;?>'>
									<th>
										<!-- <a href="javascript:void(0)" name="mostra_dados_impantacao" class="mostra_dados_impantacao" style="font-family: arial;font-size: 10pt;text-align: left;color:white;text-decoration: none;float:left;" rel="1"><span class="href_impantacao" rel="<?php echo $b;?>" alt="0"><span class="conteudo_status_<?php echo $b;?>">+</span><span>&nbsp;<b><?php echo $descricao_fabrica;?></b></a> -->
										Fabríca
									</th>
									<th>Implantação</th>
									<th>Parc.</th>
									<th>Entrada</th>
									<th>Ínicio</th>
									<th>Finalização</th>
									<th>Parc. Paga</th>
									<th>Crédito</th>
									<th>Débito</th>
									<th>Ação</th>
								</tr>
							</thead>
							<tbody>
							<?php

								for($b=0;$b < pg_numrows($res_busca);$b++) {
									$controle_implantacao	= pg_result($res_busca,$b,controle_implantacao);
									$cod_fabrica			= pg_result($res_busca,$b,fabrica);
									$valor_implantacao		= pg_result($res_busca,$b,valor_implantacao);
									$valor_entrada_1		= pg_result($res_busca,$b,valor_entrada);
									$numero_parcela			= pg_result($res_busca,$b,numero_parcela);
									$data_implantacao		= pg_result($res_busca,$b,data_implantacao);
									$data_finalizacao		= pg_result($res_busca,$b,data_finalizacao);
									$finalizada				= pg_result($res_busca,$b,finalizada);
									$admin					= pg_result($res_busca,$b,admin);
									$data_input				= pg_result($res_busca,$b,data_input);
									$descricao_fabrica		= pg_result($res_busca,$b,descricao_fabrica);
									$excluido				= pg_result($res_busca,$b,excluido);

									$title_valor_implantacao	 = "R$ ".number_format($valor_implantacao, 2, ',', '.');
									$valor_implantacao			 = number_format($valor_implantacao, 2, ',', '.');
									$title_valor_entrada		 = "R$ ".number_format($valor_entrada_1, 2, ',', '.');
									$valor_entrada				 = number_format($valor_entrada_1, 2, ',', '.');

									$valor_total_a_receber		 = "0";
									$title_valor_total_a_receber = "R$ ".@number_format($total_valor_pago, 2, ',', '.');


									$sql_total_parcelas_pg = "SELECT
																SUM(valor_entrada) AS valor_entrada
																FROM tbl_controle_parcela_implantacao
															  WHERE controle_implantacao = '$controle_implantacao'
															  AND pago ='t'";
															 //echo "<BR>SQL =".$sql_bus_parcela."<BR>";
									$res_total_parcelas_pg = @pg_exec ($con,$sql_total_parcelas_pg);
									if (@pg_numrows($res_total_parcelas_pg) > 0) {
										$total_pg = pg_result($res_total_parcelas_pg,'0',valor_entrada);
									}else{
										$total_pg = "0";
									}
									$total_pg		= $total_pg + $valor_entrada_1;
									$total_valor_pago		= "R$ ".number_format($total_pg, 2, ',', '.');
									$title_total_valor_pago	= "R$ ".number_format($total_pg, 2, ',', '.');

									$sql_total_pagar = " SELECT (tbl_controle_implantacao.valor_implantacao - tbl_controle_implantacao.valor_entrada) AS ttl_pagar
														 FROM tbl_controle_implantacao
														 WHERE tbl_controle_implantacao.controle_implantacao = '$controle_implantacao'";
														 //echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
									$res_total_pagar = @pg_exec ($con,$sql_total_pagar);
									if (@pg_numrows($res_total_pagar) > 0) {
										$total_pagar	= pg_result($res_total_pagar,'0',ttl_pagar);
									}else{
										$total_pagar = "0";
									}

									$sql_total_parcelas = " SELECT count(tbl_controle_parcela_implantacao.controle_parcela_implantacao)  AS total_parcelas
															FROM tbl_controle_parcela_implantacao
															WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
															AND pago ='t'";
														//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
									$res_total_parcelas = @pg_exec ($con,$sql_total_parcelas);
									if (@pg_numrows($res_total_parcelas) > 0) {
										$total_parcelas_pagas	= pg_result($res_total_parcelas,'0',total_parcelas);
									}else{
										$total_parcelas_pagas = "0";
									}

									$sql_total_pago = " SELECT
														SUM(COALESCE(tbl_controle_parcela_implantacao.valor_entrada,0))  AS total_receber
														FROM tbl_controle_parcela_implantacao
														WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
														AND pago ='t'";
														//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
									$res_total_pago = @pg_exec ($con,$sql_total_pago);
									if (@pg_numrows($res_total_pago) > 0) {
										$total_pago	= pg_result($res_total_pago,'0',total_receber);
									}else{
										$total_pago = "0";
									}

									$valore_a_pagar	= number_format($total_pagar, 2, '.', '');
									$valore_ja_pago	= number_format($total_pago, 2, '.', '');
									$valore_pago	= number_format($total_pago, 2, ',', '.');

									$total_rc = $total_pagar. $total_pago;

									$total_rc = $valore_a_pagar - $valore_ja_pago."<br>";


									if($total_rc == 0) {
										$verif_finalizar_implantacao = 0;
									}else{
										$verif_finalizar_implantacao = $total_pago - $total_rc;
									}

									//echo "PG **".$total_pago." - REC **".$total_rc." = TOTAL **".$verif_finalizar_implantacao;

									if($verif_finalizar_implantacao == 0) {
										$display_finaliza_implantacao = 'style="display:block;"';
									}else{
										$display_finaliza_implantacao = 'style="display:block;"';
									}

									if($total_rc < 0) {
										$total_rc = "0";
									}

									$total_rc	= number_format($total_rc, 2, ',', '.');

									if($total_rc == ''){
										$total_rc = $valor_implantacao;
									}
									$total_pagar			= "R$ ".$total_rc;
									$title_valor_total_a_receber	= "R$ ".$total_rc;
									$valor_implantacao              = "R$ ".$valor_implantacao;

									$cor = ($b % 2) ? "#F7F5F0" : "#F1F4FA";
									echo "<tr bgcolor='{$cor}' style='cursor: pointer ' rel='{$controle_implantacao}'>";
										echo "<td nowrap class='fabricante'>{$descricao_fabrica}</td>";
										echo "<td nowrap class='fabricante'>{$valor_implantacao}</td>";
										echo "<td nowrap class='fabricante'>{$numero_parcela}x</td>";
										echo "<td nowrap class='fabricante'>R$ {$valor_entrada}</td>";
										echo "<td nowrap class='fabricante'>{$data_implantacao}</td>";
										echo "<td nowrap class='fabricante'>{$data_finalizacao}</td>";
										echo "<td nowrap class='fabricante'>{$total_parcelas_pagas}</td>";
										echo "<td nowrap class='fabricante'>R$ {$valore_pago}</td>";
										echo "<td nowrap class='fabricante'>R$ {$total_rc}</td>";
										echo "<td nowrap style='text-align: center;'>";
											if($excluido == 'f'){
												echo "<a href='javascript: void(0);' class='voltar_implantacao' id='voltar_implantacao' rel='{$controle_implantacao}' alt='{$controle_implantacao}' style='color: #596D9B;'>Voltar Implantação</a>";
											}
										echo "</td>";
									echo "</tr>";

									if(intval($numero_parcela) > 0){
										echo "<thead  class='sub_titulo cli_{$controle_implantacao}' style='display: none'>";
											echo "<tr>";
												echo "<th>Parcela</th>";
												echo "<th>Valor</th>";
												echo "<th>Data Prevista</th>";
												echo "<th>Pago</th>";
												echo "<th>Data Pagamento</th>";
												echo "<th>NF</th>";
												echo "<th colspan='4'>Observação</th>";
											echo "<tr>";
										echo "</thead>";
										echo "<tbody  class='sub_titulo cli_{$controle_implantacao}' style='display: none'>";

											for($p=0;$p < $numero_parcela;$p++) {
												$n_parcela = $p + 1;

												$sql_bus_parcela = "SELECT
														controle_parcela_implantacao,
														controle_implantacao,
														parcela,
														valor_entrada,
														to_char(data_prevista,'DD/MM/YYYY') AS data_prevista,
														to_char(data_pagamento,'DD/MM/YYYY') AS data_pagamento,
														nf,
														pago,
														observacao
														FROM tbl_controle_parcela_implantacao
														WHERE controle_implantacao = '$controle_implantacao'
																	AND parcela ='$n_parcela'";
												//echo nl2br($sql_bus_parcela);

												$par_controle_parcela_implantacao	= "";
												$par_controle_implantacao			= "";
												$par_parcela						= "";
												$par_valor_entrada					= "";
												$par_data_prevista					= "";
												$par_data_pagamento					= "";
												$par_nf								= "";
												$title_par_valor_entrada			= "";
												$pago								= "";
												$observacao							= "";
												$res_bus_parcela = @pg_exec ($con,$sql_bus_parcela);
												if (@pg_numrows($res_bus_parcela) > 0) {
													$par_controle_parcela_implantacao	= pg_result($res_bus_parcela,'0',controle_parcela_implantacao);
													$par_controle_implantacao			= pg_result($res_bus_parcela,'0',controle_implantacao);
													$par_parcela						= pg_result($res_bus_parcela,'0',parcela);
													$par_valor_entrada					= pg_result($res_bus_parcela,'0',valor_entrada);
													$par_data_prevista					= pg_result($res_bus_parcela,'0',data_prevista);
													$par_data_pagamento					= pg_result($res_bus_parcela,'0',data_pagamento);
													$par_nf								= pg_result($res_bus_parcela,'0',nf);
													$pago								= pg_result($res_bus_parcela,'0',pago) ? "Sim" : "Não";
													$observacao							= pg_result($res_bus_parcela,'0',observacao);

													$title_par_valor_entrada			= "R$ ".number_format($par_valor_entrada, 2, ',', '.');
													$par_valor_entrada					= number_format($par_valor_entrada, 2, ',', '.');
													$par_valor_entrada					= "R$ ".$par_valor_entrada;
												}

												$cor = ($p % 2) ? "#F7F5F0" : "#F1F4FA";
												echo "<tr bgcolor='{$cor}'>";
													echo "<td>{$par_parcela}</td>";
													echo "<td>$par_valor_entrada</td>";
													echo "<td>{$par_data_prevista}</td>";
													echo "<td>{$pago}</td>";
													echo "<td>{$par_data_pagamento}</td>";
													echo "<td>{$par_nf}</td>";
													echo "<td colspan='4'>{$observacao}</td>";
												echo "<tr>";

											}
											echo "<tr style='background-color: #596D9B;'>";
												echo "<td colspan='10' style='height: 3px;'></td>";
											echo "<tr>";
										echo "</tbody>";
									}
								}
						echo "</tbody>";
					echo "</table>";
				}else{
					echo "<center><div style='color:green;font-size:15'><b>Nenhum registro encontrado.</b></div></center>";
				}
		}else {
			echo "<center><div style='color:red;font-size:15'><b>".$msg_erro."</b></div></center>";
		}
	//echo "TESTE BUSCA OK <br>".$msg_erro;
	exit;
}
include 'cabecalho.php';
?>
<style type="text/css">
.titulo_tabela, .titulo_tabela td{
	background-color:#596d9b !important;
	font: bold 14px "Arial" !important;
	color:#FFFFFF !important;
	text-align:center !important;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario, .formulario td{
	background-color:#D9E2EF;
	font:12px Arial;
	text-align: left;
}


table.relatorio {
	border: solid 1px #5A6D9C;
}


.titulo_relatorio{
	font-weight: bold;
}



/* VALIDAÇÃO */
* { font-family: Verdana; font-size: 96%; }

input[type=text], textarea, select{
	border: 1px solid #CCC !important;
	background-color: #FCFCFC;
	padding: 2px;
}

.relatorio th{
	background-color:#596d9b;
    font: bold 12px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.relatorio td{
    font: 12px "Arial";
}

.sub_titulo th{
	background-color:#7E92BC !important;
}
</style>
<?

function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

include "menu.php";

?>


<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>

<script type="text/javascript" src="js/jquery.price_format.1.5.js"></script>
<script src="../js/jquery.validate.js" type="text/javascript"></script>
<script src="../js/jquery.blockUI_2.39.js" type="text/javascript"></script>
<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>
<script src="js/jquery.maskedinput.js" type="text/javascript"></script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.price_format.1.5.js"></script>

<? include "javascript_calendario_new.php";
include '../js/js_css.php';
?>

<script type="text/javascript" charset="utf-8">
	$(document).ready(init);
		function init(){
			$.datePicker.setDateFormat('dmy', '/');
			$.datePicker.setLanguageStrings(
				['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
				['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
				{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
			);
		}

	$(function() {
			$('#data_incial').datepick({startdate:'01/01/2000'});
			$('#data_final').datepick({startDate:'01/01/2000'});
			$('#data_incial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");
			$.tablesorter.defaults.widgets = ['zebra'];
			$('.relatorio').tablesorter();
		});

   $(function() {
		$('#valor_incial').priceFormat({
			prefix: 'R$ ',
			centsSeparator: ',',
			thousandsSeparator: '.'
		});

		$('#valor_final').priceFormat({
			prefix: 'R$ ',
			centsSeparator: ',',
			thousandsSeparator: '.'
		});
  });

	function busca_dados() {

		var filtro_fabrica		= $("#fabrica_busca").val();
		var filtro_dt_inicio	= $("#data_incial").val();
		var filtro_dt_final		= $("#data_final").val();
		var filtro_nt_fiscal	= $("#nota_fiscal").val();

		if ($("#status").is(':checked')) {
			var filtro_ativo			= 't';
		}else {
			var filtro_ativo			= '';
		}

		filtro_fabrica		= encodeURIComponent(filtro_fabrica);
		filtro_dt_inicio	= encodeURIComponent(filtro_dt_inicio);
		filtro_dt_final		= encodeURIComponent(filtro_dt_final);
		filtro_ativo		= encodeURIComponent(filtro_ativo);
		filtro_nt_fiscal	= encodeURIComponent(filtro_nt_fiscal);

		$.blockUI({
			message: '<h1><div style="font-size:14px;">PROCESSANDO AGUARDE !</div></h1>',
			timeout: 120000
		});

		$("#dados_relatorio").load('<?=$PHP_SELF?>?filtro_fabrica='+filtro_fabrica+'&filtro_dt_inicio='+filtro_dt_inicio+'&filtro_dt_final='+filtro_dt_final+'&filtro_ativo='+filtro_ativo+'&filtro_nt_fiscal='+filtro_nt_fiscal,{'busca_dados':'busca'},function(response, status, xhr) {
		  //alert(status);
		  //alert(response);
		  //alert(xhr);
		  $.unblockUI();

		});

		//$("#text").val(val.replace('.', ':'));
		//dados_relatorio
	}


	$(document).ready(function() {

		$('.fabricante').live('click', function(){
			var implantacao = $(this).parent().attr('rel');

			$(".cli_"+implantacao).toggle();
		});


		$('.href_impantacao').live('click', function(){
			//alert("TESTE");
			var cod_impantacao			= $(this).attr('rel'); //CODIGO DA DIV
			var cod_impantacao_status	= $(this).attr('alt'); //VALOR QUE VERIFICA O STATUS DA DIV

			var div_href				= "href_impantacao_"+cod_impantacao;//VALOR DA DIV DO HREF
			var div_conteudo			= 'href_dados_impantacao_'+cod_impantacao; //DIV DO CONTEUDO COM OS DADOS DA IMPLANTAÇÃO
			//ALT 0 FECHADA /// ALT 1 ABERTA
			var div_conteudo_status		= 'conteudo_status_'+cod_impantacao;//LABEL COM (+) (-) MOSTRANDO STATUS DO CONTEUDO

			if(cod_impantacao_status == '0'){
				$("."+div_conteudo_status).html('-');
				$(this).attr('alt', '1');
				$("."+div_conteudo).show('fast');
			}else{
				$("."+div_conteudo_status).html('+');
				$(this).attr('alt', '0');
				$("."+div_conteudo).hide('fast');
			}

		});


		$('.finalizar_implantacao').live('click', function(){
			alert("finalizar");
			var cod_impantacao	= $(this).attr('rel'); //CODIGO DA DIV
			alert(cod_impantacao);
		});


		$('.voltar_implantacao').live('click', function(){

			var codigo					= $(this).attr('rel');
			var cod_implantacao_volta	= $(this).attr('alt');
			var campo 					= $(this);
			//alert(cod_implantacao_volta);

			if(confirm("Deseja voltar para implantação.")) {

				$("#dados_relatorio_none").load('<?=$PHP_SELF?>?cod_implantacao_finaliza='+cod_implantacao_volta,{'cod_implantacao_para_volta':'cod_implantacao_para_volta'},function(response, status, xhr) {
					//alert(status);
					//alert(response);
					//alert(xhr);
					//alert(response);
					if(response == 2) {
						campo.parent().parent().css('display','none');
						$(".cli_"+codigo).css('display','none');

						//alert("OK");
						$("#infor_fabrica"+codigo).css('display','none');
						//$(".href_dados_impantacao_"+codigo).css('display','none');
						$("#msg_fabrica"+codigo).css('display','none');
						$("#voltar_implantacao_"+codigo).css('display','none');
					}else {
						alert("ERRO AO VOLTAR PARA IMPLANTAÇÃO.");
					}

				});
			}

		});
	});
</script>
<form name='filtrar' method='POST' ACTION='<? echo $PHP_SELF;?>'>
	<table width = '500' align = 'center' cellpadding='3' cellspacing='0' border='0' class="formulario">
		<tr class='titulo_tabela'>
			<td  colspan='4'>Parâmetros de Pesquisa </td>
		</tr>
		<tr>
			<td width='50px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='50px'>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='2'>
				Fabricante<br />
				<?php
					$sqlfabrica = "SELECT
										DISTINCT(tbl_fabrica.fabrica) AS fabrica,
										tbl_fabrica.nome
								   FROM  tbl_controle_implantacao
									JOIN tbl_fabrica
									ON 	 tbl_controle_implantacao.fabrica = tbl_fabrica.fabrica
									WHERE tbl_controle_implantacao.finalizada = 't'
									GROUP BY tbl_fabrica.fabrica,tbl_fabrica.nome
									ORDER BY fabrica";
					$resfabrica = pg_query ($con,$sqlfabrica);
					$n_fabricas = pg_num_rows($res);
				?>
				<select class='frm' style='width: 400px;' name='fabrica_busca' id="fabrica_busca">
				<option value=''>Todas Fabricas</option>
				<?php
				for ($x = 0 ; $x < pg_num_rows($resfabrica) ; $x++){
					$fabrica   = trim(pg_fetch_result($resfabrica,$x,fabrica));
					$nome      = trim(pg_fetch_result($resfabrica,$x,nome));
					?>
					<option value='<?php echo $fabrica;?>' <?php if ($fabrica_busca == $fabrica) echo " SELECTED "; ?> ><?php echo $nome;?></option>
				<?php
				}
				?>
				</select>
			</td>
			<td>&nbsp;</td>
		<tr>
		<tr>
			<td>&nbsp;</td>
			<td>Data incial<br /><input type='text' name='data_incial' id='data_incial' value='' class='' size='12'></td>
			<td>Data Final<br /><input type='text' name='data_final' id='data_final' value='' class='' size='12'></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Nota Fiscal<br /><input type='text' name='nota_fiscal' id='nota_fiscal' value='' class='' size='12'></td>
			<td>Implantações excluída<br /><input type='checkbox' name='status' id='status' value='t' ></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style='padding: 12px; text-align: center;' colspan='4'>
				<input type="button" name="cadastrar" id="cadastrar" value="Pesquisar" class="frm" style='paddin: 3px 10px' onclick="busca_dados();">
			</td>
		</tr>

	</table>

<table width = '500' align= 'center' cellpadding='0' cellspacing='0' border='0'  style="margin-top:10px;">
	<tr>
		<td>
			<div id="dados_relatorio_none" style='display:none;'></div>
		</td>
	</tr>
	<tr>
		<td>
			<div id="dados_relatorio"></div>
		</td>
	</tr>
</table>

</FORM><br /><br /><br /><br />


<?php
include "rodape.php";
 ?>
</body>
</html>

<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

list($dia_atual, $mes_atual, $ano_atual) = explode(" ", date("d m Y"));

//$dia_atual = "11";
//$mes_atual = "10";
//$ano_atual = "2018";

$sqlDashboard =  "
	
	WITH dados AS (

		SELECT tbl_hd_chamado.hd_chamado,
			   extract(day from tbl_hd_chamado.data)   as dia,
			   extract(month from tbl_hd_chamado.data) as mes,
			   extract(year from tbl_hd_chamado.data)  as ano,
			   tbl_hd_chamado.data 					   as data_abertura,
			   atendente_atual.grupo_admin,
			   tbl_hd_chamado.fabrica_responsavel,
			   tbl_hd_chamado.tipo_chamado
		FROM tbl_hd_chamado
		JOIN tbl_admin atendente_atual ON tbl_hd_chamado.atendente = atendente_atual.admin
		AND  atendente_atual.fabrica = 10
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND extract(year from tbl_hd_chamado.data) 	= {$ano_atual}
		AND extract(month from tbl_hd_chamado.data) = {$mes_atual}

	), dados_status AS (

		SELECT 
			   SUM(tbl_hd_chamado.hora_faturada) FILTER(where tbl_hd_chamado.data_aprovacao is not null) as horas_a_receber, 
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Efetivação') as em_efetivacao,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Validação')  as em_validacao,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.data_envio_aprovacao IS NOT NULL) AS com_orcamento,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.data_envio_aprovacao IS NULL) AS sem_orcamento,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Análise'   AND tbl_hd_chamado.analise IS NOT NULL) AS com_analise,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Análise'   AND tbl_hd_chamado.analise IS NULL AND tbl_hd_chamado.tipo_chamado = 5) AS sem_analise,
			   COUNT(1) FILTER( WHERE tbl_hd_chamado.status = 'Requisitos' AND (
			   		SELECT tbl_hd_chamado_item.hd_chamado_item
			   		FROM tbl_hd_chamado_item
			   		WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			   		AND tbl_hd_chamado_item.status_item = 'Ap.Requisitos'
			   		LIMIT 1
			   	) IS NOT NULL) AS com_requisitos,
			   	COUNT(1) FILTER(WHERE tbl_hd_chamado.status = 'Requisitos' AND (
			   		SELECT tbl_hd_chamado_item.hd_chamado_item
			   		FROM tbl_hd_chamado_item
			   		WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			   		AND tbl_hd_chamado_item.status_item = 'Ap.Requisitos'
			   		LIMIT 1
			   	) IS NULL) AS sem_requisitos,
			   	tbl_hd_chamado.fabrica_responsavel
		FROM tbl_hd_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_hd_chamado.resolvido IS NULL
		AND tbl_hd_chamado.status != 'Resolvido'
		GROUP BY tbl_hd_chamado.fabrica_responsavel

	), dados_resolvidos AS (

		SELECT  SUM(tbl_hd_chamado.hora_faturada) as horas_recebidas,
				COUNT(1) FILTER(WHERE tbl_hd_chamado.tipo_chamado = 4) as resolvidos_alteracao_mes,
				COUNT(1) FILTER(WHERE tbl_hd_chamado.tipo_chamado = 5) as resolvidos_erro_mes,
				COUNT(1) FILTER(WHERE tbl_hd_chamado.tipo_chamado = 4 AND extract(day from tbl_hd_chamado.data_resolvido)  = {$dia_atual}) as resolvidos_alteracao_hoje,
				COUNT(1) FILTER(WHERE tbl_hd_chamado.tipo_chamado = 5 AND extract(day from tbl_hd_chamado.data_resolvido) = {$dia_atual}) as resolvidos_erro_hoje,
				tbl_hd_chamado.fabrica_responsavel
		FROM tbl_hd_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND extract(year from tbl_hd_chamado.data_resolvido) = {$ano_atual}
		AND extract(month from tbl_hd_chamado.data_resolvido) = {$mes_atual}
		GROUP BY tbl_hd_chamado.fabrica_responsavel

	)
	SELECT
		dados_status.*,
		dados_resolvidos.*,
		COUNT(*) as total_chamados_abertos,
		COUNT(1) FILTER(WHERE dados.dia = {$dia_atual} AND dados.tipo_chamado = 4) as abertos_hoje_alteracao,
		COUNT(1) FILTER(WHERE dados.mes = {$mes_atual} AND dados.tipo_chamado = 4) as abertos_mes_alteracao,
		COUNT(1) FILTER(WHERE dados.dia = {$dia_atual} AND dados.tipo_chamado = 5) as abertos_hoje_erro,
		COUNT(1) FILTER(WHERE dados.mes = {$mes_atual} AND dados.tipo_chamado = 5) as abertos_mes_erro,
		TO_CHAR(dados.data_abertura, 'mm/yyyy') 		as mes_ano
	FROM dados
	JOIN dados_status 	   ON dados_status.fabrica_responsavel 	   = dados.fabrica_responsavel
	JOIN dados_resolvidos ON dados_resolvidos.fabrica_responsavel = dados.fabrica_responsavel
	GROUP BY mes_ano,
			 dados_status.com_orcamento,
			 dados_status.sem_orcamento,
			 dados_status.com_analise,
			 dados_status.sem_analise,
			 dados_status.com_requisitos,
			 dados_status.sem_requisitos,
			 dados_resolvidos.resolvidos_alteracao_mes,
			 dados_resolvidos.resolvidos_erro_mes,
			 dados_resolvidos.resolvidos_alteracao_hoje,
			 dados_resolvidos.resolvidos_erro_hoje,
			 dados_status.em_efetivacao,
			 dados_status.em_validacao,
			 dados_resolvidos.horas_recebidas,
			 dados_status.horas_a_receber,
			 dados_status.fabrica_responsavel,
			 dados_resolvidos.fabrica_responsavel

";
$resDashboard = pg_query($con, $sqlDashboard);

$hoje = date("d/m/Y");

$resolvidos_hoje 	  = (int) pg_fetch_result($resDashboard, 0, 'resolvidos_alteracao_hoje');
$resolvidos_mes  	  = (int) pg_fetch_result($resDashboard, 0, 'resolvidos_alteracao_mes');
$abertos_hoje 	 	  = (int) pg_fetch_result($resDashboard, 0, 'abertos_hoje_alteracao');
$abertos_mes  	 	  = (int) pg_fetch_result($resDashboard, 0, 'abertos_mes_alteracao');

$resolvidos_hoje_erro = (int) pg_fetch_result($resDashboard, 0, 'resolvidos_erro_hoje');
$resolvidos_mes_erro  = (int) pg_fetch_result($resDashboard, 0, 'resolvidos_erro_mes');
$abertos_hoje_erro 	  = (int) pg_fetch_result($resDashboard, 0, 'abertos_hoje_erro');
$abertos_mes_erro  	  = (int) pg_fetch_result($resDashboard, 0, 'abertos_mes_erro');

$em_efetivacao        = (int) pg_fetch_result($resDashboard, 0, 'em_efetivacao');
$em_validacao         = (int) pg_fetch_result($resDashboard, 0, 'em_validacao');

$com_requisitos       = (int) pg_fetch_result($resDashboard, 0, 'com_requisitos');
$sem_requisitos       = (int) pg_fetch_result($resDashboard, 0, 'sem_requisitos');
$com_orcamento        = (int) pg_fetch_result($resDashboard, 0, 'com_orcamento');
$sem_orcamento        = (int) pg_fetch_result($resDashboard, 0, 'sem_orcamento');
$com_analise          = (int) pg_fetch_result($resDashboard, 0, 'com_analise');
$sem_analise          = (int) pg_fetch_result($resDashboard, 0, 'sem_analise');

$horas_a_faturar      = (int) pg_fetch_result($resDashboard, 0, 'horas_a_receber');
$horas_faturadas      = (int) pg_fetch_result($resDashboard, 0, 'horas_recebidas');

$graficoDia = json_encode([
						["name" => "Resolvidos Dia",
					   	 "y"    => $resolvidos_hoje],
					  	["name" => "Abertos Dia",
					   	 "y"    => $abertos_hoje]
			  ]);

$graficoMes = json_encode([
						["name" => utf8_encode("Resolvidos Mês"),
					   	 "y"    => $resolvidos_mes],
					  	["name" => utf8_encode("Abertos Mês"),
					   	 "y"    => $abertos_mes]
			  ]);

$graficoDiaErro = json_encode([
							["name" => "Resolvidos Dia",
						   	 "y"    => $resolvidos_hoje_erro],
						  	["name" => "Abertos Dia",
						   	 "y"    => $abertos_hoje_erro]
				  ]);

$graficoMesErro = json_encode([
							["name" => utf8_encode("Resolvidos Mês"),
						   	 "y"    => $resolvidos_mes_erro],
						  	["name" => utf8_encode("Abertos Mês"),
						   	 "y"    => $abertos_mes_erro]
				  ]);

$graficoAnalise = json_encode([
						["name" => utf8_encode("S/Análise"),
					   	 "y"    => $sem_analise],
					  	["name" => utf8_encode("C/Análise"),
					   	 "y"    => $com_analise]
			      ]);

$graficoOrcamento = json_encode([
							["name" => utf8_encode("S/Orçamento"), 
						   	 "y"    => $sem_orcamento],
						  	["name" => utf8_encode("C/Orçamento"), 
						   	 "y"    => $com_orcamento]
					]);

$graficoRequisitos = json_encode([
							["name" => utf8_encode("S/Requisitos"), 
						   	 "y"    => $sem_requisitos],
						  	["name" => utf8_encode("C/Requisitos"), 
						   	 "y"    => $com_requisitos]
					]);

$graficoEfetivacaoValidacao = json_encode([
									["name" => utf8_encode("Em Efetivação"),
								   	 "y"    => $em_efetivacao],
								  	["name" => utf8_encode("Em Validação"),
								   	 "y"    => $em_validacao]
							  ]);



function retornaDataPortugues() {
	$numero_dia = date('w')*1;
    $dia_mes = date('d');
    $numero_mes = date('m')*1;
    $ano = date('Y');
    $dia = array('Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado');
    $mes = array('', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
    return $dia[$numero_dia] . ", " .$dia_mes . " de " . $mes[$numero_mes] . " de " . $ano;
}
?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>
		<script>
			Highcharts.setOptions({
			    colors: Highcharts.map(Highcharts.getOptions().colors, function (color) {
			        return {
			            radialGradient: {
			                cx: 0.5,
			                cy: 0.3,
			                r: 0.7
			            },
			            stops: [
			                [0, color],
			                [1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
			            ]
			        };
			    })
			});
		</script>
		<title>Dashboard Desenvolvimento | Telecontrol</title>
		<style>
			body {
				background-color: #eef2f6;
			}
			.tela {
				height: 93% !important;
			}
			.grafico {
				width: 96%; 
				height: 45.5%;
				margin-top: 2.5%;
				border: 1px solid black;
			}
			.grafico_status {
				width: 96%; 
				height: 94.5%;
				margin-top: 2.5%;
				border: 1px solid black;				
			}
			.cabecalho h2 {
				font-family: sans-serif;
				color: white;
				font-size: 22px;
			}
			.cabecalho .row {
				margin-left: 7.5%;
			}
			.div_cabecalho_resolvidos, 
			.div_cabecalho_abertos, 
			.div_cabecalho_status,
			.div_cabecalho_erros,
			.div_cabecalho_orcamento,
			.div_cabecalho_requisitos,
			.div_cabecalho_analise,
			.div_cabecalho_ev {
				width: 100%;
				margin-left: 2.5%;
				height: 10%;
				margin-top: 2%;
				border: 1px solid black;
				text-align: center;
			}
			.div_cabecalho_analise {
				background-color: red;
			}
			.div_cabecalho_requisitos {
				background-color: #088A08;
			}
			.div_cabecalho_abertos {
				background-color: darkblue;
			}
			.div_cabecalho_resolvidos {
				background-color: darkblue;
			}
			.div_cabecalho_erros {
				background-color: darkred;
			}
			.div_cabecalho_status {
				background-color: black;
			}
			.div_cabecalho_orcamento {
				background-color: #AEB404;
			}
			.div_cabecalho_ev {
				background-color: #6E6E6E;
			}
			.div_info {
				width: 100%;
				margin-left: 2.5%;
				height: 80%;
				margin-top: 2.5%;
				border: 1px solid black;
				background-color: white;
				text-align: center;
				padding-top: 2.5%;
			}
			.div_info h3 {
				color: #405a78;
				font-weight: lighter;
				font-size: 20px;
			}
			.legenda_status {
				width: 17px;
				height: 17px;
				background-color: #404040;
				border: 0.5px black solid;
			}
			.txt_status_dia {
				font-size: 12px;
				padding-top: 1px;
				font-family: sans-serif;
			}
			.txt_status {
				font-size: 15px;
				padding-top: 10px;
				text-align: center;
			}
			.legenda_resolvidos_dia {
				width: 20px;
				height: 20px;
				background-color: #298A08;
			}
			.txt_resolvidos_dia {
				font-size: 15px;
				padding-top: 1px;
				font-family: sans-serif;
			}
			.legenda_resolvidos_mes {
				background-color: #B40404;
				width: 20px;
				height: 20px;
			}
			.txt_resolvidos_mes {
				font-size: 15px;
				padding-top: 1px;
				font-family: sans-serif;
			}
			.legendas {
				margin: 5%;
				height: 10%;
			}
			.legendas_alteracao {
				height: 8%;
			}
			.legendas_status {
				margin: 2%;

			}
			.legendas:not(:last-of-type) {
			}
			.txt_resolvidos {
				font-size: 21px;
				padding-top: 10px;
				text-align: center;
			}
			.div_legenda {
				width: 85%;
				margin-left: 17%;
				margin-top: 7.5%;
			}
			.tabela_dia {
				margin-left: 5%;
				width: 90%;
			}
			.tabela_dia th, .tabela_dia td {
				text-align: center;
			}
			.tabela_dia th {
				background-color: #4d4d4d;
				color: white;
				height: 35px;
			}
			.tabela_dia td {
				background-color: white;
				border: solid 1px gray;
				text-align: center;
				height: 60px;
				font-size: 16px;
				width: 50%;
			}
			.aberto {
				color: #d90000 !important;
			}
			.resolvido {
				color: green !important;
			}
			.numero_dia_mes {
				font-size: 25px;
				color: black !important;
			}
			#legenda_geral {
				width: 96.5%;
				margin-left: 1%;
				height: 5%;
				margin-top: 0.25%;
				border: 1px solid black;
				text-align: center;
				background-color: white;
			}
			#lista_legendas div {
				width: 16.64%;
				float: left;
				text-align: center;
				height: 100%;
				opacity: 0.20;
				color: white;
				font-size: 17px;
				line-height: 30px;
				font-weight: bolder;
				box-shadow: 1px 0px black;
				cursor: pointer;
			}
			.div_grafico {
				margin-top: -1%;
			}
		</style>
	</head>
	<body>
		<div id="legenda_geral">
			<div id="lista_legendas" style="width: 100%;">
				<div style="background-color: darkblue;opacity: .75">
					Alteração
				</div>
				<div style="background-color: darkred;">
					Erro
				</div>
				<div style="background-color: #AEB404;">
					Orçamento
				</div>
				<div style="background-color: #088A08;">
					Requisitos
				</div>
				<div style="background-color: red;">
					Análise
				</div>
				<div style="background-color: #6E6E6E;">
					Efetivação/Validação
				</div>
			</div>
		</div>
		<div class="tela row-fluid">
			<div class="span5 cabecalho">
				<div class="div_cabecalho_resolvidos">
					<h2>Alteração - Abertos / Resolvidos</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas_alteracao">
							<span class="span1">
								<div class="legenda_resolvidos_dia"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">Resolvidos</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">Abertos</div> 
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado do Dia</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido" style="font-size: 12px !important;"><span class="numero_dia_mes"><?= $resolvidos_hoje ?></span><br /><strong>Resolvidos</strong></td>
								<td class="aberto" style="font-size: 12px !important;"><span class="numero_dia_mes"><?= $abertos_hoje ?></span><br /><strong>Abertos</strong></td>
							</tr>
						</tbody>
					</table>
					<br />
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado do Mês</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $resolvidos_mes ?></span><br /><strong>Resolvidos</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $abertos_mes ?></span><br /><strong>Abertos</strong></td>
							</tr>
							<tr>
								<td><span class="numero_dia_mes"><?= $horas_faturadas ?></span><br /><strong>Horas</strong></td>
								<td><span class="numero_dia_mes"><?= $horas_a_faturar ?></span><br /><strong>Horas</strong></td>
							<tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_dia" class="grafico"></div>
					<div id="grafico_mes" class="grafico"></div>
				</div>
			</div>
		</div>
		<div class="tela row-fluid" hidden>
			<div class="span5 cabecalho">
				<div class="div_cabecalho_erros">
					<h2>Erros - Abertos / Resolvidos</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas">
							<span class="span1">
								<div class="legenda_resolvidos_dia"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">Resolvidos</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">Abertos</div> 
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado do Dia</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $resolvidos_hoje_erro ?></span><br /><strong>Resolvidos</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $abertos_hoje_erro ?></span><br /><strong>Abertos</strong></td>
							</tr>
						</tbody>
					</table>
					<br />
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado do Mês</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $resolvidos_mes_erro ?></span><br /><strong>Resolvidos</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $abertos_mes_erro ?></span><br /><strong>Abertos</strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_dia_erro" class="grafico"></div>
					<div id="grafico_mes_erro" class="grafico"></div>
				</div>
			</div>
		</div>
		<div class="tela row-fluid" hidden>
			<div class="span5 cabecalho">
				<div class="div_cabecalho_orcamento">
					<h2>Orçamento</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas">
							<span class="span1">
								<div class="legenda_resolvidos_dia" style="background-color: #298A08;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">C/Orçamento</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes" style="background-color: #B40404;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">S/Orçamento</div> 
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $com_orcamento ?></span><br /><strong>C/Orçamento</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $sem_orcamento ?></span><br /><strong>S/Orçamento</strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_orcamento" class="grafico_status"></div>
				</div>
			</div>
		</div>
		<div class="tela row-fluid" hidden>
			<div class="span5 cabecalho">
				<div class="div_cabecalho_requisitos">
					<h2>Requisitos</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas">
							<span class="span1">
								<div class="legenda_resolvidos_dia" style="background-color: #298A08;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">C/Requisitos</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes" style="background-color: #B40404;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">S/Requisitos</div> 
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $com_requisitos ?></span><br /><strong>C/Requisitos</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $sem_requisitos ?></span><br /><strong>S/Requisitos</strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_requisitos" class="grafico_status"></div>
				</div>
			</div>
		</div>
		<div class="tela row-fluid" hidden>
			<div class="span5 cabecalho">
				<div class="div_cabecalho_analise">
					<h2>Análise</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas">
							<span class="span1">
								<div class="legenda_resolvidos_dia" style="background-color: #298A08;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">C/Análise</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes" style="background-color: #B40404;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">S/Análise</div> 
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $com_analise ?></span><br /><strong>C/Análise</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $sem_analise ?></span><br /><strong>S/Análise</strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_analise" class="grafico_status"></div>
				</div>
			</div>
		</div>
		<div class="tela row-fluid ultimo" hidden>
			<div class="span5 cabecalho">
				<div class="div_cabecalho_ev">
					<h2>Efetivação / Validação</h2>
				</div>
				<div class="div_info">
					<h3><?= retornaDataPortugues() ?></h3>
					<div class="div_legenda">
						<div class="legendas">
							<span class="span1">
								<div class="legenda_resolvidos_dia" style="background-color: #298A08;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_dia">Efetivação</div>
							</div>
							<span class="span1">
								<div class="legenda_resolvidos_mes" style="background-color: #B40404;"></div>
							</span>
							<div class="span5">
								<div class="span9 txt_resolvidos_mes">Validação</div>
							</div>
						</div>
					</div>
					<table class="tabela_dia">
						<thead>
							<tr>
								<th colspan="2">Resultado</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="resolvido"><span class="numero_dia_mes"><?= $em_efetivacao ?></span><br /><strong>Efetivação</strong></td>
								<td class="aberto"><span class="numero_dia_mes"><?= $em_validacao ?></span><br /><strong>Validação</strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="span7 corpo">
				<div class="div_grafico">
					<div id="grafico_efetivacao_validacao" class="grafico_status"></div>
				</div>
			</div>
		</div>
		<script>

			$(function() {

				var alternaTelas = setInterval(intervalo, 20000);

				$(document).keydown(function(e) {

					var indexTelaAtiva = $(".tela:visible").index();

				    if (e.keyCode == 37) {

				    	var elementoAlternar = $("#lista_legendas > div")[indexTelaAtiva - 2];
				    	$(elementoAlternar).click();

				    } else if (e.keyCode == 39) {

				    	var elementoAlternar  = $("#lista_legendas > div")[indexTelaAtiva];
				    	$(elementoAlternar).click();

				    }

				});

				function intervalo() {

					var active 			= $(".tela:visible");
					var next   			= $(active).next();

					if ($(active).hasClass("ultimo")) {
						location.reload();
					}

					var indexElementoAtual = $(next).index() - 1;
					var indexElementoAnt   = $(active).index() - 1;

					var elementoLegendaAtual = $("#lista_legendas > div")[indexElementoAtual];
					var elementoLegendaAnt   = $("#lista_legendas > div")[indexElementoAnt];

					$(elementoLegendaAtual).css({
						opacity: 1
					});

					$(elementoLegendaAnt).css({
						opacity: 0.25
					});

					$(active).hide("fast");
					$(next).show("slow");

				}
				
				$("#lista_legendas > div").click(function(){

					var indexThat = $(this).index();
					var tela      = $(".tela")[indexThat];

					$("#lista_legendas > div").css({
						opacity: 0.25
					});

					$(this).css({
						opacity: 1
					});

					$(".tela:visible").hide("fast");
					$(tela).show();

					clearInterval(alternaTelas);
					alternaTelas = setInterval(intervalo, 20000);

				});

			});
						// Build the chart
			Highcharts.chart('grafico_analise', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'C/Análise x S/Análise'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoAnalise ?>
			    }],
			    colors: ['#B40404', '#298A08']
			});

			// Build the chart
			Highcharts.chart('grafico_orcamento', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'C/Orçamento x S/Orçamento'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoOrcamento ?>
			    }],
			    colors: ['#B40404', '#298A08']
			});

						// Build the chart
			Highcharts.chart('grafico_requisitos', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'C/Requisitos x S/Requisitos'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoRequisitos ?>
			    }],
			    colors: ['#B40404', '#298A08']
			});

			// Build the chart
			Highcharts.chart('grafico_dia', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'abertos x resolvidos no dia'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoDia ?>
			    }],
			    colors: ['#298A08','#B40404']
			});

			// Build the chart
			Highcharts.chart('grafico_mes', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'abertos x resolvidos no mês'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoMes ?>
			    }],
			    colors: ['#298A08','#B40404']
			});

			// Build the chart
			Highcharts.chart('grafico_dia_erro', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'abertos x resolvidos no dia'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoDiaErro ?>
			    }],
			    colors: ['#298A08','#B40404']
			});

			// Build the chart
			Highcharts.chart('grafico_mes_erro', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'abertos x resolvidos no mês'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoMesErro ?>
			    }],
			    colors: ['#298A08','#B40404']
			});

			// Build the chart
			Highcharts.chart('grafico_efetivacao_validacao', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'efetivação x validação'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                },
			                connectorColor: 'silver'
			            }
			        }
			    },
			    series: [{
			        name: 'Share',
			        data: <?= $graficoEfetivacaoValidacao ?>
			    }],
			    colors: ['#298A08','#B40404']
			});
		</script>
	</body>
</html>

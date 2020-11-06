<?php

include '../admin/dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$tv = false;

if (!empty($_GET['tv']) and ($_GET['tv'] == 'true')) {
    $tv = true;
}

if (false === $tv) {
    include "menu_bs.php";
    $refresh_content = '90';
} else {
    echo '<html>
            <head>
                <title>Relatório Backlog</title>
                <link type="text/css" rel="stylesheet" href="css/css.css" />
                <link type="text/css" rel="stylesheet" href="css/menu.css" />
                <link type="text/css" rel="stylesheet" href="css/styles_navigation.css" />
                <link href="../imagens/tc_2009.ico" rel="shortcut icon" />
                <head>
            <body>
        ';
    $refresh_content = '300; url=adm_painel.php';
}

$meta_refresh = '';

if ($_POST["acao"] == "pesquisar" or empty($_POST["acao"]))
{
	$relatorio         = $_POST["relatorio"];
	$tipo              = $_POST["tipo"];
	$atendente_suporte = $_POST["atendente_suporte"];
	$analista          = $_POST["analista"];
	$desenvolvedor     = $_POST["desenvolvedor"];
	$fabrica           = $_POST["fabrica"];
	$tipo_resultado    = $_POST['tipo_resultado'];
	$tipo_data         = $_POST['tipo_data'];

	if (empty($_POST['acao'])) {
		$relatorio      = 'mensal';
		$tipo           = 'todos';
		$tipo_resultado = 'postit';

		$meta_refresh = '<meta http-equiv="Refresh" content="' . $refresh_content . '">';
	}

	if (strlen($relatorio) == 0)
	{
		$msg_erro .= "Selecione se o Relatório é Semanal ou Mensal <br />";
	}

	switch ($relatorio)
	{
		case "semanal":
				$data_inicial = $_POST["startDate"];
				$data_final   = $_POST["endDate"];

				if (strlen($data_inicial) == 0 or strlen($data_final) == 0)
				{
					$msg_erro .= "Data Inválida <br />";
				}

				if(strlen($msg_erro) == 0)
				{
					list($di, $mi, $yi) = explode("/", $data_inicial);

					if(!checkdate($mi,$di,$yi))
						$msg_erro .= "Data Inicial Inválida <br />";
				}

				if(strlen($msg_erro) == 0)
				{
					list($df, $mf, $yf) = explode("/", $data_final);

					if(!checkdate($mf,$df,$yf))
						$msg_erro = "Data Final Inválida <br />";
				}

				if(strlen($msg_erro) == 0)
				{
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final   = "$yf-$mf-$df";
				}
		break;
		case "mensal":
				$data_mes = $_POST["mes"];
				$data_ano = $_POST["ano"];

				if (empty($_POST['acao'])) {
					date_default_timezone_set('America/Sao_Paulo');
					$data_mes = (int) date('m');
					$data_ano = date('Y');
					$mes = $data_mes;
					$ano = $data_ano;
				}

				if (strlen($ano) == 0)
				{
					$msg_erro .= "Selecione um ano <br />";
				}

				if (strlen($mes) == 0)
				{
					$msg_erro .= "Selecione um mês <br />";
				}
		break;
	}

	if (strlen($tipo) == 0)
	{
		$msg_erro .= "Selecione um Tipo <br />";
	}

	if (empty($msg_erro))
	{
		switch ($relatorio)
		{
			case "semanal":
					$where = "AND tbl_hd_chamado.data_resolvido::date BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
					$order = "ORDER BY tbl_hd_chamado.hd_chamado";
			break;
			case "mensal":
				if ($tipo_data=='resolvido') {
					$extract1 = " EXTRACT(WEEK FROM tbl_hd_chamado.data_resolvido) as semana ";
					$extract2 = " EXTRACT(WEEK FROM tbl_hd_chamado.data_resolvido) ";
				 } else {
					$extract1 = " EXTRACT(WEEK FROM tbl_backlog_item.data_ult_alteracao) as semana ";
					$extract2 = " EXTRACT(WEEK FROM tbl_backlog_item.data_ult_alteracao) ";
				}


					$select = ", $extract1";
					if ($tipo_data=='resolvido') {
						$where = " UPPER(tbl_hd_chamado.status) = 'RESOLVIDO' AND DATE_TRUNC('month', tbl_hd_chamado.data_resolvido) = DATE('$ano-$mes-01') ";
					} else {
						$where = "  DATE_TRUNC('month', tbl_backlog_item.data_ult_alteracao) = DATE('$ano-$mes-01') ";
					}
					$order = "ORDER BY $extract2, tbl_hd_chamado.hd_chamado";
			break;
		}

		if (strlen($atendente_suporte) > 0)
		{
			$where .= "AND tbl_backlog_item.suporte = $atendente_suporte ";
		}

		if (strlen($analista) > 0)
		{
			$where .= "AND tbl_backlog_item.analista = $analista ";
		}

		if (strlen($desenvolvedor) > 0)
		{
			$where .= " AND tbl_backlog_item.desenvolvedor = $desenvolvedor ";
		}

		if (strlen($fabrica) > 0)
		{
			$where .= " AND tbl_hd_chamado.fabrica = $fabrica ";
		}

		if (strlen($tipo) > 0)
		{
			if ($tipo == "erro")
			{
				$where .= " AND tbl_hd_chamado.tipo_chamado = 5 ";
			}
			else if ($tipo == "alteracao")
			{
				$where .= " AND tbl_hd_chamado.tipo_chamado IN (1, 2, 3, 4, 6, 7) ";
			}
		}

		/*$sql = "SELECT
					tbl_backlog_item.hd_chamado,
					tbl_fabrica.nome,
					tbl_tipo_chamado.tipo_chamado,
					tbl_tipo_chamado.descricao,
					tbl_backlog_item.horas_analisadas,
					tbl_backlog_item.horas_utilizadas,
					tbl_backlog_item.analista,
					tbl_backlog_item.desenvolvedor,
					tbl_backlog_item.suporte,
					TO_CHAR(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY') AS resolvido
					$select
				FROM
					tbl_backlog_item
				JOIN
					tbl_hd_chamado
					ON
						tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
				JOIN
					tbl_tipo_chamado
					ON
						tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
				JOIN
					tbl_fabrica
					ON
						tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				WHERE
					UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
					$where
				$order";*/

		$sql = "SELECT
					DISTINCT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.data,
						tbl_hd_chamado.data_aprovacao,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.hora_desenvolvimento,
						tbl_hd_chamado.horas_suporte,
						tbl_fabrica.nome,
						tbl_tipo_chamado.tipo_chamado,
						tbl_tipo_chamado.descricao,
						(
							select
								case when horas_analisadas isnull then 0 else horas_analisadas end||'|'||case when analista isnull then 0 else analista end||'|'||case when desenvolvedor isnull then 0 else desenvolvedor end||'|'||case when suporte isnull then 0 else suporte end
							from
								tbl_backlog_item bi
							where
								bi.hd_chamado = tbl_hd_chamado.hd_chamado
							order by
								backlog asc
							limit 1
						) as backlog_item,
						TO_CHAR(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY') AS resolvido
						$select
				FROM
					tbl_hd_chamado
				JOIN
					tbl_backlog_item ON tbl_backlog_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN
					tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
				JOIN
					tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				WHERE
					$where
				$order";

		$resultado = $sql;
		$resx = pg_query($con, $sql);

		if (pg_num_rows($resx) == 0 and count($_REQUEST)) {
			$msg_erro = "Nenhum Chamado Encontrado <br />";
		} else {
			echo "
				<div class='container-fluid'>
					<div class='panel panel-warning'>
						<div class='panel-heading'>Consulta</div>
						<div class='panel-body' style='max-height:33vh;overflow-y:auto;'><code>$sql</code></div>
					</div>
				</div>";
			if ($relatorio == "mensal" and $tipo == "todos") {
				if ($tipo_data=='resolvido') {
					$extract1 = " EXTRACT(WEEK FROM tbl_hd_chamado.data_resolvido) as semana ";
					$extract2 = " EXTRACT(WEEK FROM tbl_hd_chamado.data_resolvido) ";
				 } else {
					$extract1 = " EXTRACT(WEEK FROM tbl_backlog_item.data_ult_alteracao) as semana ";
					$extract2 = " EXTRACT(WEEK FROM tbl_backlog_item.data_ult_alteracao) ";
				}

				$sqlM = "SELECT
						$extract1 
						FROM
							tbl_backlog_item
						JOIN
							tbl_hd_chamado
							ON
								tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
						JOIN
							tbl_tipo_chamado
							ON
								tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
						JOIN
							tbl_fabrica
							ON
								tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
						WHERE
							$where
						GROUP BY
							$extract2
						ORDER BY
							$extract2";
				$resM = pg_query($con, $sqlM);
				
				for ($i = 0; $i < pg_num_rows($resM); $i++)
				{
					$QtdeSemanas[] = pg_result($resM, $i, "semana");
				}
			}
		}
	}
}

function calc_horas($admin, $hd_chamado)
	{
		global $con;

		if(!empty($admin)) {
				$cond = "	AND tbl_hd_chamado_atendente.admin = $admin ";
		}

		$sqlH = "SELECT
					EXTRACT(EPOCH FROM SUM( CASE WHEN data_termino is null THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio ))/3600 AS horas_chamado
				FROM
					tbl_backlog_item
				JOIN
					tbl_hd_chamado
					ON
						tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
				LEFT JOIN
					tbl_hd_chamado_atendente
					ON
						tbl_hd_chamado_atendente.hd_chamado = tbl_backlog_item.hd_chamado
				JOIN
					tbl_fabrica
					ON
						tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				WHERE UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
				AND tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
				$cond
				GROUP BY
					tbl_backlog_item.backlog_item, tbl_hd_chamado_atendente.hd_chamado
				ORDER BY
					tbl_hd_chamado_atendente.hd_chamado";

		$resH = pg_query($con, $sqlH);

		$horas_chamado = pg_result($resH, 0, "horas_chamado");

		if (strlen($horas_chamado) > 0)
		{
			list($h, $m) = explode(".", $horas_chamado);
			$min = ($horas_chamado - $h) * 60;
			list($m, $s) = explode(".", $min);
			if (strlen($m) == 1)
			{
				$m = "0$m";
			}
			$horas_chamado = "$h.$m";
			return $horas_chamado;
		}
		else
		{
			return "0";
		}
	}

	function con_admin($admin)
	{
		global $con;

		$sql = "SELECT nome_completo, login FROM tbl_admin WHERE admin = $admin";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0)
		{
			$nome = pg_result($res, 0, "nome_completo");

			if (strlen($nome) > 0)
			{
				return $nome;
			}
			else
			{
				$login = pg_result($res, 0, "login");

				if (strlen($login) > 0)
				{
					return $login;
				}
				else
				{
					return "";
				}
			}
		}
		else
		{
			return "";
		}
	}

	function calc_week($day,$month,$year)
	{
		return ceil(($day + date("w",mktime(0,0,0,$month,1,$year)))/7);
	}

$mesArray[1] = "Janeiro";
$mesArray[2] = "Fevereiro";
$mesArray[3] = "Março";
$mesArray[4] = "Abril";
$mesArray[5] = "Maio";
$mesArray[6] = "Junho";
$mesArray[7] = "Julho";
$mesArray[8] = "Agosto";
$mesArray[9] = "Setembro";
$mesArray[10] = "Outubro";
$mesArray[11] = "Novembro";
$mesArray[12] = "Dezembro";

echo $meta_refresh;

?>
<link href="js/js_custom/themes/custom-theme/jquery.ui.all.css" rel="stylesheet" />
<link href="js/js_custom/themes/custom-theme/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="all"/>
<link href="../plugins/jquery/jpaginate/css/style.css" rel="stylesheet" />

<?php
if ($tipo_resultado == "postit") {
	echo '<link rel="stylesheet" href="css/backlog_postit.css" type="text/css" />';
}
?>

<style>
	.titulo_pagina
	{
		color: #3B3E63;
		font-size: 16px;
		border: 0;
		width: 780px;
		margin: 0 auto;
		margin-top: 14px;
		font-weight: bold;
		text-align: center;
	}

	.center
	{
		width: 620px;
		margin: 0 auto;
		text-align: center;
	}

	#tbl_pesquisa
	{
		border: 0px;
		width: 620px;
		margin: 0 auto;
		margin-top: 20px;
		margin-bottom: 14px;
		border-collapse: collapse;
	}

	.th_top
	{
		color: #FFF;
		font-size: 12px;
		-webkit-border-radius: 12px 12px 0px 0px;
		-moz-border-radius: 12px 12px 0px 0px;
		-o-border-radius: 12px 12px 0px 0px;
		-ms-border-radius: 12px 12px 0px 0px;
		border-radius: 12px 12px 0px 0px;
		padding: 4px;
		background-color: rgb(59,62,99);
		background-image: linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -o-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -moz-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -ms-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgb(80,81,126)),
		color-stop(1, rgb(59,62,99))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#50517E', endColorstr='#3B3E63', GradientType=0);
	}

	.th_bottom
	{
		color: #FFF;
		font-size: 12px;
		-webkit-border-radius: 0px 0px 12px 12px;
		-moz-border-radius: 0px 0px 12px 12px;
		-o-border-radius: 0px 0px 12px 12px;
		-ms-border-radius: 0px 0px 12px 12px;
		border-radius: 0px 0px 12px 12px;
		padding: 4px;
		background-color: rgb(59,62,99);
		background-image: linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -o-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(80,81,126)),
		color-stop(1, rgb(59,62,99))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#50517E', endColorstr='#3B3E63', GradientType=0);
	}

	#tbl_pesquisa td
	{
		color: #000;
		font-size: 12px;
		background-color: #eeeeee;
		font-weight: bold;
	}

	input[type=text]
	{
		height: 22px;
		border: 1px #666666 solid;
		background-color: #fdfdfd;
		color: #3B3E63;
	}

	select
	{
		height: 22px;
		border: 1px #666666 solid;
		background-color: #fdfdfd;
		color: #3B3E63;
	}

	option
	{
		height: 22px;
		border: 1px #585C94 solid;
		background-color: #3B3E63;
		color: #fff;
	}

	#botao a
	{
		text-decoration: none;
		color: #3B3E63;
		font-size: 11px;
	}

	#botao a:hover
	{
		text-decoration: none;
		color: #3B3E63;
		font-size: 11px;
	}

	.btn
	{
		position:relative;
		padding:10px 10px;
		padding-right:40px;
		background-color: rgb(229,229,229);
		background-image: linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -o-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(202,202,202)),
		color-stop(1, rgb(229,229,229))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#CACACA', endColorstr='#E5E5E5', GradientType=0);
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		-o-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		-moz-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		-o-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
	}

	.btn:active
	{
		top:3px;
		background-image: linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -o-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(229,229,229)),
		color-stop(1, rgb(202,202,202))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#CACACA', endColorstr='#E5E5E5', GradientType=0);
		-webkit-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		-moz-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		-o-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
	}

	.btn::before
	{
		background-color:#B4B4B4;
		background-image:url(imagens/right_arrow.png);
		background-repeat:no-repeat;
		background-position:center center;
		content:"";
		width:20px;
		height:20px;
		position:absolute;
		right:15px;
		top:50%;
		margin-top:-9px;
		-webkit-border-radius: 50%;
		-moz-border-radius: 50%;
		-o-border-radius: 50%;
		border-radius: 50%;
		-webkit-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		-moz-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		-o-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
	}

	.btn:active::before
	{
		top:50%;
		margin-top:-12px;
		-webkit-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		-moz-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		-o-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
	}

	.ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year
	{
		width: 80px;
	}

	.ui-datepicker
	{
		width: 320px;
	}

	.ui-button-text
	{
		font-size: 10px;
	}

	#data_semanal
	{
		margin-left: 10px;
		margin-top: 20px;
	}

	#data_semanal #di_df
	{
		background-color: #3B3E63;
		color: #fff;
		font-size: 14px;
		padding: 4px 4px 4px 4px;
		-webkit-border-radius: 6px 6px 6px 6px;
		-moz-border-radius: 6px 6px 6px 6px;
		-o-border-radius: 6px 6px 6px 6px;
		-ms-border-radius: 6px 6px 6px 6px;
		border-radius: 6px 6px 6px 6px;
		width: 320px;
		font-weight: normal;
	}

	#data_semanal #startDate, #data_semanal #endDate
	{
		color: #EBC70B;
	}

	#data_mensal
	{
		padding-left: 10px;
	}

	.msg_erro
	{
		width: 620px;
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		padding: 5px 0px 5px 0px;
		text-align:center;
		-webkit-border-radius: 12px 12px 12px 12px;
		-moz-border-radius: 12px 12px 12px 12px;
		-o-border-radius: 12px 12px 12px 12px;
		-ms-border-radius: 12px 12px 12px 12px;
		border-radius: 12px 12px 12px 12px;
		background-image: linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -o-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(237,0,0)),
		color-stop(1, rgb(231,81,81))
		);
	}

	#tbl_pesquisa_resultado
	{
		width: 1100px;
		margin: 0 auto;
		border-collapse: collapse;
	}

	#tbl_pesquisa_resultado td, #tbl_pesquisa_resultado th
	{
		padding: 2px;
	}

	.pesquisa_titulo
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(59,62,99);
	}

	.pesquisa_titulo_red
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(237,0,0);
	}

	.pesquisa_titulo_green
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(0,142,0);
	}

	.pesquisa_subtitulo
	{
		color: rgb(59,62,99);
		font-size: 13px;
		font-weight: bold;
		background-color: rgb(255,255,255);
	}

	.pesquisa_dados
	{
		color: rgb(0,0,0);
		font-size: 12px;
		text-align: center;
		background-color: 238,238,238;
	}

	#totais
	{
		width: 620px;
		margin: 0 auto;
		border: 0;
		font-size: 11px;
		font-weight: bold;
	}

	#excel
	{
		margin: 0 auto;
		text-align: center;
	}

	#excel a
	{
		text-decoration: none;
		font-size: 13px;
	}
</style>

<script src="js/js_custom/jquery-1.7.1.js"></script>
<script src="js/js_custom/ui/jquery-ui-1.8.18.custom.js"></script>
<script src="js/js_custom/ui/jquery.ui.core.js"></script>
<script src="js/js_custom/ui/jquery.ui.widget.js"></script>
<script src="js/js_custom/ui/jquery.ui.button.js"></script>
<script src="js/js_custom/ui/jquery.ui.datepicker.js"></script>
<script src="../plugins/jquery/jpaginate/jquery.paginate.js"></script>
<script src="../js/jquery.maskedinput2.js"></script>
<script>
	function abreChamado(chamado) {
		window.open('adm_chamado_detalhe.php?hd_chamado=' + chamado);

	}

	jQuery(function($){
				$.datepicker.regional['pt-BR'] = {
					closeText: 'Fechar',
					prevText: '&#x3c;Anterior',
					nextText: 'Pr&oacute;ximo&#x3e;',
					currentText: 'Hoje',
					monthNames: ['Janeiro','Fevereiro','Mar&ccedil;o','Abril','Maio','Junho',
					'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
					monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun',
					'Jul','Ago','Set','Out','Nov','Dez'],
					dayNames: ['Domingo','Segunda-feira','Ter&ccedil;a-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sabado'],
					dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'],
					dayNamesMin: ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'],
					weekHeader: 'Sm',
					dateFormat: 'dd/mm/yy',
					firstDay: 0,
					isRTL: false,
					showMonthAfterYear: false,
					yearSuffix: ''};
				$.datepicker.setDefaults($.datepicker.regional['pt-BR']);
	});

	$(function() {
		var startDate;
		var endDate;

		var selectCurrentWeek = function() {
			window.setTimeout(function () {
				$('.week-picker').find('.ui-datepicker-current-day a').addClass('ui-state-active')
			}, 1);
		}

		$('.week-picker').datepicker( {
			showOtherMonths: true,
			selectOtherMonths: true,
			onSelect: function(dateText, inst) {
				var date = $(this).datepicker('getDate');
				startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay());
				endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 6);
				var dateFormat = inst.settings.dateFormat || $.datepicker._defaults.dateFormat;
				$('#startDate').html($.datepicker.formatDate( dateFormat, startDate, inst.settings ));
				$('#endDate').html($.datepicker.formatDate( dateFormat, endDate, inst.settings ));
				$('input[name=startDate]').val($.datepicker.formatDate( dateFormat, startDate, inst.settings ));
				$('input[name=endDate]').val($.datepicker.formatDate( dateFormat, endDate, inst.settings ));

				selectCurrentWeek();
			},
			beforeShowDay: function(date) {
				var cssClass = '';
				if(date >= startDate && date <= endDate)
					cssClass = 'ui-datepicker-current-day';
				return [true, cssClass];
			},
			onChangeMonthYear: function(year, month, inst) {
				selectCurrentWeek();
			}
		});

		$('.week-picker .ui-datepicker-calendar tr').live('mousemove', function() { $(this).find('td a').addClass('ui-state-hover'); });
		$('.week-picker .ui-datepicker-calendar tr').live('mouseleave', function() { $(this).find('td a').removeClass('ui-state-hover'); });

		$("#tipo_relatorio").buttonset();
		$("label[for^=tipo_relatorio]").attr("style","width: 80px; font-size: 12px; text-align: center;");

		$("#tipo_chamado").buttonset();
		$("label[for^=tipo_chamado]").attr("style","width: 80px; font-size: 12px; text-align: center;");

		$("#tipo_resultado").buttonset();
		$("#tipo_data").buttonset();
		$("label[for^=tipo_resultado]").attr("style","width: 80px; font-size: 12px; text-align: center;");
		$("label[for^=tipo_data]").attr("style","width: 80px; font-size: 12px; text-align: center;");

		$("input[name=relatorio]").change(function () {
			var valor = $(this).val();

			if (valor == "semanal")
			{
				$("#data_mensal").hide("slow");
				$("#data_semanal").show("slow");
			}

			if (valor == "mensal")
			{
				$("#data_semanal").hide("slow");
				$("#data_mensal").show("slow");
			}
		});

		var relatorio = "<?=$relatorio?>";

		if (relatorio == "semanal")
		{
			$("#data_semanal").show("slow");
		}
		if (relatorio == "mensal" || relatorio == "")
		{
			$("#data_mensal").show("slow");
		}
	});
</script>

<?php if (false === $tv): ?>

<form id="frm_pesquisa" method="POST">

	<div class="titulo_pagina">
		Relatório Backlog
	</div>

	<?
	if (strlen($msg_erro) > 0)
	{
	?>
		<div class="msg_erro" style="margin: 0 auto; margin-top: 20px;">
			<?
			echo $msg_erro;
			?>
		</div>
	<?
	}
	?>

	<div class="center">
		<table id="tbl_pesquisa">
			<tr>
				<th class="th_top" colspan="3">
					Parâmetros de Pesquisa
				</th>
			</tr>
			<tr>
				<td colspan="3" style="text-align: center; font-weight: bold; padding-top: 20px;">
					<div id="tipo_relatorio">
						<input type="radio" name="relatorio" id="tipo_relatorio_1" value="semanal" <?if ($relatorio == "semanal") echo "CHECKED";?> />
						<label for="tipo_relatorio_1">Semanal</label>
						<input type="radio" name="relatorio" id="tipo_relatorio_2" value="mensal" <?if ($relatorio == "mensal" or empty($relatorio)) echo "CHECKED";?> />
						<label for="tipo_relatorio_2">Mensal</label>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div id="data_semanal" style="display: none;">
						<div class="week-picker"></div>
						<div id="di_df">
							<label>Data Inicial:&nbsp;</label><span id="startDate"><?=$data_inicial?></span>
							<input type="hidden" name="startDate" value="<?=$data_inicial?>" />
							&nbsp;&nbsp;&nbsp;
							<label>Data Final:&nbsp;</label><span id="endDate"><?=$data_final?></span>
							<input type="hidden" name="endDate" value="<?=$data_final?>" />
						</div>
					</div>
					<div id="data_mensal" style="display: none;">
						<div style="width: 100px; float: left;">
							Mês
							<br />
							<select name="mes">
								<option value=""></option>
								<?
								$i = 1;
								foreach ($mesArray as $mes)
								{
									if ($data_mes == $i)
									{
										$selected = "selected";
									}
									else
									{
										$selected = "";
									}

									echo "<option value='$i' $selected>$mes</option>";
									$i++;
								}
								?>
							</select>
						</div>
						<div style="width: 100px; float: left;">
							Ano
							<br />
							<select name="ano">
								<?
								$ano_atual = Date("Y");
								for ($ano = $ano_atual; $ano >= 2000; $ano--)
								{
									if ($data_ano == $ano)
									{
										$selected = "SELECTED";
									}
									else
									{
										$selected = "";
									}

									echo "<option value='$ano' $selected>$ano</option>";
								}
								?>
							</select>
						</div>
					</div>
				</td>
				<td style="padding: 20px 0px 0px 10px; text-align: center;">
					<div id="tipo_chamado">
						Tipo
						<br />
						<input type="radio" name="tipo" id="tipo_chamado_1" value="todos" <?if ($tipo == "todos") echo "checked";?> />
						<label for="tipo_chamado_1">Todos</label>
						<input type="radio" name="tipo" id="tipo_chamado_2" value="erro" <?if ($tipo == "erro") echo "checked";?> />
						<label for="tipo_chamado_2">Erro</label>
						<input type="radio" name="tipo" id="tipo_chamado_3" value="alteracao" <?if ($tipo == "alteracao") echo "checked";?> />
						<label for="tipo_chamado_3">Alteração</label>
					</div>
				</td>
			</tr>
			<tr>
				<td style="padding: 20px 0px 0px 10px;">
					Suporte
					<br />
					<select name="atendente_suporte">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo = TRUE
									AND
										tbl_grupo_admin.grupo_admin = 6
								ORDER BY
									tbl_admin.nome_completo";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");

								if ($atendente_suporte == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}

								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="padding: 20px 0px 0px 10px;">
					Analista
					<br />
					<select name="analista">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo 
									AND
										tbl_grupo_admin.grupo_admin in (1,2,7)
									ORDER BY
										tbl_admin.nome_completo";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");

								if ($analista == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}

								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="padding: 20px 0px 0px 10px;">
					Desenvolvedor
					<br />
					<select name="desenvolvedor">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo = TRUE
									AND
										tbl_grupo_admin.grupo_admin in (4,2,1)
								ORDER BY
									tbl_admin.nome_completo";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");

								if ($desenvolvedor == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}

								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center; padding: 20px 0px 20px 10px;">
					Fábrica
					<br />
					<select name="fabrica">
						<option value=""></option>
						<?
						$sql = "SELECT
									fabrica,
									nome
								FROM
									tbl_fabrica
								WHERE
									ativo_fabrica = TRUE
								ORDER BY
									nome";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$fabrica_id = pg_result($res, $i, "fabrica");
								$nome       = pg_result($res, $i, "nome");

								if ($fabrica == $fabrica_id)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}

								echo "<option value='$fabrica_id' $selected>$nome</option>";
							}
						}
						?>
					</select>
				</td>
				<td>
					<div id="tipo_resultado">
						Resultado
						<br />
						<input type="radio" name="tipo_resultado" id="tipo_resultado_1" value="normal" <?if ($tipo_resultado == "normal" or empty($tipo_resultado)) echo "checked";?> />
						<label for="tipo_resultado_1">Normal</label>
						<input type="radio" name="tipo_resultado" id="tipo_resultado_2" value="postit" <?if ($tipo_resultado == "postit") echo "checked";?> />
						<label for="tipo_resultado_2">Post-It</label>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan=100%>


					<div id="tipo_data">
                                                Tipo
                                                <br />
                                                <input type="radio" name="tipo_data" id="tipo_data_1" value="abertura" <?if ($tipo_data == "abertura") echo "checked";?> />
                                                <label for="tipo_data_1">Abertura</label>
                                                <input type="radio" name="tipo_data" id="tipo_data_2" value="resolvido" <?if ($tipo_data == "resolvido"  or empty($tipo_data)) echo "checked";?> />
                                                <label for="tipo_data_2">Resolvido</label>
                                        </div>


				</td>


			</tr>
			<tr>
				<th class="th_bottom" colspan="3" style="text-align: center; padding: 20px 0px 30px 0px">
					<input type="hidden" name="acao" value="" />
					<p id="botao" style="cursor: pointer;" onclick="$('input[name=acao]').val('pesquisar'); $('form[id=frm_pesquisa]').submit();">
						<a class="btn">
							Pesquisar
						</a>
					</p>
				</th>
			</tr>
		</table>
	</div>

</form>

<?php endif ?>

<?
if (pg_num_rows($resx) > 0)
{

	if ($tipo_resultado == "postit") {
		include 'relatorio_backlog_postit.php';
		exit;
	}
	echo $tipo; echo $relatorio;

	if (($tipo == "erro" or $tipo == "alteracao") and $relatorio == "semanal")
	{
	
		flush();

		echo `rm -f /tmp/assist/relatorio_backlog.html`;
		$fp = fopen ("/tmp/assist/relatorio_backlog.html","w");

		switch ($tipo)
		{
			case "erro":
				$xtipo = "de Erros em Programas";
				$tipochamadoArray = Array(5);
				$titulo_cor = "red";
				$titulo_cor2 = "rgb(237,0,0)";
			break;
			case "alteracao":
				$xtipo = "de Alterações de Dados";
				$tipochamadoArray = Array(1, 2, 3, 4, 6, 7);
				$titulo_cor = "green";
				$titulo_cor2 = "rgb(0,142,0)";
			break;
		}

		echo "<table border='1' id='tbl_pesquisa_resultado'>
				<tr class='pesquisa_titulo_$titulo_cor'>
					<th colspan='100%'>
						Chamados $xtipo
					</th>
				</tr>
				<tr class='pesquisa_subtitulo'>
					<th>Chamado</th>
					<th>Fábrica</th>
					<th>Tipo</th>
					<th>Hrs. Analis.</th>
					<th>Hrs. Desenvol.</th>
					<th>Hrs. Suporte</th>
					<th>Hrs. Total</th>
					<th>Analista</th>
					<th>Desenvolvedor</th>
					<th>Suporte</th>
				</tr>";


		fputs ($fp, "<table border='1'>
						<tr style='color: #fff; font-size: 14px;'>
							<th colspan='100%' style='background-color: $titulo_cor2;'>
								Chamados $xtipo
							</th>
						</tr>
						<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th style='background-color: rgb(255,255,255);'>Chamado</th>
							<th style='background-color: rgb(255,255,255);'>Fábrica</th>
							<th style='background-color: rgb(255,255,255);'>Tipo</th>
							<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
							<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
						    <th>Hrs. Suporte</th>
						    <th>Hrs. Total</th>
							<th style='background-color: rgb(255,255,255);'>Analista</th>
							<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
							<th style='background-color: rgb(255,255,255);'>Suporte</th>
						</tr>");

		$x = 0;
		$total_chamados = 0;
		$total_horas_analisadas = 0;
		$total_horas_desenvolvidas = 0;
		$totais_horas = 0 ; 
		$total_suporte = 0 ; 

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, $tipochamadoArray))
			{
				$hd_chamado       = pg_result($resx, $i, "hd_chamado");
				$nome             = pg_result($resx, $i, "nome");
				$descricao        = pg_result($resx, $i, "descricao");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$horas_analisadas = $xhoras_analisadas;
				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				if (strlen($horas_analisadas) == 0)
				{
					$horas_analisadas = 0;
				}

				$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
				$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
				$total_suporte += $horas_suporte;
				$totais_horas += $horas_totais;

				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
						<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a></td>
						<td nowrap>$nome</td>
						<td nowrap>$descricao</td>
						<td>$horas_analisadas</td>
						<td>$horas_utilizadas</td>
						<td>$horas_suporte</td>
						<td>$horas_totais</td>
						<td nowrap>$nome_a</td>
						<td nowrap>$nome_d</td>
						<td nowrap>$nome_s</td>
					</tr>";
				fputs($fp, "<tr style='color: rgb(0,0,0); font-size: 14px; text-align: center;'>
								<td style='background-color: $cor;'>$hd_chamado</td>
								<td nowrap style='background-color: $cor;'>$nome</td>
								<td nowrap style='background-color: $cor;'>$descricao</td>
								<td style='background-color: $cor;'>$horas_analisadas</td>
								<td style='background-color: $cor;'>$horas_utilizadas</td>
						<td>$horas_suporte</td>
								<td>$horas_totais</td>
								<td nowrap style='background-color: $cor;'>$nome_a</td>
								<td nowrap style='background-color: $cor;'>$nome_d</td>
								<td nowrap style='background-color: $cor;'>$nome_s</td>
							</tr>");

				$x = $x + 1;
			}
		}
		$total_chamados = $x;

		if ($x > 0)
		{
			echo "<tr class='pesquisa_subtitulo'>
					<th nowrap>Total HD $total_chamados</th>
					<th colspan='2'>&nbsp;</th>
					<th nowrap>Total $total_horas_analisadas</th>
					<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
					<th nowrap>Total $totais_horas</th>
					<th colspan='3'>&nbsp;</th>
				  </tr>
			</table>";
			fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
							<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
						    <th nowrap>Total  $totais_horas</th>
							<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
						  </tr>
					</table>");
		}

		echo "<br />";

		fclose ($fp);

		$data = date("Y-m-d").".".date("H-i-s");

		copy ("/tmp/assist/relatorio_backlog.html", "../admin/xls/relatorio_backlog.$data.xls");

		echo "<div id='excel'>
				<a href='../admin/xls/relatorio_backlog.$data.xls' target='_blank'>
					<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo Excel
				</a>
			 </div>";
	}
	else if ($tipo == "todos" and $relatorio == "semanal")
	{

		flush();
		echo `rm /tmp/assist/relatorio_backlog.html`;
		$fp = fopen ("/tmp/assist/relatorio_backlog.html","w");

		echo "<table border='1' id='tbl_pesquisa_resultado'>
				<tr class='pesquisa_titulo_red'>
					<th colspan='100%'>
						Chamados de Erros em Programas
					</th>
				</tr>
				<tr class='pesquisa_subtitulo'>
					<th>Chamado</th>
					<th>Fábrica</th>
					<th>Data Abertura</th>
					<th>Data Aprovacao</th>
					<th>Data Resolvido</th>
					<th>Tipo</th>
					<th>aaaaHrs. Analis.</th>
					<th>Hrs. Desenvol.</th>
					<th>Hrs. Suporte</th>
					<th>Hrs. Total</th>
					<th>Analista</th>
					<th>Desenvolvedor</th>
					<th>Suporte</th>
				</tr>";
		fputs ($fp, "<table border='1'>
						<tr style='color: #fff; font-size: 14px;'>
							<th colspan='100%' style='background-color: rgb(237,0,0);'>
								Chamados de Erros em Programas
							</th>
						</tr>
						<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th style='background-color: rgb(255,255,255);'>Chamado</th>
							<th style='background-color: rgb(255,255,255);'>Fábrica</th>
							<th style='background-color: rgb(255,255,255);'>Data Abertura</th>
							<th style='background-color: rgb(255,255,255);'>Data Aprovação</th>
							<th style='background-color: rgb(255,255,255);'>Data Resolvido</th>
							<th style='background-color: rgb(255,255,255);'>Tipo</th>
							<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
							<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
							<th>Hrs. Suporte</th>
						    <th>Hrs. Total</th>
							<th style='background-color: rgb(255,255,255);'>Analista</th>
							<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
							<th style='background-color: rgb(255,255,255);'>Suporte</th>
						</tr>");

		$x = 0;
		$total_chamados = 0;
		$total_horas_analisadas = 0;
		$total_horas_desenvolvidas = 0;
		$total_suporte = 0 ; 
		$totais_horas = 0 ;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, Array(5)))
			{
				$hd_chamado       = pg_result($resx, $i, "hd_chamado");
				$data_abertura    = pg_result($resx, $i, "data");
				$data_aprovacao   = pg_result($resx, $i, "data_aprovacao");
				$data_resolvido   = pg_result($resx, $i, "resolvido");
				$nome             = pg_result($resx, $i, "nome");
				$descricao        = pg_result($resx, $i, "descricao");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$horas_analisadas = $xhoras_analisadas;
				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				if (strlen($horas_analisadas) == 0)
				{
					$horas_analisadas = 0;
				}

				$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
				$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
				$total_suporte += $horas_suporte;
				$totais_horas += $horas_totais;

				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
						<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a></td>
						<td nowrap>$nome</td>
						<td nowrap>$data_abertura</td>
						<td nowrap>$data_aprovacao</td>
						<td nowrap>$resolvido</td>
						<td nowrap>$descricao</td>
						<td>$horas_analisadas</td>
						<td>$horas_utilizadas</td>
						<td>$horas_suporte</td>
						<td>$horas_totais</td>
						<td nowrap>$nome_a</td>
						<td nowrap>$nome_d</td>
						<td nowrap>$nome_s</td>
					</tr>";
				fputs($fp, "<tr style='color: rgb(0,0,0); font-size: 14px; text-align: center;'>
								<td style='background-color: $cor;'>$hd_chamado</td>
								<td nowrap style='background-color: $cor;'>$nome</td>
								<td nowrap style='background-color: $cor;'>$data_abertura</td>
								<td nowrap style='background-color: $cor;'>$data_aprovacao</td>
								<td nowrap style='background-color: $cor;'>$resolvido</td>
								<td nowrap style='background-color: $cor;'>$descricao</td>
								<td style='background-color: $cor;'>$horas_analisadas</td>
								<td style='background-color: $cor;'>$horas_utilizadas</td>
						<td>$horas_suporte</td>
								<td>$horas_totais</td>
								<td nowrap style='background-color: $cor;'>$nome_a</td>
								<td nowrap style='background-color: $cor;'>$nome_d</td>
								<td nowrap style='background-color: $cor;'>$nome_s</td>
							</tr>");

				$x = $x + 1;
			}
		}
		$total_chamados = $x;

		echo "<tr class='pesquisa_subtitulo'>
				<th nowrap>Total HD $total_chamados</th>
				<th colspan='2'>&nbsp;</th>
				<th nowrap>Total $total_horas_analisadas</th>
				<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
				<th nowrap>Total  $totais_horas</th>
				<th colspan='3'>&nbsp;</th>
			  </tr>
			  </table>";
		fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
						<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
						<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
						<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
						<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
						<th nowrap>Total  $totais_horas</th>
						<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
					  </tr>
				</table>");

		echo "<br />";
		fputs ($fp, "<br />");

		echo "<table border='1' id='tbl_pesquisa_resultado'>
				<tr class='pesquisa_titulo_green'>
					<th colspan='100%'>
						Chamados de Alterações de Dados
					</th>
				</tr>
				<tr class='pesquisa_subtitulo'>
					<th>Chamado</th>
					<th>Fábrica</th>
					<th>Data Abertura</th>
					<th>Data Aprovação</th>
					<th>Data Resvolvido</th>
					<th>Tipo</th>
					<th>Hrs Analisadas</th>
					<th>Hrs Desenvolvidas</th>
					<th>Hrs. Suporte</th>
				    <th>Hrs. Total</th>
					<th>Analista</th>
					<th>Desenvolvedor</th>
					<th>Suporte</th>
				</tr>";
		fputs ($fp, "<table border='1'>
						<tr style='color: #fff; font-size: 14px;'>
							<th colspan='100%' style='background-color: rgb(0,142,0);'>
								Chamados de Alterações de Dados
							</th>
						</tr>
						<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th style='background-color: rgb(255,255,255);'>Chamado</th>
							<th style='background-color: rgb(255,255,255);'>Fábrica</th>
							<th style='background-color: rgb(255,255,255);'>Data Abertura</th>
							<th style='background-color: rgb(255,255,255);'>Data Aprovacao</th>
							<th style='background-color: rgb(255,255,255);'>Data Resolvido</th>
							<th style='background-color: rgb(255,255,255);'>Tipo</th>
							<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
							<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
							<th>Hrs. Suporte</th>
						    <th>Hrs. Total</th>
							<th style='background-color: rgb(255,255,255);'>Analista</th>
							<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
							<th style='background-color: rgb(255,255,255);'>Suporte</th>
						</tr>");

		$x = 0;
		$total_chamados = 0;
		$total_horas_analisadas = 0;
		$total_horas_desenvolvidas = 0;
		$total_suporte = 0 ; 
		$totais_horas = 0 ;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (!in_array($tipo_chamado, Array(5)))
			{
				$hd_chamado       = pg_result($resx, $i, "hd_chamado");
				$nome             = pg_result($resx, $i, "nome");
				$data_abertura    = pg_result($resx, $i, "data");
				$data_aprovacao   = pg_result($resx, $i, "data_aprovacao");
				$data_resolvido   = pg_result($resx, $i, "resolvido");
				$descricao        = pg_result($resx, $i, "descricao");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$horas_analisadas = $xhoras_analisadas;
				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
				$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
				$total_suporte += $horas_suporte;
				$totais_horas += $horas_totais;

				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
						<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a></td>
						<td nowrap>$nome</td>
						<td nowrap>$data_abertura</td>
						<td nowrap>$data_aprovacao</td>
						<td nowrap>$resolvido</td>
						<td nowrap>$descricao</td>
						<td>$horas_analisadas</td>
						<td>$horas_utilizadas</td>
						<td>$horas_suporte</td>
						<td>$horas_totais</td>
						<td nowrap>$nome_a</td>
						<td nowrap>$nome_d</td>
						<td nowrap>$nome_s</td>
					</tr>";
				fputs($fp, "<tr style='color: rgb(0,0,0); font-size: 14px; text-align: center;'>
								<td style='background-color: $cor;'>$hd_chamado</td>
								<td nowrap style='background-color: $cor;'>$nome</td>
								<td nowrap style='background-color: $cor;'>$data_abertura</td>
								<td nowrap style='background-color: $cor;'>$data_aprovacao</td>
								<td nowrap style='background-color: $cor;'>$descricao</td>
								<td nowrap style='background-color: $cor;'>$descricao</td>
								<td style='background-color: $cor;'>$horas_analisadas</td>
								<td style='background-color: $cor;'>$horas_utilizadas</td>
						<td>$horas_suporte</td>
								<td>$horas_totais</td>
								<td nowrap style='background-color: $cor;'>$nome_a</td>
								<td nowrap style='background-color: $cor;'>$nome_d</td>
								<td nowrap style='background-color: $cor;'>$nome_s</td>
							</tr>");

				$x = $x + 1;
			}
		}
		$total_chamados = $x;

		echo "<tr class='pesquisa_subtitulo'>
				<th nowrap>Total HD $total_chamados</th>
				<th colspan='2'>&nbsp;</th>
				<th nowrap>Total $total_horas_analisadas</th>
				<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
				<th nowrap>Total  $totais_horas</th>
				<th colspan='3'>&nbsp;</th>
			  </tr>
			  </table>";
		fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
						<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
						<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
						<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
						<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
						<th nowrap>Total  $totais_horas</th>
						<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
					  </tr>
				</table>");

		echo "<br />";

		$x = 0;
		$total_horas_a_erro = 0;
		$total_horas_d_erro = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado       = pg_result($resx, $i, "hd_chamado");
				$backlog_item      = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor    = $xnome_d;
				$horas_analisadas  = $xhoras_analisadas;
				$horas_utilizadas  = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);

				$total_horas_a_erro = $total_horas_a_erro + $horas_analisadas;
				$total_horas_d_erro = $total_horas_d_erro + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_erros = $x;
		}

		$x = 0;
		$total_horas_a_desenvolvimento = 0;
		$total_horas_d_desenvolvimento = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (!in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado       = pg_result($resx, $i, "hd_chamado");
				$backlog_item      = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor    = $xnome_d;
				$horas_analisadas  = $xhoras_analisadas;
				$horas_utilizadas  = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);
				
				$total_horas_a_desenvolvimento = $total_horas_a_desenvolvimento + $horas_analisadas;
				$total_horas_d_desenvolvimento = $total_horas_d_desenvolvimento + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_desenvolvimento = $x;
		}

		$total = $total_erros + $total_desenvolvimento;
		$total_geral_horas_a = $total_horas_a_erro + $total_horas_a_desenvolvimento;
		$total_geral_horas_d = $total_horas_d_erro + $total_horas_d_desenvolvimento;

		$porcentagem_erros = ($total_erros * 100) / $total;
		$porcentagem_erros = number_format($porcentagem_erros, 0, "", "");

		$porcentagem_desenvolvimento = ($total_desenvolvimento * 100) / $total;
		$porcentagem_desenvolvimento = number_format($porcentagem_desenvolvimento, 0, "", "");

		$porcentagem_horas_a_erros = ($total_horas_a_erro * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_erros = number_format($porcentagem_horas_a_erros, 0, "", "");

		$porcentagem_horas_a_desenvolvimento = ($total_horas_a_desenvolvimento * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_desenvolvimento = number_format($porcentagem_horas_a_desenvolvimento, 0, "", "");

		$porcentagem_horas_d_erros = ($total_horas_d_erro * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_erros = number_format($porcentagem_horas_d_erros, 0, "", "");

		$porcentagem_horas_d_desenvolvimento = ($total_horas_d_desenvolvimento * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_desenvolvimento = number_format($porcentagem_horas_d_desenvolvimento, 0, "", "");

		echo "<table id='totais'>
				<tr>
					<td colspan='3' style='text-align: center;'>
						Total de Chamados do Dia $data_inicial á $data_final = $total
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total HD Erros: $total_erros = $porcentagem_erros%
					</td>
					<td rowspan='4' style='width: 0.1px; background-color: #000;'>
					</td>
					<td style='text-align: left;'>
						Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Geral Horas Analisadas: $total_geral_horas_a
					</td>
					<td style='text-align: left;'>
						Total Geral Horas Desenvolvidas: $total_geral_horas_d
					</td>
				</tr>
			  </table>";

		fputs ($fp, "<br />");

		fputs ($fp, "<table style='font-weight: bold;'>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td colspan='2' style='text-align: center;' nowrap>
								Total de Chamados do Dia $data_inicial á $data_final = $total
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total HD Erros: $total_erros = $porcentagem_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Geral Horas Analisadas: $total_geral_horas_a
							</td>
							<td style='text-align: left;' nowrap>
								Total Geral Horas Desenvolvidas: $total_geral_horas_d
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
					  </table>");

		fclose ($fp);

		$data = date("Y-m-d").".".date("H-i-s");

		copy ("/tmp/assist/relatorio_backlog.html", "../admin/xls/relatorio_backlog.$data.xls");

		echo "<br />";

		echo "<div id='excel'>
				<a href='../admin/xls/relatorio_backlog.$data.xls' target='_blank'>
					<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo Excel
				</a>
			 </div>";
	}
 	else if (($tipo == "erro" or $tipo == "alteracao") and $relatorio == "mensal")
	{
		flush();
		echo `rm /tmp/assist/relatorio_backlog.html`;
		$fp = fopen ("/tmp/assist/relatorio_backlog.html","w");

		switch ($tipo)
		{
			case "erro":
				$xtipo = "de Erros em Programas";
				$tipochamadoArray = Array(5);
				$titulo_cor = "red";
				$titulo_cor2 = "rgb(237,0,0)";
			break;
			case "alteracao":
				$xtipo = "de Alterações de Dados";
				$tipochamadoArray = Array(1, 2, 3, 4, 6, 7);
				$titulo_cor = "green";
				$titulo_cor2 = "rgb(0,142,0)";
			break;
		}

		$x = 0;
		$total_chamados = 0;
		$total_horas_analisadas = 0;
		$total_horas_desenvolvidas = 0;
		$total_suporte = 0 ; 
		$totais_horas = 0 ;
		$semana_ant = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, $tipochamadoArray))
			{
				$semana       = pg_result($resx, $i, "semana");
				$resolvido    = pg_result($resx, $i, "resolvido");

				$resolvido    = explode("/", $resolvido);

				$semana_calc = calc_week($resolvido[0],$resolvido[1],$resolvido[2]);

				if (empty($semana_ant))
				{
					echo "<table border='1' id='tbl_pesquisa_resultado'>
							<tr class='pesquisa_titulo_$titulo_cor'>
								<th colspan='100%'>
									Chamados $xtipo
								</th>
							</tr>
							<tr class='pesquisa_titulo'>
								<th colspan='100%'>
									Chamados $xtipo da {$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
								</th>
							</tr>
							<tr class='pesquisa_subtitulo'>
								<th>Chamado</th>
								<th>Fábrica</th>
								<th>Tipo</th>
								<th>aaaaHrs. Analis.</th>
								<th>Hrs. Desenvol.</th>
								<th>Hrs. Suporte</th>
								<th>Hrs. Total</th>
								<th>Analista</th>
								<th>Desenvolvedor</th>
								<th>Suporte</th>
							</tr>";
					fputs ($fp, "<table border='1'>
									<tr style='color: #fff; font-size: 14px;'>
										<th colspan='100%' style='background-color: $titulo_cor2;'>
											Chamados $xtipo
										</th>
									</tr>
									<tr style='color: #fff; font-size: 14px;'>
										<th colspan='100%' style='background-color: rgb(59,62,99);'>
											Chamados $xtipo da {$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
										</th>
									</tr>
									<tr style='color: rgb(59,62,99); font-size: 14px;'>
										<th style='background-color: rgb(255,255,255);'>Chamado</th>
										<th style='background-color: rgb(255,255,255);'>Fábrica</th>
										<th style='background-color: rgb(255,255,255);'>Tipo</th>
										<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
										<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
										<th>Hrs. Suporte</th>
										<th>Hrs. Total</th>
										<th style='background-color: rgb(255,255,255);'>Analista</th>
										<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
										<th style='background-color: rgb(255,255,255);'>Suporte</th>
									</tr>");

					$semana_ant = $semana;
				}

				if ($semana <> $semana_ant)
				{
					$total_chamados = $x;

					echo "<tr class='pesquisa_subtitulo'>
							<th nowrap>Total HD $total_chamados</th>
							<th colspan='2'>&nbsp;</th>
							<th nowrap>Total $total_horas_analisadas</th>
							<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
						    <th nowrap>Total  $totais_horas</th>
							<th colspan='3'>&nbsp;</th>
						  </tr>
						  </table>";
					fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
									<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
									<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
									<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
									<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
								    <th nowrap>Total  $totais_horas</th>
									<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
								  </tr>
							</table>");

					$x = 0;
					$total_chamados = 0;
					$total_horas_analisadas = 0;
					$total_horas_desenvolvidas = 0;
				$total_suporte = 0 ; 
				    $totais_horas = 0 ;

					echo "<br />";
					fputs ($fp, "<br />");

					echo "<table border='1' id='tbl_pesquisa_resultado'>
							<tr class='pesquisa_titulo_$titulo_cor'>
								<th colspan='100%'>
									Chamados $xtipo
								</th>
							</tr>
							<tr class='pesquisa_titulo'>
								<th colspan='100%'>
									Chamados $xtipo da {$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
								</th>
							</tr>
							<tr class='pesquisa_subtitulo'>
								<th>Chamado</th>
								<th>Fábrica</th>
								<th>Tipo</th>
								<th>aaaaaHrs. Analis.</th>
								<th>Hrs. Desenvol.</th>
								<th>Hrs. Suporte</th>
								<th>Hrs. Total</th>
								<th>Analista</th>
								<th>Desenvolvedor</th>
								<th>Suporte</th>
							</tr>";
					fputs ($fp, "<table border='1'>
									<tr style='color: #fff; font-size: 14px;'>
										<th colspan='100%' style='background-color: $titulo_cor2;'>
											Chamados $xtipo
										</th>
									</tr>
									<tr style='color: #fff; font-size: 14px;'>
										<th colspan='100%' style='background-color: rgb(59,62,99);'>
											Chamados $xtipo da {$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
										</th>
									</tr>
									<tr style='color: rgb(59,62,99); font-size: 14px;'>
										<th style='background-color: rgb(255,255,255);'>Chamado</th>
										<th style='background-color: rgb(255,255,255);'>Fábrica</th>
										<th style='background-color: rgb(255,255,255);'>Tipo</th>
										<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
										<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
										<th>Hrs. Suporte</th>
										<th>Hrs. Total</th>
										<th style='background-color: rgb(255,255,255);'>Analista</th>
										<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
										<th style='background-color: rgb(255,255,255);'>Suporte</th>
									</tr>");

					$semana_ant = $semana;
				}

				$hd_chamado       = pg_result($resx, $i, "hd_chamado");
				$nome             = pg_result($resx, $i, "nome");
				$descricao        = pg_result($resx, $i, "descricao");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$horas_analisadas = $xhoras_analisadas;
				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $hd_chamado);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				if (strlen($horas_analisadas) == 0)
				{
					$horas_analisadas = 0;
				}

				$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
				$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
				$total_suporte += $horas_suporte;
				$totais_horas += $horas_totais;

				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
						<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a></td>
						<td nowrap>$nome</td>
						<td nowrap>$descricao</td>
						<td>$horas_analisadas</td>
						<td>$horas_utilizadas</td>
						<td>$horas_suporte</td>
						<td>$horas_totais</td>
						<td nowrap>$nome_a</td>
						<td nowrap>$nome_d</td>
						<td nowrap>$nome_s</td>
					</tr>";
				fputs($fp, "<tr style='color: rgb(0,0,0); font-size: 14px; text-align: center;'>
								<td style='background-color: $cor;'>$hd_chamado</td>
								<td nowrap style='background-color: $cor;'>$nome</td>
								<td nowrap style='background-color: $cor;'>$descricao</td>
								<td style='background-color: $cor;'>$horas_analisadas</td>
								<td style='background-color: $cor;'>$horas_utilizadas</td>
						<td>$horas_suporte</td>
								<td>$horas_totais</td>
								<td nowrap style='background-color: $cor;'>$nome_a</td>
								<td nowrap style='background-color: $cor;'>$nome_d</td>
								<td nowrap style='background-color: $cor;'>$nome_s</td>
							</tr>");

				$x = $x + 1;

				if ($i == (pg_num_rows($resx) - 1) and $x > 0)
				{
					$total_chamados = $x;

					echo "<tr class='pesquisa_subtitulo'>
							<th nowrap>Total HD $total_chamados</th>
							<th colspan='2'>&nbsp;</th>
							<th nowrap>Total $total_horas_analisadas</th>
							<th nowrap>Total $total_horas_desenvolvidas</th>
							<th nowrap>Total $total_suporte</th>
				            <th nowrap>Total  $totais_horas</th>
							<th colspan='3'>&nbsp;</th>
						  </tr>
						  </table>";
					fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
									<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
									<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
									<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
									<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
									<th nowrap>Total $total_suporte</th>
								    <th nowrap>Total $totais_horas</th>
									<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
								  </tr>
							</table>");
				}
			}
		}

		echo "<br />";

		$x = 0;
		$total_horas_a_erro = 0;
		$total_horas_d_erro = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado      = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$horas_totais     = calc_horas(null, $xhd_chamado);

				$total_horas_a_erro = $total_horas_a_erro + $horas_analisadas;
				$total_horas_d_erro = $total_horas_d_erro + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_erros = $x;
		}

		$x = 0;
		$total_horas_a_desenvolvimento = 0;
		$total_horas_d_desenvolvimento = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (!in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado      = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);

				$total_horas_a_desenvolvimento = $total_horas_a_desenvolvimento + $horas_analisadas;
				$total_horas_d_desenvolvimento = $total_horas_d_desenvolvimento + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_desenvolvimento = $x;
		}

		$total = $total_erros + $total_desenvolvimento;
		$total_geral_horas_a = $total_horas_a_erro + $total_horas_a_desenvolvimento;
		$total_geral_horas_d = $total_horas_d_erro + $total_horas_d_desenvolvimento;

		echo "<table id='totais'>
				<tr>
					<td colspan='3' style='text-align: center;'>
						Total de Chamados do Mês de $mesArray[$data_mes] = $total
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total HD Erros: $total_erros
					</td>
					<td rowspan='4' style='width: 0.1px; background-color: #000;'>
					</td>
					<td style='text-align: left;'>
						Total HD Desenvolvimento: $total_desenvolvimento
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Analisadas Erros: $total_horas_a_erro
					</td>
					<td style='text-align: left;'>
						Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Desenvolvidas Erros: $total_horas_d_erro
					</td>
					<td style='text-align: left;'>
						Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Geral Horas Analisadas: $total_geral_horas_a
					</td>
					<td style='text-align: left;'>
						Total Geral Horas Desenvolvidas: $total_geral_horas_d
					</td>
				</tr>
			  </table>";

		fputs ($fp, "<br />");

		fputs ($fp, "<table style='font-weight: bold;'>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td colspan='2' style='text-align: center;' nowrap>
								Total de Chamados do Mês de $mesArray[$data_mes] = $total
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total HD Erros: $total_erros
							</td>
							<td style='text-align: left;' nowrap>
								Total HD Desenvolvimento: $total_desenvolvimento
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Analisadas Erros: $total_horas_a_erro
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Desenvolvidas Erros: $total_horas_d_erro
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Geral Horas Analisadas: $total_geral_horas_a
							</td>
							<td style='text-align: left;' nowrap>
								Total Geral Horas Desenvolvidas: $total_geral_horas_d
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
					  </table>");

		fclose ($fp);

		$data = date("Y-m-d").".".date("H-i-s");

		copy ("/tmp/assist/relatorio_backlog.html", "../admin/xls/relatorio_backlog.$data.xls");

		echo "<br />";

		echo "<div id='excel'>
				<a href='../admin/xls/relatorio_backlog.$data.xls' target='_blank'>
					<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo Excel
				</a>
			 </div>";
	}
	else if ($tipo == "todos" and $relatorio == "mensal")
	{
	
		flush();
		echo `rm /tmp/assist/relatorio_backlog.html`;
		$fp = fopen ("/tmp/assist/relatorio_backlog.html","w");
		foreach ($QtdeSemanas as $Xsemana)
		{
			$x = 0;
			$total_chamados = 0;
			$total_horas_analisadas = 0;
			$total_horas_desenvolvidas = 0;
			$total_suporte = 0 ; 
			$totais_horas = 0 ;
			$semana_calc = "";

			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{
				 $tipo_chamado = pg_result($resx, $i, "tipo_chamado");
				$semana       = pg_result($resx, $i, "semana");

				if ($semana == $Xsemana)
				{
					if ($tipo_data == 'resolvido') {
						$resolvido    = pg_result($resx, $i, "resolvido");
						if (strlen($resolvido) > 0)
						{
							$resolvido    = explode("/", $resolvido);
							$semana_calc = calc_week($resolvido[0],$resolvido[1],$resolvido[2]);
							break;
						}
					}
				}
			}

			echo "<table border='1' id='tbl_pesquisa_resultado'>
					<tr class='pesquisa_titulo_red'>
						<th colspan='100%'>
							Chamados de Erros em Programas
						</th>
					</tr>
					<tr class='pesquisa_titulo'>
						<th colspan='100%'>
							{$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
						</th>
					</tr>
					<tr class='pesquisa_subtitulo'>
						<th>Chamado</th>
						<th>Fábrica</th>
						<th>Data Abertura</th>
						<th>Data Resolvido</th>
						<th>Tipo</th>
						<th>aaaaHrs. Analis.</th>
						<th>Hrs. Desenvol.</th>
						<th>Hrs. Suporte</th>
						<th>Hrs. Total</th>
						<th>Analista</th>
						<th>Desenvolvedor</th>
						<th>Suporte</th>
					</tr>";

			fputs ($fp, "<table border='1'>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(237,0,0);'>
									Chamados de Erros em Programas
								</th>
							</tr>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(59,62,99);'>
									{$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
								</th>
							</tr>
							<tr style='color: rgb(59,62,99); font-size: 14px;'>
								<th style='background-color: rgb(255,255,255);'>Chamado</th>
								<th style='background-color: rgb(255,255,255);'>Fábrica</th>
								<th style='background-color: rgb(255,255,255);'>Data Abertura</th>
								<th style='background-color: rgb(255,255,255);'>Data Resolvido</th>
								<th style='background-color: rgb(255,255,255);'>Tipo</th>
								<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
								<th>Hrs. Suporte</th>
								<th>Hrs. Total</th>
								<th style='background-color: rgb(255,255,255);'>Analista</th>
								<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
								<th style='background-color: rgb(255,255,255);'>Suporte</th>
							</tr>");

			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{
				$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
				$semana       = pg_result($resx, $i, "semana");

				if (in_array($tipo_chamado, Array(5)) and $semana == $Xsemana)
				{
					$hd_chamado       = pg_result($resx, $i, "hd_chamado");
					$nome             = pg_result($resx, $i, "nome");
					$data_abertura    = pg_result($resx, $i, "data");
					$resolvido        = pg_result($resx, $i, "resolvido");
					$descricao        = pg_result($resx, $i, "descricao");
					$backlog_item     = pg_result($resx, $i, "backlog_item");
					list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
					$horas_analisadas = $xhoras_analisadas;
					$analista         = $xnome_a;
					$desenvolvedor    = $xnome_d;
					$suporte          = $xnome_s;

					$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
					$horas_totais     = calc_horas(null, $hd_chamado);
					$nome_a = con_admin($analista);
					$nome_d = con_admin($desenvolvedor);
					$nome_s = con_admin($suporte);

					$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
					$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
				    $total_suporte += $horas_suporte;
				    $totais_horas += $horas_totais;

					$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
							<td>
								<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
									$hd_chamado
								</a>
							</td>
							<td nowrap>
								$nome
							</td>
							<td nowrap>
								$data_abertura
							</td>
							<td nowrap>
								$resolvido
							</td>
							<td nowrap>
								$descricao
							</td>
							<td>
								$horas_analisadas
							</td>
							<td>
								$horas_utilizadas
							</td>
						<td>$horas_suporte</td>
								<td>$horas_totais</td>
							<td nowrap>
								$nome_a
							</td>
							<td nowrap>
								$nome_d
							</td>
							<td nowrap>
								$nome_s
							</td>
						</tr>";
					fputs ($fp, "<tr style='font-size: 14px; text-align: center;'>
									<td style='background-color: $cor;'>
										<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
											$hd_chamado
										</a>
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome
									</td>
									<td nowrap style='background-color: $cor;'>
										$data_abertura
									</td>
									<td nowrap style='background-color: $cor;'>
										$resolvido
									</td>
									<td nowrap style='background-color: $cor;'>
										$descricao
									</td>
									<td style='background-color: $cor;'>
										$horas_analisadas
									</td>
									<td style='background-color: $cor;'>
										$horas_utilizadas
									</td>
						<td>$horas_suporte</td>
								    <td>$horas_totais</td>
									<td nowrap style='background-color: $cor;'>
										$nome_a
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_d
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_s
									</td>
								</tr>");

					$x = $x + 1;
				}
			}

			$total_chamados = $x;

			echo "<tr class='pesquisa_subtitulo'>
					<th nowrap>Total HD $total_chamados</th>
					<th colspan='2'>&nbsp;</th>
					<th nowrap>Total $total_horas_analisadas</th>
					<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
					<th nowrap>Total $totais_horas</th>
					<th colspan='3'>&nbsp;</th>
				  </tr>
				</table>";
			fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
							<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
							<th nowrap>Total $total_suporte</th>
						    <th nowrap>Total $totais_horas</th>
							<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
						  </tr>
						</table>");

			echo "<br />";
			fputs ($fp, "<br />");

			$x = 0;
			$total_chamados = 0;
			$total_horas_analisadas = 0;
			$total_horas_desenvolvidas = 0;
				$total_suporte = 0 ; 
			$totais_horas = 0 ;

			$semana_calc = "";

			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{
				$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
				$semana       = pg_result($resx, $i, "semana");

				if ($semana == $Xsemana)
				{
					$resolvido    = pg_result($resx, $i, "resolvido");
					if (strlen($resolvido) > 0)
					{
						$resolvido    = explode("/", $resolvido);
						$semana_calc = calc_week($resolvido[0],$resolvido[1],$resolvido[2]);
						break;
					}
				}
			}

			echo "<table border='1' id='tbl_pesquisa_resultado'>
					<tr class='pesquisa_titulo_green'>
						<th colspan='100%'>
							Chamados de Alterações de Dados
						</th>
					</tr>
					<tr class='pesquisa_titulo'>
						<th colspan='100%'>
							{$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
						</th>
					</tr>
					<tr class='pesquisa_subtitulo'>
						<th>Chamado</th>
						<th>Fábrica</th>
						<th>Data Abertura</th>
						<th>Data Aprovação</th>
						<th>Data Resolvido</th>
						<th>Tipo</th>
						<th>Orçamento</th>
						<th>Hrs. Analis.</th>
						<th>Hrs. Desenvol.</th>
						<th>Hrs. Cobradas Suporte</th>
						<th>Hrs. Suporte</th>
						<th>Hrs. Total</th>
						<th>Analista</th>
						<th>Desenvolvedor</th>
						<th>Suporte</th>
					</tr>";
			fputs ($fp, "<table border='1'>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(0,142,0);'>
									Chamados de Alterações de Dados
								</th>
							</tr>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(59,62,99);'>
									{$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
								</th>
							</tr>
							<tr style='color: rgb(59,62,99); font-size: 14px;'>
								<th style='background-color: rgb(255,255,255);'>Chamado</th>
								<th style='background-color: rgb(255,255,255);'>Fábrica</th>
								<th style='background-color: rgb(255,255,255);'>Data Abertura</th>
								<th style='background-color: rgb(255,255,255);'>Data Aprovação</th>
								<th style='background-color: rgb(255,255,255);'>Data Resolvido</th>
								<th style='background-color: rgb(255,255,255);'>Tipo</th>
								<th style='background-color: rgb(255,255,255);'>Orçamento</th>
								<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
								<th>Hrs. Cobradas Suporte</th>
								<th>Hrs. Suporte</th>
								<th>Hrs. Total</th>
								<th style='background-color: rgb(255,255,255);'>Analista</th>
								<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
								<th style='background-color: rgb(255,255,255);'>Suporte</th>
							</tr>");

			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{
				$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
				$semana       = pg_result($resx, $i, "semana");

				if (!in_array($tipo_chamado, Array(5)) and $semana == $Xsemana)
				{
					$hd_chamado       = pg_result($resx, $i, "hd_chamado");
					$orcamento        = pg_result($resx, $i, "hora_desenvolvimento");
					$nome             = pg_result($resx, $i, "nome");
					$data_abertura    = pg_result($resx, $i, "data");
					$data_aprovacao   = pg_result($resx, $i, "data_aprovacao");
					$resolvido        = pg_result($resx, $i, "resolvido");
					$descricao        = pg_result($resx, $i, "descricao");
					$backlog_item     = pg_result($resx, $i, "backlog_item");
					list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
					$horas_analisadas = $xhoras_analisadas;
					$analista         = $xnome_a;
					$desenvolvedor    = $xnome_d;
					$suporte          = $xnome_s;

					$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
					$horas_suporte = calc_horas($suporte, $hd_chamado);
					$horas_totais     = calc_horas(null, $hd_chamado);
					$nome_a = con_admin($analista);
					$nome_d = con_admin($desenvolvedor);
					$nome_s = con_admin($suporte);
					$horas_suporte_cobrada = pg_result($resx,$i,"horas_suporte");
					$total_horas_analisadas    = $total_horas_analisadas + $horas_analisadas;
					$total_horas_desenvolvidas = $total_horas_desenvolvidas + $horas_utilizadas;
					$total_suporte_cobrada += $horas_suporte_cobrada;
					$total_suporte += $horas_suporte;
					$totais_horas += $horas_totais;
					$total_orcamento += $orcamento;

					$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
							<td>
								<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
									$hd_chamado
								</a>
							</td>
							<td nowrap>
								$nome
							</td>
							<td nowrap>
								$data_abertura
							</td>
							<td nowrap>
								$data_aprovacao
							</td>
							<td nowrap>
								$resolvido
							</td>
							
							<td nowrap>
								$descricao
							</td>

							<td>
								$orcamento
							</td>
							<td>
								$horas_analisadas
							</td>
							<td>
								$horas_utilizadas
							</td>
						<td>$horas_suporte_cobrada</td>
						<td>$horas_suporte</td>

								<td>$horas_totais</td>
							<td nowrap>
								$nome_a
							</td>
							<td nowrap>
								$nome_d
							</td>
							<td nowrap>
								$nome_s
							</td>
						</tr>";
					fputs ($fp, "<tr style='font-size: 14px; text-align: center;'>
									<td style='background-color: $cor;'>
										<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
											$hd_chamado
										</a>
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome
									</td>
									<td nowrap style='background-color: $cor;'>
										$data_abertura
									</td>
									<td nowrap style='background-color: $cor;'>
										$data_aprovacao
									</td>
									<td nowrap style='background-color: $cor;'>
										$resolvido
									</td>
									<td nowrap style='background-color: $cor;'>
										$descricao
									</td>
									<td style='background-color: $cor;'>
										$orcamento
									</td>
									<td style='background-color: $cor;'>
										$horas_analisadas
									</td>
									<td style='background-color: $cor;'>
										$horas_utilizadas
									</td>
						<td>$horas_suporte_cobrada</td>
						<td>$horas_suporte</td>
										<td>$horas_totais</td>
									<td nowrap style='background-color: $cor;'>
										$nome_a
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_d
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_s
									</td>
								</tr>");

					$x = $x + 1;
				}
			}

			$total_chamados = $x;

			echo "<tr class='pesquisa_subtitulo'>
					<th nowrap>Total HD $total_chamados</th>
					<th colspan='2'>&nbsp;</th>
					<th nowrap>Total $total_orcamento</th>
					<th nowrap>Total $total_horas_analisadas</th>
					<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte_cobrada</th>
					<th nowrap>Total $total_suporte</th>
					<th nowrap>Total  $totais_horas</th>
					<th colspan='3'>&nbsp;</th>
				  </tr>
				</table>";
			fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
							<th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
						    <th nowrap>Total  $totais_horas</th>
							<th colspan='3' style='background-color: rgb(255,255,255);'>&nbsp;</th>
						  </tr>
						</table>");

			echo "<br />";
			fputs ($fp, "<br />");
		}

		echo "<br />";

		$x = 0;
		$total_horas_a_erro = 0;
		$total_horas_d_erro = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado      = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);

				$total_horas_a_erro = $total_horas_a_erro + $horas_analisadas;
				$total_horas_d_erro = $total_horas_d_erro + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_erros = $x;
		}

		$x = 0;
		$total_horas_a_desenvolvimento = 0;
		$total_horas_d_desenvolvimento = 0;

		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");

			if (!in_array($tipo_chamado, Array(5)))
			{
				$xhd_chamado       = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);

				$total_horas_a_desenvolvimento = $total_horas_a_desenvolvimento + $horas_analisadas;
				$total_horas_d_desenvolvimento = $total_horas_d_desenvolvimento + $horas_utilizadas;

				$x = $x + 1;
			}

			$total_desenvolvimento = $x;
		}

		$total = $total_erros + $total_desenvolvimento;
		$total_geral_horas_a = $total_horas_a_erro + $total_horas_a_desenvolvimento;
		$total_geral_horas_d = $total_horas_d_erro + $total_horas_d_desenvolvimento;

		$porcentagem_erros = ($total_erros * 100) / $total;
		$porcentagem_erros = number_format($porcentagem_erros, 0, "", "");

		$porcentagem_desenvolvimento = ($total_desenvolvimento * 100) / $total;
		$porcentagem_desenvolvimento = number_format($porcentagem_desenvolvimento, 0, "", "");

		$porcentagem_horas_a_erros = ($total_horas_a_erro * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_erros = number_format($porcentagem_horas_a_erros, 0, "", "");

		$porcentagem_horas_a_desenvolvimento = ($total_horas_a_desenvolvimento * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_desenvolvimento = number_format($porcentagem_horas_a_desenvolvimento, 0, "", "");

		$porcentagem_horas_d_erros = ($total_horas_d_erro * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_erros = number_format($porcentagem_horas_d_erros, 0, "", "");

		$porcentagem_horas_d_desenvolvimento = ($total_horas_d_desenvolvimento * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_desenvolvimento = number_format($porcentagem_horas_d_desenvolvimento, 0, "", "");

		echo "<table id='totais'>
				<tr>
					<td colspan='3' style='text-align: center;'>
						Total de Chamados do Mês de $mesArray[$data_mes] = $total
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total HD Erros: $total_erros = $porcentagem_erros%
					</td>
					<td rowspan='4' style='width: 0.1px; background-color: #000;'>
					</td>
					<td style='text-align: left;'>
						Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Geral Horas Analisadas: $total_geral_horas_a
					</td>
					<td style='text-align: left;'>
						Total Geral Horas Desenvolvidas: $total_geral_horas_d
					</td>
				</tr>
			  </table>";

		fputs ($fp, "<br />");

		fputs ($fp, "<table style='font-weight: bold;'>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td colspan='2' style='text-align: center;' nowrap>
								Total de Chamados do Mês de $mesArray[$data_mes] = $total
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total HD Erros: $total_erros = $porcentagem_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Geral Horas Analisadas: $total_geral_horas_a
							</td>
							<td style='text-align: left;' nowrap>
								Total Geral Horas Desenvolvidas: $total_geral_horas_d
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
					  </table>");

		fclose ($fp);

		$data = date("Y-m-d").".".date("H-i-s");

		copy ("/tmp/assist/relatorio_backlog.html", "../admin/xls/relatorio_backlog.$data.xls");

		echo "<br />";

		echo "<div id='excel'>
				<a href='../admin/xls/relatorio_backlog.$data.xls' target='_blank'>
					<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo Excel
				</a>
			 </div>";
	}
}

include "rodape.php";
?>
